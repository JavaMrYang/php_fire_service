<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/1
 * Time: 8:47
 */

namespace app\fire\controller;


use app\fire\logic\forest_ranger\ReportDataLogic;
use app\fire\validate\BaseValidate;
use think\Controller;

/**
 * 护林员数据上报
 * Class ReportDataController
 * @package app\fire\controller
 */
class HlyReportController extends Controller
{
    /**
     * 上报数据
     * @return string|\think\response\Json
     */
    function addReportData(){
        $auth = Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = ReportDataLogic::saveReportData($data,$auth[1]);
        return Common::reJson($result);
    }


    /**
     * 软删除数据
     * @return string|\think\response\Json
     */
    function delReportData(){
        $auth = Common::auth(1);
        if(!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();

        $result = ReportDataLogic::deleteReportData($data,$auth);
        return Common::reJson($result);
    }

    /**
     * 修改上报数据
     * @return string|\think\response\Json
     */
    function editReportData(){
        $auth = Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();

        $result = ReportDataLogic::updateReportData($data,$auth);
        return Common::reJson($result);
    }

    /**
     * 上报数据列表
     * @return string|\think\response\Json
     */
    function getReportDataList(){
        $auth = Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $validate = new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
            'region'=>'require|region',
            'report_type'=>'in:1,2,3,4,5',
            'begin_time'=>'dateFormat:Y-m-d',
            'end_time'=>'dateFormat:Y-m-d'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = ReportDataLogic::queryReportDataList($data,$auth);
        return Common::reJson($result);
    }

    /**
     * 上报数据详情
     * @return string|\think\response\Json
     */
    function getReportDataInfo(){
        $auth = Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = ReportDataLogic::queryReportDataInfo($data);
        return Common::reJson($result);
    }
}