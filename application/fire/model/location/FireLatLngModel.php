<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/31
 * Time: 16:49
 */
namespace app\fire\model\location;

use think\Model;

class FireLatLngModel extends Model{
    protected $table='tb_fire_latlng';

    protected $pk='id';

    protected $autoWriteTimestamp = 'datetime'; //开启自动写入时间戳
}