<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/22
 * Time: 11:20
 */
namespace app\fire\model\overall;


use app\fire\controller\Errors;
use think\Db;
use think\Exception;
use think\Model;
use think\model\concern\SoftDelete;

class HotModel extends Model {
    protected $table='tb_fire_hot';

    protected $pk='hot_id';

    //开启时间戳
    protected $autoWriteTimestamp = 'datetime';

    use SoftDelete;
    protected  $deleteTime='hot_delete_time';

    function user(){
        return $this->belongsTo("app\\fire\\model\\UserModel",'recv_user_id','uid')
            ->bind(['recv_name'=>'name']);
    }
}