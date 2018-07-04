<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/22
 * Time: 10:52
 */
namespace app\fire\controller;

use app\fire\logic\TaskLogic;
use app\fire\validate\BaseValidate;
use think\App;
use think\Controller;
use think\Exception;

class TaskController extends Controller {
    private $task_logic;
    public function __construct(App $app = null,TaskLogic $taskLogic)
    {
        parent::__construct($app);
        $this->task_logic=$taskLogic;
    }

    /**
     * 添加任务
     * @return string|\think\response\Json
     */
    function addTask(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $task=Common::getPostJson();
        //$image=request()->file('task_images'); //获取上传图片文件
        $result=$this->validate($task,'Task.add');
        if($result!==true) return Common::reJson(Errors::validateError($result));
        $dbRes=TaskLogic::saveTask($task,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 接收任务
     * @return array
     */
    function acceptTask(){
        try {
            $auth=Common::auth();
            if(!$auth[0]) return Common::reJson($auth);
            $data = Common::getPostJson();
            $dbRes=TaskLogic::acceptTask($data,$auth[1]);
            return Common::reJson($dbRes);
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }


    /**
     * 按条件查询用户
     * @return string|\think\response\Json
     */
    function getTaskByCondition(){
        try{
            $auth=Common::auth();
            if(!$auth[0]) return Common::reJson($auth);
            $data=Common::getPostJson();
            $validate=new BaseValidate([
                'per_page' => 'require|number|max:50|min:1',
                'current_page' => 'require|number|min:1',
                'task_region'=>'require|number'
            ]);
            if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
            $dbRes=TaskLogic::getListTaskByCondition($data,$auth[1]);
            return Common::reJson($dbRes);
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

    /**
     * 按任务id查询任务详情
     * @return array|string|\think\response\Json
     */
    function getTaskById(){
        try{
            $auth=Common::auth();
            if(!$auth[0]) return Common::reJson($auth);
            $data=Common::getPostJson();
            $dbRes=TaskLogic::getTaskById($data);
            return Common::reJson($dbRes);
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

    /**
     * 反馈任务
     * @return string|\think\response\Json
     */
    function feedBackTask(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $file=request()->file('task_result_image');
        $data=Common::getPostJson();
        $dbRes=TaskLogic::feedBackTask($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 取消任务
     * @return string|\think\response\Json
     */
    function deleteTask(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'task_id'=>'require|number'
        ],['task_id.number'=>'任务id必须为数字']);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=TaskLogic::cancelTask($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 拒绝任务
     * @return string|\think\response\Json
     */
    function refuseTask(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $validate=new BaseValidate([
            'task_id'=>'require|number'
        ],['task_id.number'=>'任务id必须为数字']);
        if(!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $dbRes=$this->task_logic->refuseTask($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 统计任务
     * @return string|\think\response\Json
     */
    function countTaskData(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=TaskLogic::countTaskByCondition($data,$auth[1]);
        return Common::reJson($dbRes);
    }

    /**
     * 统计任务数量
     * @return string|\think\response\Json
     */
    function countTaskAmount(){
        $auth=Common::auth();
        if(!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=TaskLogic::countTaskTotalByCondition($data,$auth[1]);
        return Common::reJson($dbRes);
    }
}