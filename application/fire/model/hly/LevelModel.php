<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/25
 * Time: 16:29
 */
namespace app\fire\model\hly;

use think\Model;
use think\model\concern\SoftDelete;

class LevelModel extends Model{
    protected $pk='id';

    protected $table='tb_level';

    protected $autoWriteTimestamp='datetime'; //自动写入的时间戳

    use SoftDelete;
    protected $deleteTime = 'delete_time';

    function user(){
        return $this->hasOne('app\\fire\\model\\UserModel','tel','apply_tel')
            ->bind(['apply_name'=>'name']);
    }
    function apprUser(){
        return $this->hasOne('app\\fire\\model\\UserModel','uid','appr_uid')
            ->bind(['appr_name'=>'name']);
    }
}