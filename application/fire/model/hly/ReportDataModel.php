<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/28
 * Time: 13:58
 */
namespace app\fire\model\hly;

use think\Model;
use think\model\concern\SoftDelete;

class ReportDataModel extends Model {
    protected $table='tb_report_data';

    protected $pk='id';

    protected $autoWriteTimestamp='datetime'; //自动写入的时间戳

    use SoftDelete;
    protected $deleteTime='delete_time'; //使用软删除
}