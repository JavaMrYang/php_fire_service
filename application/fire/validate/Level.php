<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/26
 * Time: 10:13
 */
namespace app\fire\validate;

use app\fire\controller\Common;
use think\Validate;

class Level extends Validate{
    protected $rule=[
        'id'=>'require|number',
        'apply_tel'=>'require|length:11',
        'apply_start_time'=>'require|date|checkDate',
        'apply_end_time'=>'require|date',
        'apply_reason'=>'require|max:255'

    ];

    protected $message=[
        'id.require'=>'请假id必填',
        'id.number'=>'请假id必须是数字',
        'apply_tel.require'=>'申请人必填',
        'apply_tel.length'=>'申请人手机号码的长度必须在11位',
        'apply_start_time.require'=>'申请开始时间必填',
        'apply_start_time.date'=>'申请开始时间必须是日期格式',
        'apply_end_time.require'=>'申请结束时间必填',
        'apply_end_time.date'=>'申请结束时间必须是日期格式',
        'apply_reason.require'=>'申请原因必填',
        'apply_reason.max'=>'申请原因最大长度不能超过255',
    ];

    protected $scene=[
        'add'=>['apply_tel','apply_start_time','apply_end_time','apply_reason'],
        'edit'=>['id','apply_user','apply_start_time','apply_end_time','apply_reason'],
    ];

    function checkDate($value,$rule,$data){
        $cur_time=time();
        if(strtotime($value)<$cur_time) return '申请开始时间不能小于当前时间';
       return($value>$data['apply_end_time'])? '申请开始时间不能大于结束时间':true;
    }
}