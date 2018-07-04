<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/30
 * Time: 15:47
 */
namespace app\fire\controller;

use app\fire\logic\OnWorkLogic;
use app\fire\model\hly\OnWorkModel;
use app\fire\validate\BaseValidate;
use think\Controller;

class OnWorkController extends Controller {
    /**
     * 护林员打卡
     * @return string|\think\response\Json
     */
    function saveOnWork(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=OnWorkLogic::saveOnWork($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 护林员打卡记录
     * @param $data
     * @return string|\think\response\Json
     */
    function getWorkListByCondition($data){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=OnWorkLogic::getWorkListByCondition($data);
        return Common::reJson($dbRes);
    }

    /**
     * 是否打卡
     * @return string|\think\response\Json
     */
    function isWork(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=OnWorkLogic::isWork($data);
        return Common::reJson($dbRes);
    }

    /**
     * 护林员绩效列表
     * @return string|\think\response\Json
     */
    function  hlyAppraiseList(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'region'=>'require|number'
        ]);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=OnWorkLogic::hlyAppraiseList($data);
        return Common::reJson($dbRes);
    }



}