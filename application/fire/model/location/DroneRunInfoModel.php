<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/31
 * Time: 16:15
 */
namespace app\fire\model\location;

use think\Model;

class DroneRunInfoModel extends Model{
    protected $table='tb_drone_run_info';

    protected $pk='id';

    protected $autoWriteTimestamp = 'datetime'; //开启自动写入时间戳
}