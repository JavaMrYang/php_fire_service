<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/1
 * Time: 9:25
 */
namespace app\fire\logic;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use think\Db;
use think\Exception;

class HlyLatLngLogic{

    static function getHlyPathByCondition($data){
        try{
            global $start_time,$end_time;
            $query=Db::table('tb_hly_latlng')->alias('h')
                ->join('tb_user u','u.uid=h.hly.uid','left');
            if(Common::isWhere($data,'tel')){
               $query->where('h.tel',$data['tel']);
            }
            if(Common::isWhere($data,'city')){
                $query->where('h.city','city');
            }
            if(Common::isWhere($data,'town')){
                $query->where('h.town','town');
            }
            if(Common::isWhere($data,'village')){
                $query->where('h.village','village');
            }
            if(Common::isWhere($data,'time')){
                $start_time=$data['time'].' 00:00:00';
                $end_time=$data['time'].' 23:59:59';
                $query->whereBetweenTime('time',$start_time,$end_time);
            }
            $result=$query->field('h.city,h.town,h.village,h.lat,h.lng,h.tel,h.time')->find();
            return empty($result)?Errors::DATA_NOT_FIND:[true,$result->toArray()];
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

    static function filterHlyByCondition($data,$drone_state){
        $flag=fasle;
        if(Common::isWhere($data,'phone')){
            if($data['phone']==$drone_state['publishName']) $flag=true;
        }
        if(Common::isWhere($data,'city')){
            $flag=$data['city']==$drone_state['city'];
        }
        if(Common::isWhere($data,'town')){
            $flag=$data['town']==$drone_state['town'];
        }
        if(Common::isWhere($data,'village'))
            $flag=$data['village']==$drone_state['village'];

        return $flag;
    }

    static function getHlyByDroneList($drone){
        $hly=[
            'areaName'=>$drone['region_name'],
            'isPublish'=>$drone['isPublish'],
            'publishName'=>$drone['publishName'],
            'userLatitude'=>$drone['userLatitude'],
            'userLongitude'=>$drone['userLongitude'],
            'time'=>$drone['onLineTime'],
        ];
        return $hly;
    }
}