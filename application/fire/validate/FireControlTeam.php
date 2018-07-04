<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/14
 * Time: 17:21
 */

namespace app\fire\validate;


class FireControlTeam extends BaseValidate
{
    protected $rule = [
        'id' => 'require|int',
        'region' => 'require|max:20|region',
        'position' => 'require|length:255',
        'set_time' => 'dateFormat:Y-m-d',
        'admin' => 'require|length:20',
        'admin_tel' => 'tel',
        'team_name' => 'require|length:20',
        'team_num' => 'require|length:2',
        'team_nature' => 'require|in:1,2,3',
        'user_id' => 'length:32',
        'describe' => 'length:255'
    ];

    protected $message = [
        'region.region' => '区域必须在湖南省内',
        'region.require'  => '区域必填',
        'region.max'  => '区域最多20个字符',
        'position.require'        => '地理坐标必填',
        'set_time.dateFormat'        => '成立时间必填',
        'admin.require'        => '管理者必填',
        'admin.length'        => '管理者名称长度不能大于二十位',
        'admin_tel.require'        => '管理者电话规则错误',
        'team_name.require'        => '团队名称必填',
        'team_name.length'        => '团队名称不能大于二十位',
        'team_num.require'        => '团队人数必填',
        'team_num.length'        => '团队人数不能大于100人',
        'team_nature.require'        => '团队性质必填',
        'team_nature.in'        => '团队性质规则错误',
        'user_id.length' => '录入人规则错误',
        'describe.length' => '描述长度不能大于255位'
    ];

    protected $scene = [
        'add'  =>  ['region','position','set_time','admin','admin_tel','team_name','team_num','team_nature','describe'],
        'edit'  =>  ['id','region','position','set_time','admin','admin_tel','team_name','team_num','team_nature','describe'],
        'del'  =>  ['id'],
        'query'  =>  ['id']
    ];
}