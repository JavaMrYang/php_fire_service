<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/24
 * Time: 11:15
 */
namespace app\fire\controller;

use app\fire\logic\HotLogic;
use app\fire\validate\BaseValidate;
use think\Controller;
use think\facade\Env;

class HotController extends Controller{
    /**
     * 添加卫星热点
     * @return string|\think\response\Json
     */
    function addHot(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $result=$this->validate($data,'Hot.add');
        if(true !== $result) return Common::reJson(Errors::validateError($result));
        $dbRes=HotLogic::saveHot($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    function getHotById(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'id'=>'require|number',
        ]);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=HotLogic::getHotByHotId($data['id']);
        return Common::reJson($dbRes);
    }
    /**
     * 按热点id查询任务详情
     * @return string|\think\response\Json
     */
    function getTaskByHotId(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=HotLogic::getTaskDetailByHotId($data);
        return Common::reJson($dbRes);
    }

    /**
     * 按条件查询热点
     * @return string|\think\response\Json
     */
    function  getHotByCondition(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'region'=>'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=HotLogic::getListHotByCondition($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 导入卫星热点
     * @return string|\think\response\Json
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    function importHot(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        //获取上传文件（此处应判断文件是否合法，可自行添加判断条件）
        $hotfile= input('file.hotfile');
//生成路径和文件（路径可自定义，TP5.0版本可直接使用常量ROOT_PATH和DS）
        $imgpath = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'hot';
        $result = $hotfile->move($imgpath, 'excel.xls',true,false);
//读取并返回数据
        $data =Common::read_excel($result->getPathName());
        $dbRes=HotLogic::saveHotExcel($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 删除热点任务
     * @return string|\think\response\Json
     */
    static function deleteHot(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'hot_id' => 'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=HotLogic::deleteHot($data['hot_id'],$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 统计卫星热点
     * @return string|\think\response\Json
     */
    static function countHotUpload(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=HotLogic::countHotData($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 统计今日卫星热点
     * @return string|\think\response\Json
     */
    static function countTodayHot(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $dbRes=HotLogic::countTodayFireHot();
        return Common::reJson($dbRes);
    }
}