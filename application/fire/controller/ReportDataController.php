<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/28
 * Time: 14:11
 */
namespace app\fire\controller;


use app\fire\logic\ReportDataLogic;
use app\fire\validate\BaseValidate;
use think\Controller;

class ReportDataController extends Controller {
    /**
     * 保存数据上报
     * @return string|\think\response\Json
     */
    function saveReportData(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=ReportDataLogic::saveReportData($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 编辑数据上报
     * @return string|\think\response\Json
     */
    function editReportData(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=ReportDataLogic::updateReportData($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 按id查询数据上报详情
     * @return string|\think\response\Json
     */
    function getReportDataById(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=ReportDataLogic::getReportDataById($data);
        return Common::reJson($dbRes);
    }

    /**
     * 按条件查询上报数据
     * @return string|\think\response\Json
     */
    function getListReportDataByCondition(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'report_region'=>'require',
        ]);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=ReportDataLogic::getListReportDataByCondition($data);
        return Common::reJson($dbRes);
    }

    /**
     * 删除上报数据
     * @return string|\think\response\Json
     */
    function removeReportData(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=ReportDataLogic::deleteReportData($data);
        return Common::reJson($dbRes);
    }


}