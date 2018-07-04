<?php
/**
 * Created by PhpStorm.
 * User: 呵.谢勇
 * Date: 2018/6/12
 * Time: 15:50
 */

namespace app\fire\controller;


use app\fire\validate\BaseValidate;
use think\Controller;
use app\fire\model\TbFireWatchtowerModel;
use think\Db;

class FireWatchtowerController extends Controller
{
    /** 
    *瞭望塔 --新增
    */ 
    function addFireWatchtower(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = $this->validate($data,'FireWatchtower.add');
        if($result !== true) return  Common::reJson(Errors::validateError($result));

        $auth=$auth[1];
        if (!Common::authRegion($data['region'],$auth['s_region']))
                 return Errors::REGION_PREMISSION_REJECTED;
        //$auth['s_uid']=1;
        $time=date('Y-m-d H:i:s');
        $result=new TbFireWatchtowerModel;
        $data['create_time']=$time;
        $data['create_id']=$auth['s_uid'];
        $data['status']=1;
        $data['input_time']=$time;
        try {
            Db::startTrans();
            $result->allowField(true)->save($data);
           // $result->allowField(true)->save($data);
            if ($result){
                if (Common::isWhere($data,'team_image') && $data['team_image'][0] != '') {
                    $num = 0;
                    foreach ($data['team_image'] as $value) {
                        if ((Db::table('tb_file_image')->where('id', $value)->update(['source' => '7', 'source_id' => $result->id,'status'=>'1'])) > 0)
                            $num++;
                    }
                    if ($num == count($data['team_image'])) {
                        Db::commit();
                        return Common::reJson([true,$result->id]);
                    }
                }else{
                    Db::commit();
                    return Common::reJson([true,$result->id]);
                }
            }
            Db::rollback();
            return Common::reJson([false, '添加失败']);
        } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return Common::reJson(Errors::Error($e->getMessage()));
        }
        // $result=$TbFireWatchtowerModel->allowField(true)->save($data);
        // if(empty($result)){
        //     return Common::reJson([false,'error']);
        // }
        // return Common::reJson([true,'success']);
    }


     /**
     *瞭望塔--列表查询
     */

    function getFireWatchtowerList(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'region'=>'region'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $data['current_page']= !empty($data['current_page']) ? $data['current_page']:1;    //起始页
        $data['per_page']= !empty($data['per_page'])?$data['per_page']: 20; //每页多少条
        $data['status']=1;
        $result = TbFireWatchtowerModel::queryFireWatchtowerList($data,$auth[1]);
        return Common::reJson(Common::removeEmpty($result));
    }

    /**
     * 瞭望塔--详情信息
     */
    function getFireWatchtowerInfo(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);

        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = TbFireWatchtowerModel::queryFireWatchtowerById($data);
        $path = Db::table('tb_file_image')->where('source','7')->where('source_id',$data['id'])->field('id,path')->select();
        $result[1]['image_path']=$path;
        if(empty($result)) return Common::reJson(Errors::DATA_NOT_FIND);
        return Common::reJson(Common::removeEmpty($result));
    }

     /**
     * 瞭望塔--软删除 14:53
     * @return string|\think\response\Json
     */
    function delFireWatchtower(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $TbFireWatchtowerModel = TbFireWatchtowerModel::get($data['id']);
        if(empty($TbFireWatchtowerModel)){
            return Common::reJson([false,'操作失败，未找到该条记录']);
        }
        if($TbFireWatchtowerModel['status']==2){
            return Common::reJson([false,'请勿重复执行删除操作']);
        }
        if($TbFireWatchtowerModel['create_id']!=$auth[1]['s_uid']){
            return Common::reJson([false,'请不要操作别人的数据']);
        }
        $TbFireWatchtowerModel->delete_time=date('Y-m-d H:i:s');
        $TbFireWatchtowerModel->delete_id=$auth[1]['s_uid'];
        $TbFireWatchtowerModel->status=2;
        $rs=$TbFireWatchtowerModel->save();
        if(empty($rs)){
            return Common::reJson([false,'删除操作失败']);
        }
        return Common::reJson([true,'操作成功']);
    }

    /**
     * 瞭望塔--修改
     * @return string|\think\response\Json
     */
    function editFireWatchtower(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));

        $result = $this->validate($data,'FireWatchtower.edit');
        if($result !== true) return  Common::reJson(Errors::validateError($result));

        $TbFireWatchtowerModel = TbFireWatchtowerModel::where(['id'=>$data['id'],'status'=>1])->find();
        if(empty($TbFireWatchtowerModel)){
            return Common::reJson([false,'操作失败，未找到该条记录或该记录已删除']);
        }
        if($TbFireWatchtowerModel['create_id']!=$auth[1]['s_uid']){
            return Common::reJson([false,'请不要操作别人的数据']);
        }
        $data['update_time']=date('Y-m-d H:i:s');
        $data['update_id']=$auth[1]['s_uid'];
        $data['status']=1;
        //执行修改
        try{
                Db::startTrans();
                $result=$TbFireWatchtowerModel->allowField(true)->save($data);
                if ($result > 0){
                    $fireControlTeamImageAndPath = Db::table('tb_file_image')
                        ->where('source','7')
                        ->where('source_id',$data['id'])
                        ->column('id,path');
                    $fireControlTeamImage = array_keys($fireControlTeamImageAndPath);
                    //比较两次上传的文件名是否一致
                    $del_array = array_diff($fireControlTeamImage,$data['team_image']);
                    //删除舍弃的文件
                    if(!empty($del_array)){
                        foreach ($del_array as $value){
                            $num = Db::table('tb_file_image')->where('id',$value)->update(['source'=>null,'source_id'=>'','status'=>'0']);
                           unlink(FILE_PATH.DS.$fireControlTeamImageAndPath[$value]);
                           Db::table('tb_file_image')->where('id',$value)->delete();
                        }
                    }
                    $add_array = array_diff($data['team_image'],$fireControlTeamImage);
                    if(!empty($add_array)){
                        foreach ($add_array as $value) {
                            $num = Db::table('tb_file_image')->where('id',$value)->update(['source'=>'7','source_id'=>$data['id'],'status'=>'1']);
                        }
                    }
                    Db::commit();
                    return Common::reJson([true,$result]);
                }else{
                    Db::rollback();
                    return Common::reJson([false,'修改失败']);
                }
            }catch (Exception $exception){
                Db::startTrans();
                return Common::reJson([false,'修改失败']);
            }
       

    }

    /**
     * 瞭望塔--统计
     * @return string|\think\response\Json
     */
    function statisticsFireWatchtower(){

        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        return Common::reJson([true,TbFireWatchtowerModel::statisticsFireWatchtower()]);
    }

}