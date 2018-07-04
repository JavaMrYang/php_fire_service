<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/26
 * Time: 11:30
 */
namespace app\fire\controller;

use app\fire\logic\FloorMonitorLogic;
use app\fire\validate\BaseValidate;
use think\Controller;

class FloorMonitorController extends Controller {
    /**
     * 保存地面监控点
     * @return string|\think\response\Json
     */
    function saveFloorMonitor(){
       $auth=Common::auth();
       if(!$auth[0]) return Common::reJson($auth);
       $data=Common::getPostJson();
       $dbRes=FloorMonitorLogic::addFloorMonitor($data,$auth[1]);
       return Common::reJson($dbRes);
    }

    /**
     * 编辑地面监控点
     * @return string|\think\response\Json
     */
    function editFloorMonitor(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=FloorMonitorLogic::editFloorMonitor($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 按条件查询地面监控热点
     * @return string|\think\response\Json
     */
    function getListFloorMonitorByCondition(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'floor_region'=>'require|number'
        ]);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=FloorMonitorLogic::getListFloorMonitorByCondition($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    function getFloorMonitorById(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=FloorMonitorLogic::getFloorMonitorById($data);
        return Common::reJson($dbRes);
    }
    /**
     * 删除地面监控点
     * @return string|\think\response\Json
     */
    function deleteFloorMonitor(){
        $auth=Common::auth(1);
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
           'id'=>'require|number'
        ]);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=FloorMonitorLogic::deleteFloorMonitor($data,$auth[1]);
        return Common::reJson($dbRes);
    }

}