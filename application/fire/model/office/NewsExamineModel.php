<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/7/3
 * Time: 15:02
 */
namespace app\fire\model\office;

use think\Model;

class NewsExamineModel extends Model{

    protected $table='tb_news_examine';

    protected $pk='id';

    protected $autoWriteTimestamp='datetime';

    use SoftDelete;
    protected $deleteTime = 'delete_time';
}