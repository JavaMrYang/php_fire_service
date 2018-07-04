<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/7/3
 * Time: 15:01
 */
namespace app\fire\model\office;

use think\Model;

class NewsModel extends Model{
    protected $table='tb_news';

    protected $pk='id';
    protected $autoWriteTimestamp='datetime';

    use SoftDelete;
    protected $deleteTime = 'delete_time';

    function examine(){
        return $this->hasOne('NewsExamine','news_id','id');
    }

    function assign(){
        return $this->belongsTo('app\\fire\\model\\UserModel','assgin_uid','uid')
            ->bind(['assgin_name'=>'name']);
    }

}