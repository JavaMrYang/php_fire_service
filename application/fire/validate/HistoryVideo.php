<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/25
 * Time: 11:22
 */
namespace app\fire\validate;

use think\Validate;

class HistoryVideo extends Validate{
    protected $rule=[
        'id'=>'require|number',
        'region'=>'require|number',
        'location'=>'require|max:255',
        'video_type'=>'require|max:2',
        'video_name'=>'require|max:255',
        'video_path'=>'require|max:255',
    ];

    protected $message=[
        'id.require'=>'历史视频id不能为空',
        'id.number'=>'id必须是数字',
        'region.require'=>'区域必填',
        'region.number'=>'区域必须为数字',
        'location.require'=>'位置必填',
        'location.max'=>'位置不能超过255的长度',
        'video_type.require'=>'视频类型必填',
        'video_type.max'=>'视频类型最大长度为2',
        'video_name.require'=>'视频名称必填',
        'video_name.max'=>'视频名称不能超过255的长度',
        'video_path.require'=>'视频路径不能为空',
        'video_path.max'=>'视频路径不能超过255',
    ];

    protected $scene=[
        'add'=>['region','location','video_type','video_name','video_path'],
        'edit'=>['id','region','location','video_type','video_name','video_path'],
    ];
}