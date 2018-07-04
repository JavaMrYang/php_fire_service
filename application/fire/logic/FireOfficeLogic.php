<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/9
 * Time: 16:59
 */
namespace app\fire\logic;

use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\forest\FireOfficeModel;
use think\Db;
use think\Exception;

class FireOfficeLogic{

    static function addFireOffice($data,$auth){
        if(!Common::authRegion($data['region'],$auth['s_region'])) return [false,'您不能添加其他区域的办公室信息'];
        $data['input_uid']=$auth['s_uid'];
        if(!Common::isWhere($data,'input_time')){  //判断是否传入录入时间，如果没有则设置录入时间为当前系统时间
            $data['input_time']=Common::createSystemDate();
        }
        try{
            Db::startTrans();
            $fire_office=new FireOfficeModel();
            $fire_office->allowField(true)->save($data);
            if(!empty($fire_office)){
                if (Common::isWhere($data,'office_image') && $data['office_image'][0] != '') {
                    $num = 0;
                    foreach ($data['office_image'] as $value) {
                        if ((Db::table('tb_file_image')->where('id', $value)->update(['source' => '9', 'source_id' => $fire_office->id,'status'=>'1'])) > 0)
                            $num++;
                    }
                    if ($num == count($data['office_image'])) {
                        Db::commit();
                        return [true,$fire_office->id];
                    }
                }else{
                    Db::commit();
                    return [true,$fire_office->id];
                }
            }
            return Errors::ADD_ERROR;
        }catch (Exception $e){
            Db::rollback();
            return Errors::Error($e->getMessage());
        }

    }

    static function editFireOffice($data,$auth){
        $office_info=FireOfficeModel::get($data['id']);
        if (empty($data)) return Errors::DATA_NOT_FIND;
        if (!Common::authLevel($office_info->toArray(),$auth)[0]) return Errors::AUTH_PREMISSION_EMPTY;
        if (!Common::authRegion($office_info->region,$auth['s_region']))
            return Errors::REGION_PREMISSION_REJECTED;
        $fire_office=new FireOfficeModel;
        $fire_office->save($data,['id'=>$data['id']]);
        return empty($fire_office)?Errors::UPDATE_ERROR:[true,$fire_office];
    }

    static function getFireOfficeById($id){
        $fire_office=new FireOfficeModel();
        $result=$fire_office->alias('f')->join('tb_user u','u.uid=f.input_uid')
        ->join('tb_region r','r.id = f.region')
        ->join('tb_region r1','r1.id = r.parentId','left')
        ->join('tb_region r2','r2.id = r1.parentId','left')
        ->join('tb_region r3','r3.id = r2.parentId','left')
        ->join('tb_region r4','r4.id = r3.parentId','left')->where('f.id',$id)
        ->field('f.*,u.name input_name,u.tel input_tel,r.`name` r,r1.`name` r1,r2.`name` r2,r3.`name` r3,r4.`name` r4')->find();
        if(empty($result)) return Errors::DATA_NOT_FIND;
        $result=Common::removeEmpty($result);
        $path = Db::table('tb_file_image')->where('source','9')->where('source_id',$id)->field('id,path')->select();
        $result['image_path']=$path;
        $result['region_name'] = $result['r4'].$result['r3'].$result['r2'].$result['r1'].$result['r'];
        unset($result['r']);
        unset($result['r1']);
        unset($result['r2']);
        unset($result['r3']);
        unset($result['r4']);
        return [true,$result];
    }

    static function getFireOfficeByCondition($data,$auth){
        $fire_office=new FireOfficeModel();
        $query=$fire_office->alias('f')->join('tb_user u','u.uid=f.input_uid')
            ->join('tb_region r','r.id = f.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left');
        if (Common::isWhere($data,'region')){
            if (!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
            $query->whereLike('f.region',$data['region'].'%');
        }else{
            $query->whereLike('f.region',$auth['s_region'].'%');
        };
        $query->order('f.input_time desc')
            ->field('f.*,u.name input_name,u.tel input_tel,r.`name` r,r1.`name` r1,r2.`name` r2,r3.`name` r3,r4.`name` r4');
        $result=$query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        if(empty($result['data'])) return [true,$result];
        $result['data']=Common::removeEmpty($result['data']);
        foreach ($result['data'] as $key => $value){
            $result['data'][$key]['region_name'] = $value['r4'].$value['r3'].$value['r2'].$value['r1'].$value['r'];
            unset($result['data'][$key]['r']);
            unset($result['data'][$key]['r1']);
            unset($result['data'][$key]['r2']);
            unset($result['data'][$key]['r3']);
            unset($result['data'][$key]['r4']);
        }
        return [true, $result];
    }

    static function deleteFireOffice($data,$auth){
        $office=FireOfficeModel::get($data['id']);
        if(empty($office)) return Errors::DATA_NOT_FIND;
        if(!Common::authRegion($office['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
        if($office->input_uid!=$auth['s_uid']) return [false,'您不能删除别人的数据'];
        $result=$office->destroy($data['id']);
        return !$result?Errors::DELETE_ERROR:[true,$data['id']];
    }
}