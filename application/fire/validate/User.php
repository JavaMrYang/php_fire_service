<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/27
 * Time: 15:24
 */

namespace app\fire\validate;


class User extends BaseValidate
{
    protected $rule = [
        'password' => 'length:6,16|different:tel',
        'passwords' => 'length:6,16|different:tel|alphaDash',
        'region' => 'require|max:20|region',
        'name' => 'require|max:16',
        'status'=>"in:1,0",
        'examine'=>"in:-1,0,1",
        'mids|类型'=>"require|array",
        'rid|角色'=>"require|in:1,2,3",
        'mid|类型'=>"require|in:1,2,3,4,5",
        'uid'=>"require|length:32",
        'uids|类型'=>"require|array",
        'client'=>'require|in:1,2,3,4,5',
        'tel'=>'require|tel'
    ];

    protected $message = [
        'password.length' => '密码长度需6到16',
        'passwords.length' => '密码长度需6到16',
        'password.different:tel'     => '密码不能与账号相同',
        'passwords.different:tel'     => '密码不能与账号相同',
        'passwords.alphaDash'   => '密码只能包含字母，下划线，数字',
        'region.require'  => '区域必填',
        'region.max'  => '区域最多20个字符',
        'name.require'        => '名字必填',
        'name.max'        => '名字最多16个字符',
        'tel.require'        => '手机号码必填',
    ];


    protected function tel($value)
    {
        return preg_match_all('/^1[34578]\d{9}$/', $value) ? true : '手机号码格式错误';
    }

    protected $scene = [
        'add'  =>  ['password','region','name','rid','mids','tel'],
        'status'  =>  ['uids','status','mid'],
        'edit'  =>  ['password','name','mids','uid'],
        'query'  =>  ['uid'],
        'tel' => ['tel'],
        'addUserMold' => ['tel','password'],
        'examine' => ['uids','examine','mid'],
        'register_judge' => ['tel','password'],
        'register' => ['password','region','name','mid','tel'],
        'login'=>['tel','password','verity_code','client'],
        'loginOut'=>['client'],

    ];

}