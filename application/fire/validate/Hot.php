<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/29
 * Time: 13:58
 */
namespace app\fire\validate;

class Hot extends BaseValidate {
    protected $rule=[
        'hot_id'=>'require|number',
        'hot_latlng'=>'require|max:50',
        'hot_add_time'=>'require',
        'region'=>'require|max:20|region',
        'hot_status'=>'in:-1,0,1,2',
        'add_user_id'=>'require',
        'create_time'=>'require',
        'content'=>'require|chsAlpha',
    ];

    protected $message=[
        'hot_id.number'=>'热点id必需是数字',
        'hot_add_time'=>'热点添加时间必须是datetime类型',
       'hot_latlng.require' =>'热点经纬度必填',
        'hot_latlng.max'=>'热点经纬度的长度不能超过255',
        'region.require'=>'区域必填',
        'region.max'=>'区域长度不能超过20',
        'hot_status.in'=>'热点状态必须在-1,0,1,2之间',
        'add_user_id'=>'添加用户id必填',
        'create_time'=>'创建时间必填',
        'content'=>'内容必须是汉字和字母',
    ];

    protected $scene=[
        'add'=>['hot_latlng','region',],
        'edit'=>['hot_id'],
        'delete'=>['hot_id'],
        'HotId'=>['hot_id'],
    ];
}