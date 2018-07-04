<?php
/**
 * Created by PhpStorm.
 * User: 呵.谢勇
 * Date: 2018/6/14
 * Time: 15:50
 */

namespace app\fire\controller;


use app\fire\validate\BaseValidate;
use think\Controller;
use think\Db;
use app\fire\model\TbFireMaterialReserveModel;

class FireMaterialReserveController extends Controller
{
    /**  
    *物资储备库 --新增 
    *路径:fire/fire_material_reserve/addFireMaterialReserve
    *名称:物资储备库 --新增 
    *描述:物资储备库 --新增 
    */ 
    function addFireMaterialReserve(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = $this->validate($data,'FireMaterialReserve.add');
        if($result !== true) return  Common::reJson(Errors::validateError($result));

        $auth=$auth[1];
        if (!Common::authRegion($data['region'],$auth['s_region']))
                 return Errors::REGION_PREMISSION_REJECTED;
        //$auth['s_uid']=1;
        $material_total=$data['gps']+
                        $data['fire_shovel']+
                        $data['wind_water_fire_extinguisher']+
                        $data['chain_saw']+
                        $data['dynamo']+
                        $data['high_pressure_mist_extinguisher']+
                        $data['water_pump']+
                        $data['dynamo_exploder']+
                        $data['broadax']+
                        $data['communication_van']+
                        $data['hacking_knife']+
                        $data['vehicular_locating_set']+
                        $data['clothing']+
                        $data['troop_crawler']+
                        $data['tent']+
                        $data['fire_fighting_water_wheel']+
                        $data['sleeping_bag']+
                        $data['fire_hoses']+
                        $data['rests']+
                        $data['two_three_tool']+
                        $data['uav']+
                        $data['pneumatic_extinguisher']+
                        $data['brush_cutter']+
                        $data['residual_fire_detector']+
                        $data['anemograph']+
                        $data['handheld_radio_equipment'];
        $data['material_total']=$material_total;
        $result=new TbFireMaterialReserveModel;
        $time=date('Y-m-d H:i:s');
        $data['create_time']=$time;
        $data['create_id']=$auth['s_uid'];
        $data['input_time']=$time;
        $data['status']=1;
        try {
            Db::startTrans();
            $result->allowField(true)->save($data);
           // $result->allowField(true)->save($data);
            if ($result){
                if (Common::isWhere($data,'team_image') && $data['team_image'][0] != '') {
                    $num = 0;
                    foreach ($data['team_image'] as $value) {
                        if ((Db::table('tb_file_image')->where('id', $value)->update(['source' => '6', 'source_id' => $result->id,'status'=>'1'])) > 0)
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

       
        // if(empty($result)){
        //     return Common::reJson([false,'添加失败']);
        // }
        // return Common::reJson([true,'success']);
    }


    /**
    *物资储备库--列表查询
    *路径:fire/fire_material_reserve/getFireMaterialReserveList
    *名称:物资储备库 --列表查询
    *描述:物资储备库 --列表查询
    */
    function getFireMaterialReserveList(){
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
        $result = TbFireMaterialReserveModel::queryFireMaterialReserveList($data,$auth[1]);
        return Common::reJson(Common::removeEmpty($result));
    }

    /**
    * 物资储备库--详情信息
    *路径:fire/fire_material_reserve/getFireMaterialReserveInfo
    *名称:物资储备库 --详情信息
    *描述:物资储备库 --详情信息
    */
    function getFireMaterialReserveInfo(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);

        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = TbFireMaterialReserveModel::queryFireMaterialReserveById($data);
        $path = Db::table('tb_file_image')->where('source','6')->where('source_id',$data['id'])->field('id,path')->select();
        $result['image_path']=$path;
        if(empty($result)) return Common::reJson(Errors::DATA_NOT_FIND);
        //Common::removeEmpty($result);
        //return [true,$result];
        return Common::reJson([true,Common::removeEmpty($result)]);
    }

     /**
     * 物资储备库--软删除 14:53
    *路径:fire/fire_material_reserve/delFireMaterialReserve
    *名称:物资储备库 --软删除
    *描述:物资储备库 --软删除
     * @return string|\think\response\Json
     */
    function delFireMaterialReserve(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        //$TbFireMaterialReserveModel=new TbFireMaterialReserveModel;
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        
        $TbFireMaterialReserveModel = TbFireMaterialReserveModel::get($data['id']);
        if(empty($TbFireMaterialReserveModel)){
            return Common::reJson([false,'操作失败，未找到该条记录']);
        }
        if($TbFireMaterialReserveModel['status']==2){
            return Common::reJson([false,'请勿重复执行删除操作']);
        }
        if($TbFireMaterialReserveModel['create_id']!=$auth[1]['s_uid']){
            return Common::reJson([false,'请不要操作别人的数据']);
        }
        $TbFireMaterialReserveModel->delete_time=date('Y-m-d H:i:s');
        $TbFireMaterialReserveModel->delete_id=$auth[1]['s_uid'];
        $TbFireMaterialReserveModel->status=2;
        $rs=$TbFireMaterialReserveModel->save();
        if(empty($rs)){
            return Common::reJson([false,'删除操作失败']);
        }
        return Common::reJson([true,'操作成功']);
    }

    /**
    * 物资储备库--修改
    *路径:fire/fire_material_reserve/editFireMaterialReserve
    *名称:物资储备库 --修改
    *描述:物资储备库 --修改
     * @return string|\think\response\Json
     */
    function editFireMaterialReserve(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));

        $result = $this->validate($data,'FireMaterialReserve.add');
        if($result !== true) return  Common::reJson(Errors::validateError($result));

        $material_total=$data['gps']+
                        $data['fire_shovel']+
                        $data['wind_water_fire_extinguisher']+
                        $data['chain_saw']+
                        $data['dynamo']+
                        $data['high_pressure_mist_extinguisher']+
                        $data['water_pump']+
                        $data['dynamo_exploder']+
                        $data['broadax']+
                        $data['communication_van']+
                        $data['hacking_knife']+
                        $data['vehicular_locating_set']+
                        $data['clothing']+
                        $data['troop_crawler']+
                        $data['tent']+
                        $data['fire_fighting_water_wheel']+
                        $data['sleeping_bag']+
                        $data['fire_hoses']+
                        $data['rests']+
                        $data['two_three_tool']+
                        $data['uav']+
                        $data['pneumatic_extinguisher']+
                        $data['brush_cutter']+
                        $data['residual_fire_detector']+
                        $data['anemograph']+
                        $data['handheld_radio_equipment'];
        $data['material_total']=$material_total;

        $TbFireMaterialReserveModel = TbFireMaterialReserveModel::where(['id'=>$data['id'],'status'=>1])->find();
        if(empty($TbFireMaterialReserveModel)){
            return Common::reJson([false,'操作失败，未找到该条记录或该记录已删除']);
        }
        if($TbFireMaterialReserveModel['create_id']!=$auth[1]['s_uid']){
            return Common::reJson([false,'请不要操作别人的数据']);
        }
        $data['update_time']=date('Y-m-d H:i:s');
        $data['update_id']=$auth[1]['s_uid'];
        $data['status']=1;

        //执行修改
        try{
                Db::startTrans();
                $result=$TbFireMaterialReserveModel->allowField(true)->save($data);
                //$result=$TbFireBarrierModel->save($data);
                if ($result > 0){
                    $fireControlTeamImageAndPath = Db::table('tb_file_image')
                        ->where('source','6')
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
                            $num = Db::table('tb_file_image')->where('id',$value)->update(['source'=>'6','source_id'=>$data['id'],'status'=>'1']);
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
    * 物资储备库--统计
    *路径:fire/fire_material_reserve/statisticsFireMaterialReserve
    *名称:物资储备库 --统计
    *描述:物资储备库 --统计
    * @return string|\think\response\Json
    */
    function statisticsFireMaterialReserve(){

        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $rs=TbFireMaterialReserveModel::statisticsFireMaterialReserve();
        
        return Common::reJson([true,Common::removeEmpty($rs)]);
    }

}