<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/7/4
 * Time: 9:57
 */
namespace app\fire\validate;

use think\Validate;

class News extends Validate {
    protected $rule=[
        'id'=>'require|number',
        'region'=>'require|number|max:20',
        'title'=>'require|max:255',
        'news_type'=>'require|in:0,1,2,3',
        'content'=>'require|max:255',
    ];

    protected $message=[
        'id.require'=>'id不能为空',
        'id.number'=>'id必须要为数字',
        'region.require'=>'区域编号不能为空',
        'region.number'=>'区域编号必须为数字',
        'region.max'=>'区域编号长度不能超过20',
        'title.require'=>'标题不能为空',
        'title.max'=>'标题最大长度不能超过255',
        'news_type.require'=>'新闻类型不能为空',
        'news_type.in'=>'新闻类型必须在0,1,2,3之间',
        'content.require'=>'新闻内容必填',
        'content.max'=>'新闻内容长度不能超过255',
    ];

    protected $scene=[
        'add'=>['region','title','news_type','content'],
        'edit'=>['id','region','title','news_type','content'],
    ];
}