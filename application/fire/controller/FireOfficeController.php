<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/11
 * Time: 10:12
 */
namespace app\fire\controller;

use app\fire\logic\FireOfficeLogic;
use think\Controller;

class FireOfficeController extends Controller{
    /**
     * 保存防火办公室
     * @return string|\think\response\Json
     */
    function saveOffice(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=$this->validate($data,'FireOffice.add');
        if($validate!==true) return Common::reJson(Errors::validateError($validate));
        $dbRes=FireOfficeLogic::addFireOffice($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 编辑防火办公室
     * @return string|\think\response\Json
     */
    function editOffice(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $result=$this->validate($data,'FireOffice.edit');
        if($result!==true) return Common::reJson(Errors::validateError($result));
        $dbRes=FireOfficeLogic::editFireOffice($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 查看防火办公室详情
     * @return string|\think\response\Json
     */
    function getOfficeById(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=FireOfficeLogic::getFireOfficeById($data['id']);
        return Common::reJson($dbRes);
    }

    /**
     * 查询防火办公室列表
     * @return string|\think\response\Json
     */
    function getOfficeByCondition(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=FireOfficeLogic::getFireOfficeByCondition($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 删除防火办公室
     * @return string|\think\response\Json
     */
    function deleteOffice(){
        $auth=Common::auth(1);
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $result=$this->validate($data,'FireOffice.delete');
        if($result!==true) return Common::reJson(Errors::validateError($result));
        $dbRes=FireOfficeLogic::deleteFireOffice($data,$auth[1]);
        return Common::reJson($dbRes);
    }
}