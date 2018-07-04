<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/22
 * Time: 17:30
 */
namespace app\fire\validate;

use think\Validate;

class FireOffice extends Validate{
    protected $rule=[
        'id'=>'require|number|max:50',
        'region'=>'require|number|max:20',
        'location'=>'require|max:255',
        'established_time'=>'require|date',
        'phone'=>'require|number',
        'desc'=>'require',
        'office_person'=>'require|chs',
        'administrator'=>'require',
    ];

    protected $message=[
        'id.require'=>'办公室id必填',
        'id.number'=>'办公室id必须是数字',
        'region.require'=>'区域必填',
        'region.max'=>'区域的最大长度为20',
        'established_time.require'=>'建立时间必填',
        'established_time.date'=>'建立时间必需为日期',
        'location.require'=>'位置必填',
        'phone.require'=>'手机号码必填',
        'phone.number'=>'手机号必须是数字',
        'administrator.require'=>'管理者必填',
        'desc'=>'描述必填',
        'office_person'=>'办公室成员必填',
    ];

    protected $scene=[
        'add'=>['region','location','established_time','phone','administrator'],
        'edit'=>['id','region','location','established_time','phone','administrator'],
        'delete'=>['id'],
    ];
}