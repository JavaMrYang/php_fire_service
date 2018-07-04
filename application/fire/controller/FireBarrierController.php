<?php
/**
 * Created by PhpStorm.
 * User: 呵.谢勇
 * Date: 2018/6/12
 * Time: 18:50
 */

namespace app\fire\controller;


use app\fire\validate\BaseValidate;
use think\Controller;
use think\Db;
use app\fire\model\TbFireBarrierModel;
use app\fire\logic\FireOfficeLogic;
use app\fire\model\TbFireWatchtowerModel;
use app\fire\model\TbFireMaterialReserveModel;
use app\fire\logic\FireControlTeamLogic;



class FireBarrierController extends Controller
{
    /**
    *防火隔离带 --新增
    *路径:  fire/fire_barrier/addFireBarrier
    *名称:防火隔离带--新增
    *描述:防火隔离带--新增
    */ 
    function addFireBarrier(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $result = $this->validate($data,'FireBarrier.add');
        if($result !== true) return  Common::reJson(Errors::validateError($result));

        $auth=$auth[1];
        if (!Common::authRegion($data['region'],$auth['s_region']))
                 return Errors::REGION_PREMISSION_REJECTED;
        $time=date('Y-m-d H:i:s');
        $result=new TbFireBarrierModel;
        $data['create_time']=$time;
        $data['create_id']=$auth['s_uid'];
        $data['input_time']=$time;
        $data['status']=1;
        try {
            Db::startTrans();
            $result->allowField(true)->save($data);
            if ($result){
                if (Common::isWhere($data,'team_image') && $data['team_image'][0] != '') {
                    $num = 0;
                    foreach ($data['team_image'] as $value) {
                        if ((Db::table('tb_file_image')->where('id', $value)->update(['source' => '8', 'source_id' => $result->id,'status'=>'1'])) > 0)
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
       
    }


    /**
    *防护隔离带--列表查询
    *url:getFireBarrierList
    */
    function getFireBarrierList(){
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
        $result = TbFireBarrierModel::queryFireBarrierList($data,$auth[1]);
        return Common::reJson(Common::removeEmpty($result));
    }

    /**
     * 防护隔离带--详情信息
     */
    function getFireBarrierInfo(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);

        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $result = TbFireBarrierModel::queryFireBarrierById($data);
        $path = Db::table('tb_file_image')->where('source','8')->where('source_id',$data['id'])->field('id,path')->select();
        $result['image_path']=$path;
        if(empty($result)) return  Common::reJson(Errors::DATA_NOT_FIND);
        //Common::removeEmpty($result);
        //return [true,$result];
        return Common::reJson([true,Common::removeEmpty($result)]);
    }

     /**
     * 防护隔离带--软删除
     * @return string|\think\response\Json
     */
    function delFireBarrier(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $TbFireBarrierModel = TbFireBarrierModel::get($data['id']);
        if(empty($TbFireBarrierModel)){
            return Common::reJson([false,'操作失败，未找到该条记录']);
        }
        if($TbFireBarrierModel['status']==2){
            return Common::reJson([false,'请勿重复执行删除操作']);
        }
        if($TbFireBarrierModel['create_id']!=$auth[1]['s_uid']){
            return Common::reJson([false,'请不要操作别人的数据']);
        }
        $TbFireBarrierModel->delete_time=date('Y-m-d H:i:s');
        $TbFireBarrierModel->delete_id=$auth[1]['s_uid'];
        $TbFireBarrierModel->status=2;
        $rs=$TbFireBarrierModel->save();
        if(empty($rs)){
            return Common::reJson([false,'删除操作失败']);
        }
        return Common::reJson([true,'操作成功']);
    }

    /**
     * 防护隔离带--修改
     * @return string|\think\response\Json
     */
    function editFireBarrier(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'id' => 'require|number'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));

        $result = $this->validate($data,'FireBarrier.edit');
        if($result !== true) return  Common::reJson(Errors::validateError($result));

        $TbFireBarrierModel = TbFireBarrierModel::where(['id'=>$data['id'],'status'=>1])->find();
        if(empty($TbFireBarrierModel)){
            return Common::reJson([false,'操作失败，未找到该条记录或该记录已删除']);
        }
        if($TbFireBarrierModel['create_id']!=$auth[1]['s_uid']){
            return Common::reJson([false,'请不要操作别人的数据']);
        }
        $data['update_time']=date('Y-m-d H:i:s');
        $data['update_id']=$auth[1]['s_uid'];
        $data['status']=1;
       //执行修改
        try{
                Db::startTrans();
                $result=$TbFireBarrierModel->save($data);
                if ($result > 0){
                    $fireControlTeamImageAndPath = Db::table('tb_file_image')
                        ->where('source','8')
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
                            $num = Db::table('tb_file_image')->where('id',$value)->update(['source'=>'8','source_id'=>$data['id'],'status'=>'1']);
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
    *分布示意图 
    */
    function getDistributionDiagramList(){
        $auth = Common::auth();

        if (!$auth[0]) return Common::reJson($auth);
        $data = input('post.');
        $validate = new BaseValidate([
            'region'=>'region'
        ]);
        if (!$validate->check($data)) return Common::reJson(Errors::Error($validate->getError()));
        $data['current_page']= !empty($data['current_page']) ? $data['current_page']:1;    //起始页
        $data['per_page']= !empty($data['per_page'])?$data['per_page']: 1000; //每页多少条
        $data['status']=1;

        if(empty($data['type'])){
            return Common::reJson([false,['网络异常']]);
        }
        $type=$data['type'];
        $result['FireOffice_RS']=[];//防火办公室
        $result['FireBarrier_RS']=[];//防火隔离带
        $result['FireWatchtower_RS']=[];//瞭望塔
        $result['FireMaterialReserve_RS']=[];//物资储备库
        $result['FireControlTeam_RS']=[];
        if($type=='all'){
            $result['FireOffice_RS']=FireOfficeLogic::getFireOfficeByCondition($data,$auth[1])[1]['data'];//防火办公室
            $result['FireBarrier_RS']=TbFireBarrierModel::queryFireBarrierList($data,$auth[1])[1]['data'];//防火隔离带
            $result['FireWatchtower_RS']=TbFireWatchtowerModel::queryFireWatchtowerList($data,$auth[1])[1]['data'];//瞭望塔
            $result['FireMaterialReserve_RS']=TbFireMaterialReserveModel::queryFireMaterialReserveList($data,$auth[1])[1]['data'];//物资储备库
            $result['FireControlTeam_RS']=FireControlTeamLogic::queryFireControlTeamList($data,$auth[1])[1]['data'];//消防队伍
        } else if($type=='FireOffice'){
            $result['FireOffice_RS']=FireOfficeLogic::getFireOfficeByCondition($data,$auth[1])[1]['data'];//防火办公室
        } else if($type=='FireWatchtower'){
            $result['FireWatchtower_RS']=TbFireWatchtowerModel::queryFireWatchtowerList($data,$auth[1])[1]['data'];//瞭望塔
        } else if($type=='FireMaterialReserve'){
            $result['FireMaterialReserve_RS']=TbFireMaterialReserveModel::queryFireMaterialReserveList($data,$auth[1])[1]['data'];//物资储备库
        } else if($type=='FireControlTeam'){
            $result['FireControlTeam_RS']=FireControlTeamLogic::queryFireControlTeamList($data,$auth[1])[1]['data'];//消防队伍
        } else if($type=='FireBarrier'){
             $result['FireBarrier_RS']=TbFireBarrierModel::queryFireBarrierList($data,$auth[1])[1]['data'];//防火隔离带
        } 

        //$result = TbFireBarrierModel::queryFireBarrierList($data,$auth[1]);
        return Common::reJson([true,Common::removeEmpty($result)]);
    }

    function index(){
        $DOCUMENT_ROOT=$_SERVER['DOCUMENT_ROOT'];
        $data = Common::getPostJson();
        $file = request()->file('file');
        var_dump($file);
        if(empty($data['pathName'])){
            return Common::reJson([false,'路径名称[pathName]不能为空']);
        }
        if(empty($data['fileName'])){
            return Common::reJson([false,'文件名称[fileName]不能为空']);
        }
        $path=$data['pathName'];
        $fileName=$data['fileName'];
        //$fp=fopen("C:/Users/Administrator/Desktop/fanghuo/application/fire/controller/FireMaterialReserveController.php",'rb');
        try{
            $fp=fopen($path.$fileName, 'rb');
        }catch(\Exception $e){
            return Common::reJson([false,'请选择有效的文件']);
            //$this->error('执行错误');
        }
        $rs=[];
        $i=0;
        while (!feof($fp)){
            $order =fgets($fp,2048);
            if(strpos($order,'*路径:')!==false){
                //$str = str_replace(array("\r\n", "\r", "\n"), "", substr($order,12));   
                $str = str_replace(array("\r\n", "\r", "\n"," "), "", explode(':',$order)[1]);   
                $rs[$i]['url']=$str;
                $i=$i+1;
            }
            if(strpos($order,'*名称:')!==false){
                $rs[$i-1]['name']=str_replace(array("\r\n", "\r", "\n"," "), "", explode(':',$order)[1]);
            }
            if(strpos($order,'*描述:')!==false){
                 $rs[$i-1]['describe']=str_replace(array("\r\n", "\r", "\n"," "), "", explode(':',$order)[1]);
            }
        }
        fclose($fp);
        return json($rs);
    }
     
} 