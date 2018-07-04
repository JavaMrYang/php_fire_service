<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/24
 * Time: 11:20
 */
namespace app\fire\logic;

use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\overall\HotModel;
use app\fire\model\RegionModel;
use app\fire\model\UserModel;
use think\Db;
use think\Error;
use think\Exception;

class HotLogic{
    static function saveHot($data,$auth){
        try{
            Db::startTrans();
            $data['add_user_id']=$auth['s_uid']; //设置添加用户的id
            if(!Common::isWhere($data,'hot_add_time'))
                $data['hot_add_time']=Common::createSystemDate();
            $hot=new HotModel();
            $hot->save($data);
            if(!empty($hot)){
                if (Common::isWhere($data,'images') && $data['images'][0] != '') {
                    $num = 0;
                    foreach ($data['images'] as $value) {
                        if ((Db::table('tb_file_image')->where('id', $value)->update(['source' => '10', 'source_id' => $hot->hot_id,'status'=>'1'])) > 0)
                            $num++;
                    }
                    if ($num == count($data['images'])) {
                        Db::commit();
                        return [true,$hot->hot_id];
                    }
                }else{
                    Db::commit();
                    return [true,$hot->hot_id];
                }
            }
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }
    static function getHotByHotId($id){
        $hot=HotModel::get($id);
        if(empty($hot)) return Errors::DATA_NOT_FIND;
        $hot=Common::removeEmpty($hot->toArray());
        $hot['recv_name']='';
        if(Common::isWhere($hot,'region')) {
            $region=RegionModel::getRegionNameById($hot['region']);
            if($region[0]){$hot['region_name']=$region[1]['region_name'];}
        }
        if(Common::isWhere($hot,'recv_user_id')){
            $user=UserModel::getUserDetailByUid($hot['recv_user_id']);
            if($user[0])$hot['recv_name']=$user[1]['name'];
        }
        $path = Db::table('tb_file_image')->where('source','10')->where('source_id',$id)->field('id,path')->select();
        $hot['image_path']=$path;
        return [true,$hot];
    }

    static function getTaskDetailByHotId($data){
        try {
            if(empty($data['hot_id'])) return [false,['热点id不能为空','hot_id is not null']];
            $task = self::getTaskByHotId($data);
            if ($task[0]) {
                $path = Db::table('tb_file_image')->where('source','11')->where('source_id',$task[1]['task_id'])->field('id,path')->select();
                $task[1]['task_images']=$path;
                $result_path=Db::table('tb_file_image')->where('source','12')->where('source_id',$task[1]['task_id'])->field('id,path')->select();
                $task[1]['task_result_image']=$result_path;
                $toName = $task[1]['to_name'];
                if (!empty($toName) && strpos($toName, '_') > 0) {
                    $strArray = explode('_', $toName);
                    if (strpos($toName, '3') === 0) {
                        $task['to_name'] = strlen($toName) > 1 ? $strArray[1] . '所有消防员' : '所有消防员';
                    } else if (strpos($toName, '2') === 0) {
                        $task['to_name'] = strlen($toName) > 1 ? $strArray[1] . '所有护林员' : '所有护林员';
                    }
                } else if (!empty($toName) && strpos($toName, '_') < 0) {
                    $telArray=explode(',',$toName);
                    foreach ($telArray as $tel){
                        $user = Db::table('tb_user')->where('tel', $tel)->find();
                        if(!empty($user))
                            $task['to_name'] = $task['to_name'].$user['name'];
                    }
                }
            }
            return is_array($task) ? [true, $task[1]] : Errors::DATA_NOT_FIND;
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

    static function getTaskByHotId($data)
    {
        try {
            $result = Db::table('tb_task')->alias('t')->where('hot_id', $data['hot_id'])
                ->join('tb_region r', 'r.id = t.task_region')
                ->join('tb_region r1', 'r1.id = r.parentId', 'left')
                ->join('tb_region r2', 'r2.id = r1.parentId', 'left')
                ->join('tb_region r3', 'r3.id = r2.parentId', 'left')
                ->join('tb_region r4', 'r4.id = r3.parentId', 'left')
                ->join('tb_user u1', 'u1.uid=t.task_add_uid', 'left')
                ->join('tb_user u2', 'u2.uid=t.recv_uid', 'left')
                ->field('t.*,u1.`name` as add_name,u1.tel,u2.`name` as recv_name,r.`name` r,r1.`name` r1,
                r2.`name` r2,t.pointType,r3.`name` r3,r4.`name` r4')->find();
            if (empty($result)) return [true,$result];
            $result = Common::removeEmpty($result);
            $result['region_name'] = $result['r4'] . $result['r3'] . $result['r2'] . $result['r1'] . $result['r'];
            unset($result['r']);
            unset($result['r1']);
            unset($result['r2']);
            unset($result['r3']);
            unset($result['r4']);
            return [true, $result];
        } catch (Exception $e) {
            return Errors::Error($e->getMessage());
        }
    }

    static function getListHotByCondition($data,$auth)
    {
        try {
            if(!Common::isWhere($data,'per_page')) $data['per_page']=1000; //如果每页条数为空，默认给他1000条
            if(!Common::isWhere($data,'current_page')) $data['current_page']=1; //如果当前页为空，默认给他显示第一条
            $query = HotModel::alias('h')
                ->join('tb_region r', 'r.id = h.region')
                ->join('tb_region r1', 'r1.id = r.parentId', 'left')
                ->join('tb_region r2', 'r2.id = r1.parentId', 'left')
                ->join('tb_region r3', 'r3.id = r2.parentId', 'left')
                ->join('tb_region r4', 'r4.id = r3.parentId', 'left')
                ->join('tb_user u', 'u.uid=h.add_user_id', 'left')
                ->join('tb_user u1', 'u1.uid=h.recv_user_id', 'left')
                ->where('hot_status', '<>','-1');
              if(Common::isWhere($data,'region')){
                  if(!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
                  $query->where('h.region', 'like', '%' . $data['region'] . '%');
              }else{
                  $query->whereLike('h.region',  '%' . $auth['s_region'] . '%');
              }
            if (Common::isWhere($data, 'start_time')) {
                $query->where('hot_add_time', '>=', $data['start_time']);
            }
            if (Common::isWhere($data, 'end_time')) {
                $query->where('hot_add_time', '<=', $data['end_time']);
            }
            if (Common::isWhere($data, 'hot_status')) {
                $query->where('hot_status', $data['hot_status']);
            }
            $query->field('h.*,u.name as addName,u.tel,u1.tel as recvTel,u1.name as recvName
  ,r.`name` r,r1.`name` r1,r2.`name` r2,r3.`name` r3,r4.`name` r4')
                ->order('h.hot_add_time', 'desc')->group('h.hot_id');
            $result = $query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
            if(empty($result['data'])) return [true,$result];
            foreach ($result['data'] as $key => $value) {
                $result['data'][$key]['region_name'] = $value['r4'] . $value['r3'] . $value['r2'] . $value['r1'] . $value['r'];
                unset($result['data'][$key]['r']);
                unset($result['data'][$key]['r1']);
                unset($result['data'][$key]['r2']);
                unset($result['data'][$key]['r3']);
                unset($result['data'][$key]['r4']);
            }
            $result['data'] = Common::removeEmpty($result['data']);
            return empty($result) ? Errors::DATA_NOT_FIND : [true, $result];
        } catch (Exception $e) {
            return Errors::Error($e->getMessage());
        }
    }

    static function saveHotExcel($data,$auth){
        try {
            Db::startTrans();
            global $task;
            $hotArray=[];
            foreach (array_reverse($data) as $key => $value) {
                $rowValue = implode(" ", $value);
                if (strpos($rowValue, '报告人（签名）：') === 0) {
                    $start = strpos($rowValue, '2');
                    $time = str_replace('年', '-', substr($rowValue, $start));
                    $time = trim(str_replace('　',' ',str_replace('日', '',
                        str_replace('月', '-', $time))));
                    $task['hot_add_time']=date('Y-m-d H:i:s',strtotime($time));
                }
                if (strpos($rowValue, 'HN') === 0) {
                    $str = explode(" ", $rowValue);
                    $task['hot_no'] = $str[0];
                    $task['hot_latlng'] = Common::parseString($str[1]) . ',' .  Common::parseString($str[2]);
                    $task['px'] = $str[3];
                    $task['smoke'] = $str[4];
                    $task['content'] = $str[5];
                    $task['type'] = $str[6];
                    $task['feed_back'] = $str[8];
                    $region = RegionModel::getRegionByName($str[7]);
                    if ($region[0]) {
                        $task['region'] = $region[1]['regionId'];
                    }
                    $result=self::saveHot($task,$auth);
                    if($result[0]) array_push($hotArray,$result[1]);  //如果保存成功，放入热点数据
                }
            }
            Db::commit();
            return empty($hotArray)?Errors::ADD_ERROR:[true,$hotArray];
        }catch (Exception $e){
            Db::rollback();
            return Errors::Error($e->getMessage());
        }
    }

    static function deleteHot($id,$auth){
        $hotModel=HotModel::get($id);
        if(empty($hotModel)) return Errors::DATA_NOT_FIND;
        if(!Common::authRegion($hotModel->task_region,$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
        if($hotModel->add_user_id!=$auth['s_uid']) return [false,'您不能操作他人数据'];
        $hot=new HotModel();
        $hot->allowField(true)->save([
            'hot_status'=>-1,
            'hot_delete_time'=>Common::createSystemDate()
        ],['hot_id'=>$id]);
        return empty($hot)?Errors::DATA_NOT_FIND:[true,$hot];
    }

    static function countHotData($data,$auth){
        if(Common::isWhere($data,'type')&&$data['type']==1){  //统计本年的
            $year=date('y',time());
            $start_time=$year.'-01-01';
            $end_time=$year.'-12-31';
        }else{ //统计本月的
            $start_time=date('y-m',time()).'-01 00:00:00';
            $timestamp = strtotime(date('y-m-d',time()));
            $mdays=date( 't', $timestamp );
            $end_time = date( 'Y-m-' . $mdays . ' 23:59:59', $timestamp );
        }
        $result=HotModel::alias('h')->join('tb_region r','r.id=left(h.region,4)','left')
            ->whereBetweenTime('h.hot_add_time',$start_time,$end_time)
            ->group('left(region,4),hot_status,left(hot_add_time,9)')->order('hot_add_time')
            ->field('count(left(region,4)) count_region,r.name,hot_status,left(hot_add_time,10) add_time')->select();
        $result=self::filterHotData($result->toArray());
        return empty($result)?Errors::DATA_NOT_FIND:[true,$result];
    }

    static function filterHotData($data){
        $resultArray=[];$result=[]; //分别定义返回结果数组，
        //单个返回结果,
        if(empty($data)) return $data;
       $timeArray=array_unique(array_column($data,'add_time')); //去除重复的时间
       foreach ($timeArray as $time){
           $result['add_time']=$time;
           $status0=0;$status1=0;$status2=0;$obj=[];//市级对象,状态0，状态1，状态2;
           foreach ($data as $value){
               if($time==$value['add_time']) {
                   array_push($obj,$value);//把数组中的值存放到市级对象中
                   if($value['hot_status']==0){ //分别让热点状态进行累加
                       $status0+=$value['count_region'];
                   }elseif ($value['hot_status']==1){
                       $status1+=$value['count_region'];
                   }else{
                       $status2+=$value['count_region'];
                   }
               }
           }
           $result['status0']=$status0;
           $result['status1']=$status1;
           $result['status2']=$status2;
           $result['obj']=$obj;
           $result['total']=0;
           for($i=0;$i<3;$i++) $result['total']+=$result['status'.$i];
           array_push($resultArray,$result);
       }
       return $resultArray;
    }

    static function countTodayFireHot(){
        $today_count=HotModel::alias('h')->whereTime('h.hot_add_time','today')
        ->field('count(h.hot_id) count_today')->find()->toArray();
        $yesterday_count=HotModel::alias('h')->whereTime('h.hot_add_time','yesterday')
            ->field('count(h.hot_id) count_yesterday')->find()->toArray();
        $result=array_merge($today_count,$yesterday_count);
        return [true,$result];
    }
}