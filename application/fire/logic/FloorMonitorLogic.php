<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/26
 * Time: 11:16
 */
namespace app\fire\logic;

use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\overall\FloorMonitorModel;
use app\fire\model\UserModel;
use think\Db;
use think\Exception;

class FloorMonitorLogic{

    static function addFloorMonitor($data,$auth){
        if(!Common::authRegion($data['floor_region'],$auth['s_region'])) return [false,'您不能上传其他区域的数据'];
        if(!Common::isWhere($data,'input_uid')){ //如果传过来uid为空，则把cookie中的uid赋给他
            $data['input_uid']=$auth['s_uid'];
        }
        $data['input_time']=Common::createSystemDate();
        $user=UserModel::getUserDetailByUid($auth['s_uid']);
        if(!Common::isWhere($data,'input_tel')) $data['input_tel']=$user[1][0]['tel'];
        $data['status']=1; //设置状态为启用
        $floor_monitor=new FloorMonitorModel();
        $floor_monitor->allowField(true)->save($data);
        return empty($floor_monitor)?Errors::ADD_ERROR:[true,$floor_monitor['id']];
    }

    static function editFloorMonitor($data,$auth){
        $floor=FloorMonitorModel::get($data['id']);
        if(empty($floor)) return Errors::DATA_NOT_FIND;
        if(!Common::authRegion($floor['floor_region'],$auth['s_region'])) return [false,'您不能编辑其他区域的数据'];
        if(empty($data['id'])) return [false,['地面监控点id不能为空','floor id is not null']];
        $floor=new FloorMonitorModel();
        $floor->allowField(true)->save($data,['id'=>$data['id']]);
        return empty($floor)?Errors::UPDATE_ERROR:[true,$floor->toArray()];
    }

    static function getListFloorMonitorByCondition($data,$auth){
        if(!Common::authRegion($data['floor_region'],$auth['s_region'])) return [false,'您不能查看其他区域的信息'];
         $query=Db::table('tb_floor_monitor')->alias('m')
         ->join('tb_region r','r.id = m.floor_region')
         ->join('tb_region r1','r1.id = r.parentId','left')
         ->join('tb_region r2','r2.id = r1.parentId','left')
         ->join('tb_region r3','r3.id = r2.parentId','left')
         ->join('tb_region r4','r4.id = r3.parentId','left')
         ->join('tb_user u1','u1.uid=input_uid','left')
         ->where('status','<>','-1')
         ->where('floor_region','like',$data['floor_region'].'%');
         if(Common::isWhere($data,'input_name')){
             $query->where('u1.name','like','%'.$data['input_name'].'%')
                 ->whereOr('u1.tel','like','%'.$data['input_name'].'%');
         }
         if(Common::isWhere($data,'device_name')){
             $query->where('device_name','like','%'.$data['device_name'].'%');
         }
         if(Common::isWhere($data,'start_time')){
             $query->where('input_time','>=',$data['start_time']);
         }
         if(Common::isWhere($data,'end_time')){
             $query->where('input_time','<=',$data['end_time']);
         }
        $query->field('m.*,u1.name as input_name,r.`name` r,r1.`name` r1,r2.`name` r2,r3.`name` r3,r4.`name` r4')
            ->order('m.input_time','desc')->group('m.id');
        $result=$query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        $result['data']=Common::removeEmpty($result['data']);
        foreach ($result['data'] as $key => $value){
            $result['data'][$key]['region_name'] = $value['r4'].$value['r3'].$value['r2'].$value['r1'].$value['r'];
            unset($result['data'][$key]['r']);
            unset($result['data'][$key]['r1']);
            unset($result['data'][$key]['r2']);
            unset($result['data'][$key]['r3']);
            unset($result['data'][$key]['r4']);
        }
        return empty($result) ? Errors::DATA_NOT_FIND : [true, $result];
    }

    static function getFloorMonitorById($data){
        try{
           $result=Db::table('tb_floor_monitor')->alias('m')
               ->join('tb_region r','r.id = m.floor_region')
               ->join('tb_region r1','r1.id = r.parentId','left')
               ->join('tb_region r2','r2.id = r1.parentId','left')
               ->join('tb_region r3','r3.id = r2.parentId','left')
               ->join('tb_region r4','r4.id = r3.parentId','left')
               ->join('tb_user u1','u1.uid=input_uid','left')
               ->where('m.id',$data['id'])
               ->field('m.*,u1.name as input_name,r.`name` r,r1.`name` r1,r2.`name` r2,r3.`name` r3,r4.`name` r4')
               ->find();
           if(empty($result)) return Errors::DATA_NOT_FIND;
           $result=Common::removeEmpty($result);
            $result['region_name'] = $result['r4'].$result['r3'].$result['r2'].$result['r1'].$result['r'];
            unset($result['r']);
            unset($result['r1']);
            unset($result['r2']);
            unset($result['r3']);
            unset($result['r4']);
            return [true,$result];
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }
    static function deleteFloorMonitor($data,$auth){
        $floor=FloorMonitorModel::get($data['id']);
        if(empty($floor)) Errors::DATA_NOT_FIND;
        if(!Common::authRegion($floor->floor_region,$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
        $floor_monitor=new FloorMonitorModel();
        $floor_monitor->save(['status'=>-1],
            ['id'=>$data['id']]);
        FloorMonitorModel::destroy($data['id']);
        return empty($floor_monitor)?Errors::DELETE_ERROR:[true,$floor_monitor];
    }
}