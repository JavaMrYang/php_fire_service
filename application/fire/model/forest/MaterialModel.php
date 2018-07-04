<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/13
 * Time: 15:00
 */
namespace app\fire\model\forest;

use think\Model;

class MaterialModel extends Model{
    protected $table='tb_material';
    protected $pk='id';

    protected $autoWriteTimestamp='datetime'; //自动写入的时间戳

    use SoftDelete;
    protected $deleteTime='delete_time';
}