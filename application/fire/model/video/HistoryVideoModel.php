<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/8
 * Time: 15:21
 */
namespace app\fire\model\video;


use think\Model;
use think\model\concern\SoftDelete;

class HistoryVideoModel extends Model{
    protected $table='tb_history_video';

    protected $pk='id';

    //开启时间戳
    protected $autoWriteTimestamp = 'datetime';

    use SoftDelete;
    protected $deleteTime='delete_time';

    /*function hasMany($model, $foreignKey = '', $localKey = '')
    {
        $this->hasMany('User','uid','upload_uid');
    }
    function getUser(){
        $this->hasMany('User')->field('uid input_uid,name input_name,tel input_tel');
    }*/
}