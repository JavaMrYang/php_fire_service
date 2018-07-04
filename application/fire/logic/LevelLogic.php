<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/26
 * Time: 9:10
 */
namespace app\fire\logic;

use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\hly\LevelModel;
use app\fire\model\RegionModel;
use app\fire\model\UserModel;
use think\Db;
use think\Error;
use think\Exception;

class LevelLogic{
    function authHlyMid($auth){
        $flag=false;
        $user=UserModel::getUserDetailByUid($auth['s_uid']);
        if($user[0]){
            foreach(array_column($user[1],'mid') as $mid)
                if($mid==2) return true;
        }
        return $flag;
    }
     function addLevel($data,$auth){
        if(!$this->authHlyMid($auth)) return [false,'您不是护林员您不能请假'];
        $level_obj=LevelModel::all(['apply_tel'=>$data['apply_tel']]); //获取申请人请假集合
        if(!empty($level_obj)){ //如果查询对象不为空，验证他是否在该时间段请过假
            foreach ($level_obj as $level_model){
                $beginTime=strtotime($data['apply_start_time']);
                $endTime=strtotime($data['apply_end_time']);
                //验证是否在该时间段请过假
                if(Common::is_time_cross(strtotime($level_model['apply_start_time']),strtotime($level_model['apply_end_time'])
                    ,$beginTime,$endTime)){
                    return [false,'您在该时间段已经请过假了'];
                }
            }
        }
        $diff_day=strtotime($data['apply_end_time'])-strtotime($data['apply_start_time']); //计算请假结束时间相距开始时间的天数
        $diff_day=ceil($diff_day/86400);
        if(!empty($diff_day)) $data['level_day']=$diff_day;
        $data['status']=0; //设置他为待审核状态
        $data['apply_uid']=$auth['s_uid'];
        if(!Common::isWhere($data,'region')) $data['region']=$auth['s_region'];
        $level=new LevelModel();
        $level->allowField(true)->save($data);
        return empty($level)?Errors::ADD_ERROR:[true,$level->id];
    }

    function findLevelByCondition($data,$auth){
        $query=LevelModel::alias('l')->join('tb_user u','l.apply_tel=u.tel','left')
            ->join('tb_region r', 'r.id = u.region')
            ->join('tb_region r1', 'r1.id = r.parentId', 'left')
            ->join('tb_region r2', 'r2.id = r1.parentId', 'left')
            ->join('tb_region r3', 'r3.id = r2.parentId', 'left')
            ->join('tb_region r4', 'r4.id = r3.parentId', 'left')
            ->join('tb_user u1','l.appr_uid=u1.uid','left');
        if(Common::isWhere($data,'region')){
            if(!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
            $query->whereLike('u.region','%'.$data['region'].'%');
        }else{
            $query->whereLike('u.region','%'.$auth['s_region'].'%');
        }
        if(Common::isWhere($data,'start_time'))$query->where('l.create_time','>=',$data['start_time']);
        if(Common::isWhere($data,'end_time'))$query->where('l.create_time','<=',$data['start_time']);
        if(Common::isWhere($data,'status')) $query->where('l.status',$data['status']);
        if(Common::isWhere($data,'apply_tel')){
            $query->where('l.apply_tel|u.name',['=',$data['apply_tel']],['like','%'.$data['apply_tel'].'%'],'or');
        }
        $query->field('l.*,u.name apply_name,u1.name appr_name,r.`name` r,r1.`name` r1,r2.`name` r2,r3.`name` r3,r4.`name` r4')
        ->order('l.create_time','desc')->group('l.id');
        $result=$query->paginate($data['per_page'],false,['page'=>$data['current_page']])->toArray();
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
        return [true,$result];
    }

    function examineLevel($data,$auth){
        $level_update_array=[];  //定义更新请假的数组
        foreach ($data['id'] as $value){
            $level=LevelModel::get($value);
            if(empty($level)) return [false,'审核失败找不到，该用户id'.$value];
            if(!Common::authRegion($level['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
            if($auth['s_role']!=2) return [false,'您不是管理员您不能操作'];
            $level_update=[];
            $level_update['id']=$value;
            $level_update['status']=$data['status'];
            $level_update['appr_uid']=$auth['s_uid'];
            $level_update['appr_time']=Common::createSystemDate();
            if(Common::isWhere($data,'rebut_reason'))$level_update['rebut_reason']=$data['rebut_reason'];
            array_push($level_update_array,$level_update);
        }
        try{
            Db::startTrans();
            $level_model=new LevelModel();
            $result=$level_model->isUpdate(true)->saveAll($level_update_array);
            Db::commit();
            return empty($result)?[false,'审核出错']:[true,$result];
        }catch (Exception $exception){
            Db::rollback();
            var_dump($exception);
            return Errors::Error($exception->getMessage());
        }
    }

    function getLevelById($id){
         global $level;
         $level_model=LevelModel::get($id);
         if(!empty($level_model->status)&&$level_model->status==1){
             $level=LevelModel::get($id,['user','apprUser']);
         }else{
             $level=LevelModel::get($id,'user');
         }
        if(empty($level)) return Errors::DATA_NOT_FIND;
        $level=$level->toArray();
        $level=Common::removeEmpty($level);
        if(Common::isWhere($level,'region')){
            $region=RegionModel::getRegionNameById($level['region']);
            if($region[0])$level['region_name']=$region[1]['region_name'];
        }
        return [true,$level];
    }

}