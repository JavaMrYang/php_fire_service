<?php
/**
 * Created by PhpStorm.
 * User: 呵.谢勇
 * Date: 2018/6/14
 * Time: 13:58
 */
namespace app\fire\validate;

class FireBarrier extends BaseValidate {
    protected $rule=[
        'region'=>'require|max:50|region',
        'location'=>'require|max:50',
        'established_time'=>'require',
        //'input_time'=>'require',
        'administrator'=>'require|max:20',
        'phone'=>'require|tel',
        'length' => 'require|float|between:0.01,99999999.99',
        'width' => 'require|float|between:0.01,99999999.99',
        'describe'=>'max:255',
       
    ];

    protected $message=[
        'region.require'=>'区域必填',
        'region.max'=>'区域不能超过五十个字符',
        'location.require'=>'位置必填',
        'location.max'=>'位置不能超过五十个字符',
        'established_time.require'=>'成立时间必填',
        //'input_time.require'=>'录入时间必填',
        'administrator.require'=>'管理者必填',
        'administrator.max'=>'管理者不能超过二十个字符',
        'phone.require'=>'联系电话必填',
        'length.require'=>'长度必填',
        'length.float'=>'长度必为浮点型',
        'length.between'=>'长度取值区间为：0.01至99999999.99',
        'width.require'=>'宽度必填',
        'width.float'=>'宽度必为浮点型',
        'width.between'=>'宽度取值区间为：0.01至99999999.99',
        //'describe.require'=>'描述必填',
        'describe.max'=>'描述不能超过二百五十五个字符',

       
    ];

    protected $scene=[
        'add'=>['region','location','established_time','administrator','phone','length','width','describe'],
        'edit'=>['region','location','established_time','administrator','phone','length','width','describe'],
        'delete'=>['hot_id'],
        'HotId'=>['hot_id'],
    ];
}