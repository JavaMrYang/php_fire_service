<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/31
 * Time: 16:58
 */
namespace app\fire\model\location;

use think\Model;

class HlyLatLngModel extends Model{
    protected $table='tb_hly_latlng';

    protected $pk='id';

    protected $autoWriteTimestamp = 'datetime'; //开启自动写入时间戳
}