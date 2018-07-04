<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/16
 * Time: 15:23
 */

namespace app\fire\model\fire;


use think\Model;
use think\model\concern\SoftDelete;

class FireFinishModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tb_fire_finish';
    //设置主键ID
    protected $pk = 'id';
    //开启时间戳
    protected $autoWriteTimestamp = 'datetime';

    //开启软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function finishImage()
    {
        return $this->hasOne('FireFinishImageModel','fire_finish_id','id');
    }
}