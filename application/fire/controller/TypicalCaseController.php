<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/11
 * Time: 14:04
 */

namespace app\fire\controller;


use app\fire\logic\TypicalCaseLogic;
use app\fire\validate\BaseValidate;
use think\Controller;

class TypicalCaseController extends Controller
{
    function addTypicalCase(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = TypicalCaseLogic::saveTypicalCase($data,$auth[1]);
        return Common::reJson($result);
    }

    function getTypicalCaseList(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'region' => 'region',
            'begin_time'=>'dateFormat:Y-m-d',
            'end_time'=>'dateFormat:Y-m-d'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = TypicalCaseLogic::queryTypicalCaseList($data,$auth[1]);
        return Common::reJson($result);
    }

    function delTypicalCase(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = TypicalCaseLogic::deleteTypicalCase($data['id'],$auth[1]);
        return Common::reJson($result);
    }

    function getTypicalCaseInfo(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = TypicalCaseLogic::queryTypicalCaseInfo($data['id']);
        return Common::reJson($result);
    }
}