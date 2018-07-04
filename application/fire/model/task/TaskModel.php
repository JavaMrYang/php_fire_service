<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/22
 * Time: 10:40
 */
namespace app\fire\model\task;

use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\logic\TaskLogic;
use app\fire\model\UserModel;
use think\Db;
use think\Exception;
use think\Model;

class TaskModel extends Model {
    protected $table='tb_task';

    protected $pk='task_id';

    //开启时间戳
    protected $autoWriteTimestamp = 'datetime';

    static function checkCanAccept($data,$auth){
        try{
           $user=UserModel::getUserDetailByUid($auth['s_uid']);
           $toName=Db::table('tb_task')->where('task_id',$data['task_id'])
               ->field('to_name')->find();
            $flag=strpos($toName['to_name'],'_')===false?false:true;
           if(!$flag){
               if(strpos($toName['to_name'],$user[1][0]['tel'])!==false){//搜索接受的电话号码是否包含在指定人当中
                   return true;
               }else return false;
           }
           $mid=substr($toName['to_name'],0,1);//截取接受人的第一个长度
           foreach($user[1]['mid'] as $key=>$value){
               if(strcmp($mid,$value)){ //如果用户身份和指定身份一致，则去检索他的区域是否在这个区域内
                   if(strpos($toName,$user['region'])!==false)return true;
           }
           }
           return false;
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

    /**
     * 检测任务是否有卫星热点
     * @param $id
     * @return array
     */
    static function checkIsHot($id){
        try{
            $hotId=Db::table('tb_task')->where('task_id',$id)
                ->where('task_status','<>','-1')->field('hot_id')->find();
            return empty($hotId['hot_id'])?Errors::DATA_NOT_FIND:[true,$hotId['hot_id']];
     }catch (Exception $e){
            return  Errors::Error($e->getMessage());
        }
    }

    static function getTaskById($data){
        try{
            $result=TaskLogic::alias('t')->where('task_id',$data['task_id'])
                ->join('tb_region r','r.id = t.task_region')
                ->join('tb_region r1','r1.id = r.parentId','left')
                ->join('tb_region r2','r2.id = r1.parentId','left')->join('tb_region r3','r3.id = r2.parentId','left')
                ->join('tb_region r4','r4.id = r3.parentId','left')
                ->join('tb_user u1','u1.uid=t.task_add_uid','left')
                ->join('tb_user u2','u2.uid=t.recv_uid','left')
                ->join('tb_user u3','u3.uid=t.task_complete_uid','left')
                ->field('t.*,u1.`name` as add_name,u1.tel as add_tel,u2.tel as recv_tel,u2.`name` as recv_name,
                r.`name` r,r1.`name` r1,r2.`name` r2,t.pointType,r3.`name` r3,r4.`name` r4,u3.name compelte_name')->find();
            if(empty($result)) return Errors::DATA_NOT_FIND;
                $result=Common::removeEmpty($result);
                $result['region_name'] = $result['r4'].$result['r3'].$result['r2'].$result['r1'].$result['r'];
                unset($result['r']);
                unset($result['r1']);
                unset($result['r2']);
                unset($result['r3']);
                unset($result['r4']);
            return[true,$result];
        }catch (Exception $e){
            return  Errors::Error($e->getMessage());
        }
    }

    static function getTaskStatusAndHotIdById($data){
        try{
            $result=TaskModel::alias('t')->where('task_id',$data['task_id'])
                ->join('tb_fire_hot h','h.hot_id=t.hot_id','left')
                ->field('h.hot_id,h.hot_status')->find();
            return empty($result)?Errors::DATA_NOT_FIND:[true,$result];
        }catch (Exception $e){
            return  Errors::Error($e->getMessage());
        }
    }
}