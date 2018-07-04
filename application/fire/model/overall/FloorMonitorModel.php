<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/26
 * Time: 11:14
 */
namespace app\fire\model\overall;


use think\Model;
use think\model\concern\SoftDelete;

class FloorMonitorModel extends Model{
    protected $table='tb_floor_monitor';

    protected $pk='id';

    protected $autoWriteTimestamp='datetime';

    use SoftDelete;
    protected $deleteTime = 'delete_time';
}