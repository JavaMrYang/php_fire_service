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

class FireTraceModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tb_fire_trace';
    //设置主键ID
    protected $pk = 'id';
    //开启时间戳
    protected $autoWriteTimestamp = 'datetime';
    //开启软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //设置模型一对一关联
    //完成
    public function traceImage()
    {
        return $this->hasOne('FireTraceImageModel','fire_trace_id','id');
    }
    public function traceMaterials()
    {
        return $this->hasOne('FireTraceMaterialsModel','fire_trace_id','id');
    }
}