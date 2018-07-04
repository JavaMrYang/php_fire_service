<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/12
 * Time: 9:15
 */

namespace app\fire\controller;


use app\fire\logic\FireAssessLogic;
use app\fire\validate\BaseValidate;
use think\Controller;

class FireAssessController extends Controller
{
    function addFireAssess(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireAssessLogic::saveFireAssess($data,$auth[1]);
        return Common::reJson($result);
    }

    function editFireAssess(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireAssessLogic::updateFireAssess($data,$auth[1]);
        return Common::reJson($result);
    }

    function getFireAssessInfo(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireAssessLogic::queryFireAssessInfo($data['id']);
        return Common::reJson($result);
    }
}