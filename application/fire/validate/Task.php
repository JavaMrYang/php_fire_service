<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/25
 * Time: 9:13
 */
namespace app\fire\validate;

use app\fire\controller\Common;
use think\Validate;

class Task extends Validate{
    protected $rule=[
        'task_id'=>'require|number',
        'task_type'=>'require|between:1,12',
        'task_title'=>'require|max:255',
        'task_region'=>'require|number',
        'to_name'=>'require|max:255',
        'task_latlng'=>'require|max:255',
        'task_end_time'=>'require|checkEndTime',
    ];

    protected $message=[
        'task_id.require'=>'任务id不能为空',
        'task_id.number'=>'任务id必须为数字',
        'task_type.require'=>'任务类型必填',
        'task_type.between'=>'任务类型必须在1到12之间',
        'task_region.require'=>'任务区域必填',
        'task_region.number'=>'任务区域必须为数字',
        'to_name.require'=>'任务指派人必填',
        'to_name.max'=>'任务指派人不能超过255',
        'task_latlng.require'=>'任务经纬度必填',
        'task_end_time.require'=>'任务截止时间必填',
        'task_latlng.max'=>'任务经纬度不能超过255的长度',
    ];

    protected $scene=[
        'add'=>['task_type','task_title','task_region','to_name','task_latlng','task_end_time'],
        'edit'=>['task_id','task_type','task_title','task_region','to_name','task_latlng','task_end_time'],
    ];

    protected function checkEndTime($value){
      $diff_time=strtotime($value)-strtotime(Common::createSystemDate());
      return $diff_time>0?true:'任务截止时间必须大于当前时间';
    }
}