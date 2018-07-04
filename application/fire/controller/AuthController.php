<?php
/**
 * Created by xwpeng.
 * Date: 2017/11/25
 * 用户接口
 */

namespace app\fire\controller;

use app\fire\controller\Common;
use app\fire\logic\AuthLogic;
use think\Controller;
use think\Cookie;
use think\Error;
use think\Exception;
use think\facade\Session;
use think\Validate;

class AuthController extends Controller
{

    function login()
    {
        $data = Common::getPostJson();
        $result = $this->validate($data, 'User.login');
        if ($result !== true) return Common::reJson(Errors::Error($result));
        if($data['client'] == 1){
            $mds=$data['mds'];
            if (md5($data['verity_code'])!=$mds) {
                return Common::reJson(Errors::VERIFY_CODE_ERROR);
            }
        }
        $auth = AuthLogic::auth($data);
        return Common::reJson($auth);
    }

    function loginOut()
    {
        $data = Common::getPostJson();
        $result = $this->validate($data, 'User.loginOut');
        $auth = Common::auth($data['uid']);
        if (!$auth[0]) return Common::reJson($auth);
        if ($result !== true) return Common::reJson(Errors::Error($result));
        $dbRes = AuthLogic::deleteAuth($auth[1]['s_uid'], $data['client']);
        Session::clear();
        return Common::reJson($dbRes);
    }
}

?>