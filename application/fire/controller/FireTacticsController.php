<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/4
 * Time: 15:50
 */

namespace app\fire\controller;


use app\fire\logic\FireTacticsLogic;
use app\fire\validate\BaseValidate;
use think\Controller;

class FireTacticsController extends Controller
{
    function addFireTactics(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireTacticsLogic::saveFireTactics($data,$auth[1]);
        return Common::reJson($result);
    }

    function delFireTactics(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireTacticsLogic::deleteFireTactics($data['id'],$auth[1]);
        return Common::reJson($result);
    }

    function editFireTactics(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireTacticsLogic::updateFireTactics($data,$auth[1]);
        return Common::reJson($result);
    }

    function getFireTacticsList(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $validate=new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'region'=>'number',
            'name'=>'max:20',
            'begin_time'=>'dateFormat:Y-m-d',
            'end_time'=>'dateFormat:Y-m-d'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = FireTacticsLogic::queryFireTacticsList($data,$auth[1]);
        return Common::reJson($result);
    }

    function getFireTacticsInfo(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireTacticsLogic::queryFireTacticsInfo($data['id'],$auth[1]);
        return Common::reJson($result);
    }
}