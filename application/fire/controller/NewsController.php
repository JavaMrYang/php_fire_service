<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/7/3
 * Time: 15:09
 */
namespace app\fire\controller;

use app\fire\logic\NewsLogic;
use app\fire\validate\BaseValidate;
use think\App;
use think\Controller;

class NewsController extends Controller{
    private $news_logic;
    public function __construct(App $app = null,NewsLogic $news_logic)
    {
        parent::__construct($app);
        $this->news_logic=$news_logic;
    }


    function saveNews(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $result=$this->validate($data,'News.add');
        if($result!==true) return Common::reJson(Errors::validateError($result));
        $dbRes=$this->news_logic->addNews($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    function deleteNews(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $msg=['id.require'=>'id不能为空','id.number'=>'id必须为数字'];
        $validate=new BaseValidate([
            'id'=>'require|number',
        ],$msg);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=$this->news_logic->deleteNews($data['id'],$auth[1]);
        return Common::reJson($dbRes);
    }


}