<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/30
 * Time: 15:49
 */
namespace app\fire\logic;

use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\hly\OnWorkModel;
use app\fire\model\UserModel;
use think\Db;
use think\Error;
use think\Exception;

class OnWorkLogic{
    static function saveOnWork($data,$auth){
        try{
            $time=Common::createSystemDate();
            $data['record_date']=date_format($time,'y-m-d');
            $data['record_time']=date_format($time,'h:i:s');
            $data['uid']=$auth['s_uid'];
            $user=UserModel::getUserDetailByUid($auth['s_uid']);
            if($user[0])$data['tel']=$user[1]['tel'];
            $on_work=new OnWorkModel();
            $on_work->save($data);
            return empty($on_work)?Errors::ADD_ERROR:[true,$on_work];
        }catch (Exception $e){
            return $e->getMessage();
        }
    }

    static function getWorkListByCondition($data){
        try {
            $start_time = $data['year'] . '-' . $data['month'] . '-01';
            $timestamp = strtotime($start_time);
            $mdays = date('t', $timestamp);
            $end_time = $data['year'] . '-' . $data['month'] . '-' . $mdays;
            $query = OnWorkModel::alias('w')
                ->join('tb_user u', 'u.uid=w.uid', 'left')
                ->where('record_date', '>=', $start_time)
                ->where('record_date', '<=', $end_time);
            if (Common::isWhere($data, 'tel')) {
                $query->where('w.tel', $data['tel']);
            }
            $result = $query->order('record_date desc')->group('w.id')
                ->field('w.id,w.address,w.latlng,w.record_date,w.tel,w.record_time')->select();
            return empty($result) ? Errors::DATA_NOT_FIND : [true, $result->toArray()];
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }
    static function isWork($data,$auth){
        try{
            $work=OnWorkModel::where('record_date',$data['record_date'])
                ->where('uid',$auth['s_uid']);
            return empty($work)?Errors::DATA_NOT_FIND:[true,$work];
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

    static function hlyAppraiseList($data){
        try{
            $query=OnWorkModel::alias('w')
                ->join('tb_User u','u.uid=w.uid','left')
                ->join('tb_region r','r.id = u.region')
                ->join('tb_region r1','r1.id = r.parentId','left')
                ->join('tb_region r2','r2.id = r1.parentId','left')
                ->join('tb_region r3','r3.id = r2.parentId','left')
                ->join('tb_region r4','r4.id = r3.parentId','left')
                ->where('u.region','like','%'.$data['region'].'%');
            if(Common::isWhere($data,'tel')){
                $query->where('u.tel',$data['tel']);
            }
            if(Common::isWhere($data,'start_time')){
                $query->where('record_date','>=',$data['start_time']);
            }
            if(Common::isWhere($data,'end_time')){
                $query->where('record_time','<=',$data['end_time']);
            }
            $query->field('w.tel,u.region,u.name,(SELECT COUNT(*) FROM tb_Task WHERE recv_uid=u.uid) count_task_recv,
            (SELECT COUNT(*) FROM tb_Task WHERE task_complete_uid=u.uid) count_task_complete,count(*) count_work')
            ->group('w.tel,u.region,u.name');
            $result=$query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        foreach ($result['data'] as $key => $value){
            $result['data'][$key]['region_name'] = $value['r4'].$value['r3'].$value['r2'].$value['r1'].$value['r'];
            unset($result['data'][$key]['r']);
            unset($result['data'][$key]['r1']);
            unset($result['data'][$key]['r2']);
            unset($result['data'][$key]['r3']);
            unset($result['data'][$key]['r4']);
        }
        return empty($result) ? Errors::DATA_NOT_FIND : [true, $result];
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }
}