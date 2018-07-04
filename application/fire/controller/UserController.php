<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/4
 * Time: 10:31
 */

namespace app\fire\controller;

use app\fire\logic\UserLogic;
use app\fire\validate\BaseValidate;
use think\Controller;
use think\Exception;
use think\File;

class UserController extends Controller
{

    function addUser(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = $this->validate($data, 'User.add');
        if ($result !== true) return Common::reJson(Errors::validateError($result));
        $file = request()->file('imageHead');
        $dbRes = UserLogic::saveUser($data,$file,$auth[1]);
        return Common::reJson($dbRes);
    }
    function queryUserMoldByTel()
    {
        $data = Common::getPostJson();
        $result = $this->validate($data, 'User.tel');
        if ($result !== true) return Errors::validateError($result);
        $dbRes = UserLogic::getUserMoldByTel($data['tel']);
        return Common::reJson($dbRes);
    }

    function signUserMold(){
        $data = Common::getPostJson();
        $result = $this->validate($data, 'User.addUserMold');
        if ($result !== true) return Errors::validateError($result);
        $dbRes = UserLogic::signUserMold($data);
        return Common::reJson($dbRes);
    }
    function signUser(){
        $data = Common::getPostJson();
        $result = $this->validate($data, 'User.register');
        if ($result !== true) return Errors::validateError($result);
        $dbRes = UserLogic::signUser($data);
        return $dbRes;
    }
    function editUserStatus(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = $this->validate($data, 'User.status');
        if ($result !== true) return Common::reJson(Errors::validateError($result));
        $dbRes = UserLogic::updateUserStatus($data,$auth[1]);
        return Common::reJson($dbRes);
    }
    function editUserExamine(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = $this->validate($data, 'User.examine');
        if ($result !== true) return Common::reJson(Errors::validateError($result));
        $dbRes = UserLogic::updateUserExamine($data,$auth[1]);
        return Common::reJson($dbRes);
    }
    function editUser(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = $this->validate($data, 'User.edit');
        if ($result !== true) return Common::reJson(Errors::validateError($result));
        $file = request()->file('imageHead');
        $dbRes = UserLogic::updateUserJudge($data,$file,$auth[1]);
        return Common::reJson($dbRes);
    }
    function getUserByNameOrTel(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $validate = new BaseValidate([
            'name' => 'max:20',
            'mold_type' => 'in:1,2,3,4,5',
            'region'=>'region'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes = UserLogic::queryUserByNameOrTel($data,$auth[1]);
        return Common::reJson($dbRes);
    }
    function getUserList(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'examine' => 'require|in:-1,0,1',
            'name' => 'max:20',
            'tel' => 'tel',
            'region' => 'require|region'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes = UserLogic::listUser($data,$auth[1]['s_region']);
        return Common::reJson($dbRes);
    }
    function getUserByUid(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = $this->validate($data, 'User.query');
        if ($result !== true) return Common::reJson(Errors::validateError($result));
        $dbRes = UserLogic::queryUser($data);
        return Common::reJson($dbRes);
    }

}