<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/22
 * Time: 10:55
 */
namespace app\fire\logic;

use app\fire\common\JPushClient;
use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\overall\HotModel;
use app\fire\model\task\TaskModel;
use app\fire\model\UserModel;
use think\Db;
use think\Exception;

class TaskLogic{
    /**
     * 保存任务
     * @param $data
     * @param $file
     * @return array
     */
    static function saveTask($data,$auth){
        try {
            Db::startTrans();
            if(!empty($data['hot_id'])){ //如果热点id不为空，就去查询热点
                $hot = Db::table('tb_task')->where('hot_id',$data['hot_id'])->find();
                if (!empty($hot)) return Errors::HOT_ALREADY_PUBLISH;
            }
            $recevice='';
            if(!Common::authRegion($data['task_region'],$auth['s_region']))  return [false,'不能上报其他区域的任务信息'];
            if(Common::isWhere($data,'to_name')){
                if(strpos($data['to_name'],'_')!==false){ //如果搜索到指派人有下划线，则截取他
                    $str=explode('_',$data['to_name']);
                    $data['task_obj']=$str[0];
                    $recevice=UserModel::getTelByRegionIdAndMid($str[1],$str[0]);
                }elseif ($data['to_name']=='all'){
                    $recevice='all';
                }else{
                    $tels=explode(',',$data['to_name']); //按逗号分隔任务指派人，只取第一个用户类型作为指派对象。
                    $user=UserModel::getUserDetailByTel($tels[0]);
                    if($user[0]) $data['task_obj']=$user[1]['mid'];
                    $recevice=explode(',',$data['to_name']);
                }
            }
            $add_time = Common::createSystemDate();
            $data['task_add_time'] = $add_time;
            $data['task_add_uid']=$auth['s_uid'];
            $data['task_status'] = 0; //设置任务发布的状态为已发布
            $task = new TaskModel();
            $task->save($data);

            if(!empty($task)){
                if (Common::isWhere($data,'task_images') && $data['task_images'][0] != '') {
                    $num = 0;
                    foreach ($data['task_images'] as $value) {
                        if ((Db::table('tb_file_image')->where('id', $value)->update(['source' => '11', 'source_id' => $task->task_id,'status'=>'1'])) > 0)
                            $num++;
                    }
                    if ($num == count($data['task_images'])) {
                        $message=Common::getMessageByTask($task->toArray());
                        $jg_push=new JPushClient(Errors::FIRE_APP_KEY,Errors::FIRE_APP_MASTER_SECRET);
                        $res=$jg_push->push($recevice,'火灾通知',$message);
                        var_dump($res!==false?json_encode($res):'失败');die;
                        Db::commit();
                        return [true,$task->task_id];
                    }
                }else{
                    $message=Common::getMessageByTask($task->toArray());
                    $jg_push=new JPushClient(Errors::FIRE_APP_KEY,Errors::FIRE_APP_MASTER_SECRET);
                    $res=$jg_push->push($recevice,'火灾通知',$message);
                    var_dump($res!==false?json_encode($res):'失败');die;
                    Db::commit();
                    return [true,$task->task_id];
                }
            }
        }catch (Exception $e){
            try {
                Db::rollback();
            } catch (Exception $e) {
                return Errors::Error($e->getMessage());
            }
            return Errors::Error($e->getMessage());
        }
    }
    static function countTaskTotalByCondition($data,$auth){
        try{
           $query=TaskModel::alias('t')->field('count(t.task_id) as total');
           if(Common::isWhere($data,'task_region')){
               if(!Common::authRegion($data['task_region'],$auth['s_region'])) return [false,'无法查看其他区域的任务信息'];
              $query->whereLike('t.task_region',$data['task_region'].'%') ;
           }else{
               $query->whereLike('t.task_region',$auth['region'].'%');
           }
            if(Common::isWhere($data,'task_status')){ //判断任务状态是否为空
                $query->where('task_status',$data['task_status']);
                if($data['task_status']==1){ //如果任务状态为正在执行，查询他区域下的任务
                    $query->where('t.task_region','like','%'.$auth['s_region'].'%');
                }
            }
            if(Common::isWhere($data,'task_type')){
                $typeArray=explode(',',$data['task_type']);
                $query->whereIn('task_type',$typeArray);
            }
            if(Common::isWhere($data,'task_add_uid')){
                $query->where('task_add_uid',$data['task_add_uid']);
            }
            $result=$query->find();
           return [true,$result];
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

    static function getListTaskByCondition($data,$auth){
        try{
            if(!Common::authRegion($data['task_region'],$auth['s_region'])) return [false,'无法查看其他区域的任务信息'];
           $query=TaskModel::alias('t')
                ->join('tb_region r','r.id = t.task_region')
                ->join('tb_region r1','r1.id = r.parentId','left')
                ->join('tb_region r2','r2.id = r1.parentId','left')
                ->join('tb_region r3','r3.id = r2.parentId','left')
                ->join('tb_region r4','r4.id = r3.parentId','left')
                ->join('tb_user u1','u1.uid=t.task_add_uid','left')
                ->join('tb_user u2','u2.uid=t.recv_uid','left')
                ->where('t.task_region','like','%'.$data['task_region'].'%');
            if(Common::isWhere($data,'task_status')){ //判断任务状态是否为空
                $query->where('task_status',$data['task_status']);
                if($data['task_status']==1){ //如果任务状态为正在执行，查询他区域下的任务
                    $query->where('t.task_region','like','%'.$auth['s_region'].'%');
                }
            }
            if(Common::isWhere($data,'task_type')){
                $typeArray=explode(',',$data['task_type']);
                $query->whereIn('task_type',$typeArray);
            }
            if(Common::isWhere($data,'task_add_uid')){
                $query->where('task_add_uid',$data['task_add_uid']);
            }
            $query->field('t.*,t.task_id,u1.`name` as add_name,u2.`name` as recv_name,u1.tel as add_tel,u2.tel as recv_tel,
            t.hot_id,t.task_add_time,t.task_latlng,t.task_end_time,r.`name` r,
            r1.`name` r1,r2.`name` r2,t.pointType,r3.`name` r3,r4.`name` r4')
                ->order('t.task_add_time','desc')->group('t.task_id');
            $result=$query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
            if(empty($result)) return [true, $result];
            //self::filterExpireTask($result['data']); //过滤过期字段
            foreach ($result['data'] as $key => $value){
                $result['data'][$key]['region_name'] = $value['r4'].$value['r3'].$value['r2'].$value['r1'].$value['r'];
                unset($result['data'][$key]['r']);
                unset($result['data'][$key]['r1']);
                unset($result['data'][$key]['r2']);
                unset($result['data'][$key]['r3']);
                unset($result['data'][$key]['r4']);
            }
            $result['data']=Common::removeEmpty($result['data']);
            return  [true, $result];
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }



    static function acceptTask($data,$auth){
        try{
            Db::startTrans();
            if (empty($data['task_id'])) return [false, ['任务id不能为空!', 'task id is not null']];
            $flag = TaskModel::checkCanAccept($data,$auth); //检测该任务是否能接受
            $hotId = TaskModel::checkIsHot($data['task_id']);//检测是否是卫星热点任务
            if ($hotId[0]) {  //如果热点id存在
                $hot = new HotModel();
                $hot->save([
                    'hot_receive_Time' => Common::createSystemDate(),
                    'recv_user_id' => $auth['s_uid'],
                    'hot_status'=>1,
                ],  ['hot_id' => $hotId[1]]);
            }
            if ($flag) {
                $task = new TaskModel;
                $task->save(['recv_uid' => $auth['s_uid'],
                    'task_recv_time' => Common::createSystemDate(),
                    'task_status'=>1,
                ],['task_id'=>$data['task_id']]);
                Db::commit();
                return !empty($task)?[true,$task]:[false,['任务接收失败','task is receive fail']];
            }else{
                return Errors::ASSIGN_ERROR;
            }

        }catch (Exception $e){
            Db::rollback();
           return Errors::Error($e->getMessage());
        }
    }

    static function getTaskById($data){
        try {
            if (empty($data['task_id'])) return [false, ['任务id不能为空', 'taskId is not null']];
            $task = TaskModel::getTaskById($data);
            $path = Db::table('tb_file_image')->where('source','11')->where('source_id',$data['task_id'])->field('id,path')->select();
            $task[1]['task_images']=$path;
            $result_path=Db::table('tb_file_image')->where('source','12')->where('source_id',$data['task_id'])->field('id,path')->select();
            $task[1]['task_result_image']=$result_path;
            if ($task[0]) {
                $toName = $task[1]['to_name'];
                if (!empty($toName) && strpos($toName, '_') > 0) {
                    $strArray = explode('_', $toName);
                    if (strpos($toName, '3') === 0) {
                        $task[1]['to_name'] = strlen($toName) > 1 ? $strArray[1] . '所有消防员' : '所有消防员';
                    } else if (strpos($toName, '2') === 0) {
                        $task[1]['to_name'] = strlen($toName) > 1 ? $strArray[1] . '所有护林员' : '所有护林员';
                    }
                } else if (!empty($toName) &&!strpos($toName, '_')) {
                    $telArray=explode(',',$toName);
                    foreach ($telArray as $tel){
                        $user = Db::table('tb_user')->where('tel', $tel)->find();
                        if(!empty($user))
                            $task[1]['to_name'] = empty($task[1]['to_name'])?$user['name']:$task[1]['to_name'].','.$user['name'];
                    }
                }
            }
            return is_array($task) ? [true, $task[1]] : Errors::DATA_NOT_FIND;
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

    static function feedBackTask($data,$auth){
        try{
            Db::startTrans();
            if (empty($data['task_id'])) return [false, ['任务id不能为空', 'taskId is not null']];
             $hotId=TaskModel::checkIsHot($data['task_id']);
             $flag = TaskModel::checkCanAccept($data,$auth); //检测该任务是否能接受
            if($flag){
                $hot=new HotModel();
                if($hotId[0]){
                    $hot->save([
                        'hot_status'=>'2',
                        'hot_complete_time'=>Common::createSystemDate(),
                        'recv_user_id'=>$auth['s_uid'],
                    ],['hot_id'=>$hotId[1]]);
                }
                $data['task_complete_uid']=$auth['s_uid'];
                $data['task_status']=2;
                $taskModel=new TaskModel();
                $taskModel->allowField(true)
                    ->save($data,['task_id'=>$data['task_id']]);
                if(!empty($taskModel)){
                    if (Common::isWhere($data,'task_result_image') && $data['task_result_image'][0] != '') {
                        $num = 0;
                        foreach ($data['task_result_image'] as $value) {
                            if ((Db::table('tb_file_image')->where('id', $value)->update(['source' => '10', 'source_id' => $hot->hot_id,'status'=>'1'])) > 0)
                                if ((Db::table('tb_file_image')->where('id', $value)->update(['source' => '11', 'source_id' => $taskModel->id,'status'=>'1'])) > 0)
                                $num++;
                        }
                        if ($num == count($data['task_result_image'])) {
                            Db::commit();
                            return [true,$taskModel];
                        }
                    }else{
                        Db::commit();
                        return [true,$taskModel];
                    }
                }
            }else{
                return [false,'你不是任务接收人，你不能反馈'];
            }
        }catch (Exception $e){
            Db::rollback();
            return Errors::Error($e->getMessage());
        }
    }
    static function cancelTask($data,$auth){
        try{
            if($auth['s_role']!=2) return [false,'您不是管理员，您不能取消'];
            $task=TaskModel::get($data['task_id']);
            if(empty($task)) return Errors::DATA_NOT_FIND;
            if($task->task_add_uid!=$auth['s_uid']) return [false,'您不能任务发布人，您不能取消'];
            if($task->task_status!==0) return [false,'该任务已经不处于发布状态了'];
           Db::startTrans();
           $task=TaskModel::getTaskStatusAndHotIdById($data);
           if($task[0]&&Common::isWhere($task[1],'hot_id')){ //如果热点id不为空，就把他删除
               $hot=new HotModel();
               $hot->allowField(true)->save([
                   'hot_status'=>-1
               ],['hot_id'=>$task[1]['hot_id']]);
           }
           $taskModel=new TaskModel();
           $taskModel->save([
               'task_status'=>-1
           ],['task_id'=>$data['task_id']]);
           Db::commit();
           return empty($taskModel)?Errors::DATA_NOT_FIND:[true,$taskModel];
        }catch (Exception $e){
            Db::rollback();
            return Errors::Error($e->getMessage());
        }
    }

     static function initTaskCount($type=null){
            $result=[];
            if(!empty($type)){
                for($j=0;$j<=4;$j++){
                    $result['finish'.$j]=0;
                    $result['total'.$j]=0;
                }
            }else{
                for($i=5;$i<=12;$i++){
                    $result['finish'.$i]=0;
                    $result['total'.$i]=0;
                }
            }
            return $result;
     }

     static function filterTaskObjCount($data){
         $taskCountArray=[];//定义任务统计返回的数组
         if(empty($data)) return $data;
         $timeArray=array_unique(array_column($data,'add_time')); //去除重复的时间
         foreach ($timeArray as $time) {
             $taskCount = self::initTaskCount(1);
             $taskCount['time'] = $time;
             foreach ($data as $taskData) {
                 if ($time == $taskData['add_time']) {
                     //2 护林员,3 消防员,4 无人机,5 载人机
                     //0：消防员，1：护林员，2：无人机，3：载人机
                    if($taskData['task_obj']==3){ //代表消防员
                        $taskCount['finish0']=$taskData['count_finish_task'];
                        $taskCount['total0'] = $taskData['count_total_task'];
                    }elseif ($taskData['task_obj']==2){ //代表护林员
                        $taskCount['finish1']=$taskData['count_finish_task'];
                        $taskCount['total1'] = $taskData['count_total_task'];
                    }elseif ($taskData['task_obj']==4) { //代表无人机
                        $taskCount['finish2'] = $taskData['count_finish_task'];
                        $taskCount['total2'] = $taskData['count_total_task'];
                    }elseif ($taskData['task_obj']==5){//代表载人机
                        $taskCount['finish3'] = $taskData['count_finish_task'];
                        $taskCount['total3'] = $taskData['count_total_task'];
                    }
                 }
             }
             $taskCount['total'] = 0;//定义任务总数的初始值为0
             for ($i = 0; $i <= 4; $i++) $taskCount['total'] += $taskCount['total' . $i];
             array_push($taskCountArray, $taskCount);
         }
         return $taskCountArray;
     }

   static function filterTaskCount($data){
       $taskCountArray=[];//定义任务统计返回的数组
        if(empty($data)) return $data;
        $timeArray=array_unique(array_column($data,'add_time')); //去除重复的时间
        foreach ($timeArray as $time){
            $taskCount=self::initTaskCount();
            $taskCount['time']=$time;
            foreach ($data as $taskData){
                if($time==$taskData['add_time']){
                    if ($taskData['task_type'] == 5) {
                        $taskCount['finish' . '5'] = $taskData['count_finish_task'];
                        $taskCount['total' . '5'] = $taskData['count_total_task'];
                    } elseif ($taskData['task_type'] == 6) {
                        $taskCount['finish' . '6'] = $taskData['count_finish_task'];
                        $taskCount['total' . '6'] = $taskData['count_total_task'];
                    } elseif ($taskData['task_type'] == 7) {
                        $taskCount['finish' . '7'] = $taskData['count_finish_task'];
                        $taskCount['total' . '7'] = $taskData['count_total_task'];
                    } elseif ($taskData['task_type'] == 1 || $taskData['task_type'] = 8) {
                        $taskCount['finish' . '8'] = $taskData['count_finish_task'];
                        $taskCount['total' . '8'] = $taskData['count_total_task'];
                    } elseif ($taskData['task_type'] == 2 || $taskData['task_type'] = 9) {
                        $taskCount['finish' . '9'] = $taskData['count_finish_task'];
                        $taskCount['total' . '9'] = $taskData['count_total_task'];
                    } elseif ($taskData['task_type'] == 3 || $taskData['task_type'] = 10) {
                        $taskCount['finish' . '10'] = $taskData['count_finish_task'];
                        $taskCount['total' . '10'] = $taskData['count_total_task'];
                    } elseif ($taskData['task_type'] == 4 || $taskData['task_type'] = 11) {
                        $taskCount['finish' . '11'] = $taskData['count_finish_task'];
                        $taskCount['total' . '11'] = $taskData['count_total_task'];
                    } elseif ($taskData['task_type'] == 12) {
                        $taskCount['finish' . '12'] = $taskData['count_finish_task'];
                        $taskCount['total' . '12'] = $taskData['count_total_task'];
                    }
                }
            }
            $taskCount['total']=0;//定义任务总数的初始值为0
            for ($i = 5; $i <= 12; $i++) $taskCount['total'] += $taskCount['total'.$i];
            array_push($taskCountArray,$taskCount);
        }
        return $taskCountArray;
   }

    static function countTaskByCondition($data,$auth){
        try{
            if(Common::isWhere($data,'task_region')){
                if(!Common::authRegion($data['task_region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
            }
            if(Common::isWhere($data,'start_time'))
            $data['start_time']=$data['start_time'].'-01'.' 00:00:00';
            if(Common::isWhere($data,'end_time')){
                $timestamp = strtotime($data['end_time']);
                $mdays=date( 't', $timestamp );
                $end_time = date( 'Y-m-' . $mdays . ' 23:59:59', $timestamp );
                $data['end_time']=$end_time;
            }
            $finish_query=TaskModel::alias('t')->where('t.task_status','=','2');
            if(Common::isWhere($data,'type')&&$data['type']==1) $finish_query->whereIn('t.task_obj',['1','2',
                '3','4','5']);
            if(Common::isWhere($data,'start_time')) $finish_query->where('t.task_add_time','>=',$data['start_time']);
            if(Common::isWhere($data,'end_time'))   $finish_query->where('t.task_add_time','< time',$data['end_time']);
            if(Common::isWhere($data,'task_region')) $finish_query->whereLike('t.task_region',$data['task_region'].'%');
           if(Common::isWhere($data,'type')&&$data['type']==1){
               $finish_query->field('count(t.task_type) count_finish_task,t.task_obj,left(t.task_add_time,7) add_time')
                   ->group('left(t.task_add_time,7),t.task_obj'); //统计已完成数
           }else{
               $finish_query->field('count(t.task_type) count_finish_task,t.task_type,left(t.task_add_time,7) add_time')
                   ->group('left(t.task_add_time,7),t.task_type'); //统计已完成数
           }
            $total_query=TaskModel::alias('to');
            if(Common::isWhere($data,'type')&&$data['type']==1) $finish_query->whereIn('t.task_obj',['1','2',
                '3','4','5']);
            if(Common::isWhere($data,'start_time')) $total_query->where('to.task_add_time','>=',$data['start_time']);
            if(Common::isWhere($data,'end_time'))   $total_query->where('to.task_add_time','< time',$data['end_time']);
            if(Common::isWhere($data,'task_region'))    $total_query->whereLike('to.task_region',$data['task_region'].'%');
            if(Common::isWhere($data,'type')&&$data['type']==1) { //统计任务类型为指派对象
                $total_query->field('count(to.task_type) count_total_task,to.task_obj,left(to.task_add_time,7) add_time')
                    ->group('left(to.task_add_time,7),to.task_obj'); //统计总数
                $result=Db::query('select a.count_finish_task,b.count_total_task,a.add_time,a.task_obj from '.$finish_query->buildSql().'a 
            inner join '.$total_query->buildSql().' b on b.add_time = a.add_time and b.task_obj = a.task_obj ');
            }else{
                $total_query->field('count(to.task_type) count_total_task,to.task_type,left(to.task_add_time,7) add_time')
                    ->group('left(to.task_add_time,7),to.task_type'); //统计总数
                $result=Db::query('select a.count_finish_task,b.count_total_task,a.add_time,a.task_type from '.$finish_query->buildSql().'a 
            inner join '.$total_query->buildSql().' b on b.add_time = a.add_time and b.task_type = a.task_type ');
            }
            if(Common::isWhere($data,'type')&&$data['type']==1){  //如果是统计指派对象,则
               $result=self::filterTaskObjCount($result);
            }else $result=self::filterTaskCount($result); //过滤任务成为统计的结果
            return [true,$result];
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }


    function refuseTask($data,$auth){
        $task=TaskModel::get($data['task_id']);
        if(empty($task)) return Errors::DATA_NOT_FIND;
        $flag = TaskModel::checkCanAccept($data,$auth); //检测该任务是否能接受
        if(!$flag) return [false,'您不是任务指派人，您不能拒绝'];
        Db::startTrans();
        try{
            if(!empty($task->hot_id)){ //如果热点id不为空，把他设置为删除状态
                $hot=new HotModel();
                $hot->where('hot_id',$task->hot_id)->update(['hot_status'=>-1]);
        }
        $result=TaskModel::update(['task_status'=>-3,'task_refuse_time'=>Common::createSystemDate()],
            ['task_id'=>$data['task_id']]);
        //TaskModel::destroy($data['task_id']);
        return empty($result)?Errors::UPDATE_ERROR:[true,$result];
        }catch (Exception $exception){
            Db::rollback();
            return Errors::Error($exception->getMessage());
        }

    }

    function countHlyCompleteTask($data,$auth){

    }
}