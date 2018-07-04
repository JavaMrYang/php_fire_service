<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/14
 * Time: 15:33
 */

namespace app\fire\controller;


use app\fire\logic\FireControlTeamLogic;
use app\fire\validate\BaseValidate;
use think\Controller;

class FireControlTeamController extends Controller
{
    /**
     * 添加消防队伍
     * @return string|\think\response\Json
     */
    function addFireControlTeam(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireControlTeamLogic::saveFireControlTeam($data,$auth[1]);
        return Common::reJson($result);
    }

    /**
     * 删除消防队伍
     * @return string|\think\response\Json
     */
    function delFireControlTeam(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireControlTeamLogic::deleteFireControlTeam($data['id'],$auth[1]);
        return Common::reJson($result);
    }

    /**
     * 修改消防队伍
     * @return string|\think\response\Json
     */
    function editFireControlTeam(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireControlTeamLogic::updateFireControlTeam($data,$auth[1]);
        return Common::reJson($result);
    }

    /**
     * 消防队伍列表
     * @return string|\think\response\Json
     */
    function getFireControlTeamList(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'region'=>'region',
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = FireControlTeamLogic::queryFireControlTeamList($data,$auth[1]);
        return Common::reJson($result);
    }

    /**
     * 修改消防队伍
     * @return string|\think\response\Json
     */
    function getFireControlTeamInfo(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireControlTeamLogic::queryFireControlTeamInfo($data['id']);
        return Common::reJson($result);
    }

    function getFireControlTeamCount(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = FireControlTeamLogic::queryFireControlTeamCount($data,$auth[1]);
        return Common::reJson($result);
    }
}