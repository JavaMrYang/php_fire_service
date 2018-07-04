<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/25
 * Time: 15:32
 */

namespace app\fire\model\fire;


use think\Model;

class FireTraceImageModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tb_fire_trace_image';
    //设置主键ID
    protected $pk = 'id';
    //开启时间戳
    protected $autoWriteTimestamp = 'datetime';
}