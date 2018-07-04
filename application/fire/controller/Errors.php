<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/27
 * Time: 10:52
 */

namespace app\fire\controller;


class Errors
{
    const FIRE_APP_KEY='12abb23efaa0c600412e4ced';
    const FIRE_APP_MASTER_SECRET='75067e9537d81b125c75711f';
    const PARAMS_ERROR = "params_error";
    const DB_ERROR = "db_error";
    const FILE_ROOT_PATH = ROOT_PATH . DS . 'public' . DS . 'uploads';
    const USER_ADD = "tel already exists";
    const SAVE_FILE_ERROR = "save file error";
    const IS_NOT_I = "u are not task founder";
    const TASK_STATUS_ERROR_THREE = "task status is not 2";
    const VERSION_CODE_IS_NULL = "version_code is null";
    const NEW_VERSION_NOT_FIND = "new version not find";

    const ATTACH_NOT_FIND = [false, "找不到附件"];
    const AUT_LOGIN = [false, "身份验证删除失败"];
    const FILE_SAVE_ERROR = [false, "文件保存失败"];
    const AUTH_FAILED = [false, "身份认证失败,请重新登录"];
    const AUTH_EXPIRED = [false, "身份认证过期，请重新登录"];
    const AUTH_PREMISSION_EMPTY = [false, "没有权限"];
    const REGION_PREMISSION_REJECTED = [false, "无法操作其他区域的数据"];
    const AUTH_PREMISSION_REJECTED = [false, "权限拒绝"];
    const AUTH_PREMISSION_LEVEL = [false, "管理员等级必须大于县级"];
    const DELETE_ERROR = [false, "删除错误"];
    const LOGIN_TEL_ERROR = [false, "您输入的手机号不存在"];
    const LOGIN_ERROR = [false, "您输入的手机号或密码错误"];
    const DATA_NOT_FIND = [false, "没有数据"];
    const LOGIN_STATUS = [false, "用户已停用"];
    const EXAMINE_STATUS = [false, "账号审核中，请联系管理员"];
    const VERIFY_CODE_ERROR = [false, "验证码错误"];
    const ADD_ERROR = [false, "添加错误"];
    const UPDATE_ERROR=[false,'修改错误'];
    const IMAGE_COUNT_ERROR = [false, "图片数量错误"];
    const IMAGE_NOT_FIND = [false, "没有图片"];
    const FILE_TYPE_ERROR = [false, "文件格式错误"];
    const IMAGE_FILE_SIZE_ERROR = [false, "大小不能超2M"];
    const IMAGES_INSERT_ERROR = [false, "图片添加失败"];
    const LIMITED_AUTHORITY = [false, "你不是管理,也不是本人"];
    const HOT_ALREADY_PUBLISH=[false,'热点id已经发布过'];
    const ASSIGN_ERROR=[false,'你不是指派人，你不能接收'];

    static function Error($toC, $toU = '程序出错,', $isOk = false)
    {
        return [$isOk, $toU. $toC];
    }

    static function validateError($toC)
    {
        return [false,$toC];
    }
}

?>