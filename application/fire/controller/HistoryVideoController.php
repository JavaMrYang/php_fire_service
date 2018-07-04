<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/8
 * Time: 15:24
 */
namespace app\fire\controller;

use app\fire\logic\HistoryVideoLogic;
use app\fire\validate\BaseValidate;
use think\Controller;

class HistoryVideoController extends Controller{
    /**
     * 历史视频保存
     * @return string|\think\response\Json
     */
    function saveVideo(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $result=$this->validate($data,'HistoryVideo.add');
        if($result!==true) return Common::reJson(Errors::validateError($result));
        $dbRes=HistoryVideoLogic::addHistoryVideo($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 历史视频编辑
     * @return array|string|\think\response\Json
     */
    function editVideo(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
          'id'=>'require|number',
        ]);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result=$this->validate($data,'HistoryVideo.edit');
        if($result!==true) return Common::reJson(Errors::validateError($result));
        $dbRes=HistoryVideoLogic::editHistoryVideo($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 历史视频详情
     * @return array|string|\think\response\Json
     */
    function getVideoById(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'id'=>'require|number',
        ]);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=HistoryVideoLogic::getHistoryVideoById($data['id']);
        return Common::reJson($dbRes);
    }

    /**
     * 历史视频按条件查询
     * @return array|string|\think\response\Json
     */
    function getVideoByCondition(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'per_page' => 'require|number|max:50|min:1',
            'current_page' => 'require|number|min:1',
        ]);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=HistoryVideoLogic::getHistoryVideoByCondition($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 删除历史视频
     * @return array|string|\think\response\Json
     */
    function deleteVideo(){
        $auth=Common::auth(1);
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'id'=>'require|number',
        ]);
        if(!$validate->check($data)) return Errors::Error($validate->getError());
        $dbRes=HistoryVideoLogic::deleteVideo($data,$auth[1]);
        return Common::reJson($dbRes);
    }
}