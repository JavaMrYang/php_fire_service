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

class FireUploadModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tb_fire_upload';
    //设置主键ID
    protected $pk = 'id';

    //开启时间戳
    protected $autoWriteTimestamp = 'datetime';
    //开启软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //设置模型一对一关联
    //完成
    public function uploadImage()
    {
        return $this->hasOne('FireUploadImageModel','fire_upload_id','id')
            ->bind(['fire_image1','fire_image2','fire_image3','ortho_image1','ortho_image2','ortho_image3']);
    }

    public function trace()
    {
        return $this->hasOne('FireTraceModel','fire_id','id');
    }

    public function finish()
    {
        return $this->hasOne('FireFinishModel','fire_id','id');
    }
}