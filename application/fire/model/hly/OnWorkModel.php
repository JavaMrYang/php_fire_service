<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/30
 * Time: 15:50
 */
namespace app\fire\model\hly;

use think\Model;

class OnWorkModel extends Model {
    protected $table='tb_on_work';

    protected $pk='id';

    protected $autoWriteTimestamp='datetime'; //自动写入的时间戳

    use SoftDelete;
    protected $deleteTime='delete_time';
}