<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/14
 * Time: 13:57
 */

namespace app\fire\logic;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\FireControlTeamModel;
use app\fire\model\RegionModel;
use think\Db;
use think\Exception;

class FireControlTeamLogic
{
    static function saveFireControlTeam($data,$auth){
        $data['user_id'] = $auth['s_uid'];
        if (!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
        Db::startTrans();
        $result = FireControlTeamModel::create($data,true);
        if ($result){
            if (Common::isWhere($data,'team_image') && $data['team_image'][0] != '') {
                $num = 0;
                foreach ($data['team_image'] as $value) {
                    if ((Db::table('tb_file_image')->where('id', $value)->update(['source' => '4', 'source_id' => $result->id])) > 0)
                        $num++;
                }
                if ($num == count($data['team_image'])) {
                    Db::commit();
                    return [true, $result->id];
                }
            }else{
                Db::commit();
                return [true, $result->id];
            }
        }
        Db::rollback();
        return [false, '添加失败'];
    }

    static function deleteFireControlTeam($id,$auth){
        $fireControlTeam = FireControlTeamModel::get($id);
        if (empty($fireControlTeam)) return Errors::DATA_NOT_FIND;
        if ($fireControlTeam->user_id == $auth['s_uid']){
            $result = $fireControlTeam->delete();
            return $result>0?[true,$result]:[false,'删除失败'];
        }
        return [false,'不能删除他人的用户信息'];
    }

    static function updateFireControlTeam($data,$auth){
        $fireControlTeam = FireControlTeamModel::get($data['id']);
        if (empty($fireControlTeam)) return Errors::DATA_NOT_FIND;
        if ($fireControlTeam->user_id == $auth['s_uid']){
            try{
                Db::startTrans();
                $saveFireControlTeam = new FireControlTeamModel;
                if(Common::isWhere($data,'user_id')) unset($data['user_id']);
                $result = $saveFireControlTeam->save($data,['id'=>$data['id']]);
                if ($result > 0){
                    $fireControlTeamImageAndPath = Db::table('tb_file_image')
                        ->where('source','4')
                        ->where('source_id',$data['id'])
                        ->column('id,path');
                    $fireControlTeamImage = array_keys ( $fireControlTeamImageAndPath );
                    //比较两次上传的文件名是否一致
                    $del_array = array_diff($fireControlTeamImage,$data['team_image']);
                    //删除舍弃的文件
                    if(!empty($del_array)){
                        foreach ($del_array as $value){
//                            $path = Db::table('tb_file_image')->where('id',$value)->column('path');
                            unlink(FILE_PATH.DS.$fireControlTeamImageAndPath[$value]);
                        }
                    }
                    $add_array = array_diff($data['team_image'],$fireControlTeamImage);
                    if(!empty($add_array)){
                        foreach ($add_array as $value) {
                            $num = Db::table('tb_file_image')->where('id',$value)->update(['source'=>'4','source_id'=>$data['id']]);
                        }
                    }
                    Db::commit();
                    return [true,$result];
                }else{
                    Db::rollback();
                    return [false,'修改失败'];
                }
            }catch (Exception $exception){
                Db::startTrans();
                return [false,'修改失败'];
            }
        }
        return [false,'不能修改他人的用户信息'];
    }

    static function queryFireControlTeamList($data,$auth){
        $query = FireControlTeamModel::alias('fct')
            ->join('tb_user u','u.uid = fct.user_id')
            ->join('tb_region r','r.id = fct.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left');
        if (Common::isWhere($data,'region')){
            if (!Common::authRegion($data['region'],$auth['s_region']))
                return Errors::REGION_PREMISSION_REJECTED;
            $query->whereLike('fct.region',$data['region'].'%');
        }else{
            $query->whereLike('fct.region',$auth['s_region'].'%');
        }
        $query = $query
            ->field('fct.*,u.name,r4.name r4,r3.name r3,r2.name r2,r1.name r1,r.name r')
            ->order('create_time','desc');
        $dataRes = $query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        if(empty($dataRes)) return  Errors::DATA_NOT_FIND;
        foreach ($dataRes['data'] as $key => $value){
            $dataRes['data'][$key]['region_name'] = $value['r4'].$value['r3'].$value['r2'].$value['r1'].$value['r'];
            unset($dataRes['data'][$key]['r']);
            unset($dataRes['data'][$key]['r1']);
            unset($dataRes['data'][$key]['r2']);
            unset($dataRes['data'][$key]['r3']);
            unset($dataRes['data'][$key]['r4']);
        }
        return [true, Common::removeEmpty($dataRes)];
    }

    static function queryFireControlTeamInfo($id){
        $result = FireControlTeamModel::get($id,'user');
        $region_name = RegionModel::getRegionFullNameById($result['region']);
        $result['region_name'] = $region_name[1]['region_name'];
        //获得图片
        $path = Db::table('tb_file_image')->where('source','4')->where('source_id',$id)->field('id,path')->select();
        $result['image_path']=$path;
        return !empty($result)? [true,Common::removeEmpty($result)]:Errors::DATA_NOT_FIND;
    }

    static function queryFireControlTeamCount($data,$auth){
        if (Common::isWhere($data,'region')) {
            if (!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
        }else{
            $data['region'] = 43;
        }
        return FireControlTeamModel::getFireControlTeamCount($data['region']);
    }
}