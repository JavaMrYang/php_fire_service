<?php
/**
 * Created by PhpStorm.
 * User: 呵.谢勇
 * Date: 2018/6/14
 * Time: 13:58
 */
namespace app\fire\validate;

class FireWatchtower extends BaseValidate {  
    protected $rule=[
        'region'=>'require|max:50|region',
        'location'=>'require|max:50',
        'create_year'=>'require',
        //'input_time'=>'require',
        'established_time'=>'require',
        'administrator'=>'require|max:20',
        'phone'=>'require|tel',
        'height' => 'require|float|between:0.01,99999999.99',
        'create_elevation' => 'require|float|between:0.01,99999999999.99',
        'coverage_area' => 'require|float|between:0.01,99999999999.99',
        'loading_equipment'=>'require|max:255',
        'describe'=>'max:255',
       
    ];

    protected $message=[
        'region.require'=>'区域必填',
        'region.max'=>'区域不能超过五十个字符',
        'location.require'=>'位置必填',
        'location.max'=>'位置不能超过五十个字符',
        'create_year.require'=>'建设年份必填',
        //'input_time.require'=>'录入时间必填',
        'established_time'=>'成立时间必填',
        'administrator.require'=>'管理者必填',
        'administrator.max'=>'管理者不能超过二十个字符',
        'phone.require'=>'联系电话必填',
        'height.require'=>'高度必填',
        'height.float'=>'高度必为浮点型',
        'height.between'=>'高度取值区间为：0.01至99999999.99',
        'create_elevation.require'=>'建设海拔必填',
        'create_elevation.float'=>'建设海拔必为浮点型',
        'create_elevation.between'=>'建设海拔取值区间为：0.01至99999999999.99',
        'coverage_area.require'=>'覆盖面积必填',
        'coverage_area.float'=>'覆盖面积必为浮点型',
        'coverage_area.between'=>'覆盖面积取值区间为：0.01至99999999999.99',
        'loading_equipment.require'=>'装载设备必填',
        'loading_equipment.max'=>'装载设备不能超过二百个字符',
        //'describe.require'=>'描述必填',
        'describe.max'=>'描述不能超过二百五十五个字符',

       
    ];

    protected $scene=[
        'add'=>['region','location','create_year','administrator','phone','height','create_elevation','describe','coverage_area','loading_equipment','established_time'],
        'edit'=>['region','location','create_year','administrator','phone','height','create_elevation','describe','coverage_area','loading_equipment','established_time'],
    ];
}