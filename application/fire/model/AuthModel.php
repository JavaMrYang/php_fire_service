<?php

namespace app\fire\model;

use app\fire\controller\Errors;
use think\Db;
use think\Exception;
use think\Model;

/**
 * 用户数据库操作
 * Created by xwpeng.
 */
class AuthModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tb_auth';
    //设置主键ID
    protected $pk = 'uid';
    //开启时间戳
    protected $autoWriteTimestamp = 'datetime';

}