<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/5
 * Time: 15:59
 */

namespace app\fire\validate;


class Auth extends BaseValidate
{
    protected $rule = [
        's_uid' => 'require|length:32',
        's_token' => 'require|length:32',
        's_client'=>'require|in:1,2,3,4,5'
    ];

    protected $message = [
        's_uid.require' => "auth uid require",
        's_uid.length' => "auth uid length 32",
        's_token.require' => "auth token require",
        's_token.length' => "auth token length 32",
    ];

    protected $scene = [
        'auth'  =>  ['s_uid','s_token','s_client']
    ];
}