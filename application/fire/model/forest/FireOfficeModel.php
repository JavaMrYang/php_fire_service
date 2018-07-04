<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/9
 * Time: 16:54
 */
namespace app\fire\model\forest;

use think\Model;
use think\model\concern\SoftDelete;

class FireOfficeModel extends Model {
    protected $table='tb_fire_office';

    protected $pk='id';

    protected $autoWriteTimestamp='datetime'; //自动写入的时间戳

    use SoftDelete;
    protected $deleteTime='delete_time';

    function getUser(){
        return $this->belongsTo('app\\fire\\model\\UserModel','input_uid','uid')
            ->bind('name');
    }

    function office_user(){
        return $this->hasMany('app\\fire\\model\\UserModel','input_uid','uid');
    }

    function office_region(){
        return $this->hasMany('pp\\fire\\model\\regionModel','id','region');
    }
}