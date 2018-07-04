<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/16
 * Time: 11:16
 */

namespace app\fire\controller;
use app\fire\logic\FireLogic;
use app\fire\validate\BaseValidate;
use think\Controller;
use Workerman\Worker;

class FireController extends controller
{
    /**
     * 新增火情上报
     * @return string|\think\response\Json
     */
    function addFireUpload(){
        /*$auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);*/
        $data = $_POST;
        $dbRes = FireLogic::saveFireUpload($data);
        return Common::reJson($dbRes);
    }


    /**
     * 火情查询列表3
     * @return string|\think\response\Json
     * @throws \think\exception\DbException
     */
    function getFireUploadList(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'region'=>'require|region',
            'status' => 'require|in:1,2,3',
            'fire_level'=>'in:1,2,3,4',
            'fire_type'=>'in:1,2,3',
            'begin_time'=>'dateFormat:Y-m-d',
            'end_time'=>'dateFormat:Y-m-d'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = FireLogic::queryFireUploadList($data);
        return Common::reJson($result);
    }

    /**
     * 获得火情上报信息
     * @return string|\think\response\Json
     */
    function getFireUploadInfo(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = FireLogic::queryFireUploadInfo($data['id']);
        return Common::reJson($result);
    }

    /**
     * 软删除火情
     * @return string|\think\response\Json
     */
    function delFire(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = FireLogic::deleteFire($data['id']);
        return Common::reJson($result);
    }

    /**
     * 修改火情上报
     * @return string|\think\response\Json
     */
    function editFireUpload(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = $_POST;
        $result = FireLogic::updateFireUpload($data);
        return Common::reJson($result);
    }

    /**
     * 获得火情热力图
     * @return string|\think\response\Json
     */
    function getFireHeatMap(){
        $auth = Common::auth();
        if (!$auth[0]) return Helper::reJson($auth);
        $result = FireLogic::queryFireHeatMap($auth['region']);
        return Common::reJson($result);
    }

    /**
     * 新增火情跟踪
     * @return string|\think\response\Json
     */
    function addFireTrace(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = $_POST;
        $dbRes = FireLogic::saveFireUpload($data);
        return Common::reJson($dbRes);
    }


    /**
     * 获得火情上报信息
     * @return string|\think\response\Json
     */
    function getFireTraceInfo(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = FireLogic::queryFireUploadInfo($data['id']);
        return Common::reJson($result);
    }

    /**
     * 修改火情跟踪信息
     * @return string|\think\response\Json
     */
    function editFireTrace(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = $_POST;
        $result = FireLogic::updateFireUpload($data);
        return Common::reJson($result);
    }



}