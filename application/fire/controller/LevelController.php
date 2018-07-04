<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/26
 * Time: 9:09
 */
namespace app\fire\controller;

use app\fire\logic\LevelLogic;
use app\fire\validate\BaseValidate;
use think\App;
use think\Controller;

class LevelController extends Controller{
    protected $level_logic;
    function __construct(App $app = null,LevelLogic $levelLogic)
    {
        parent::__construct($app);
        $this->level_logic=$levelLogic;
    }

    function saveLevel(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $result=$this->validate($data,'Level.add');
        if($result!==true) return Common::reJson(Errors::validateError($result));
        $dbRes=$this->level_logic->addLevel($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    function findLevelByCondition(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'per_page'=>'require|number',
            'current_page'=>'require|number',
        ]);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=$this->level_logic->findLevelByCondition($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    function examineLevel(){
        $auth=Common::auth(1);
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        var_dump($data);
        $validate=new BaseValidate([
            'id'=>'require|array',
            'status'=>'require|in:-1,0,1',
        ],['id.array'=>'id必须是数组','status.in'=>'状态必须在-1,0,1之间']);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=$this->level_logic->examineLevel($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    function getLevelById(){
        $auth=Common::auth(1);
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'id'=>'require|number',
        ]);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=$this->level_logic->getLevelById($data['id'],$auth[1]);
        return Common::reJson($dbRes);
    }
}