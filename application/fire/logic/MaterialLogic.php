<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/13
 * Time: 15:04
 */
namespace app\fire\logic;

use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\forest\MaterialModel;

class MaterialLogic{
    static function addMaterial($data,$auth){
        if(!Common::authRegion($data['region'],$auth['s_region'])) return [false,'您不能上报其他区域的物资储备'];
        if(!Common::isWhere($data,'input_time')){
           $data['input_time']=Common::createSystemDate();
        }
        $material=new MaterialModel();
        $material->save($data);
        return empty($material)?Errors::ADD_ERROR:$material;
    }

    static function editMaterial($data,$auth){
        if(Common::isWhere($data,'region')){
            if(!Common::authRegion($data['region'],$auth['s_region'])) return [false,'您不能编辑其他区域的物资储备'];
        }
        if(!Common::isWhere($data,'id')) return [false,'编辑id不能为空'];
        $material=new MaterialModel();
        $material->allowField(true)->save(
            $data,['id'=>$data['id']]);
        return empty($material)?Errors::UPDATE_ERROR:$material;
    }

    static function getMaterialById($id){
        $material=new MaterialModel();
        $result=$material->alias('m')->join('tb_user u','u.uid=m.input_uid')
            ->join('tb_region r','r.id = f.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left')->where('m.id',$id)
            ->field('f.*,u.name input_name,r.`name` r,r1.`name` r1,r2.`name` r2,r3.`name` r3,r4.`name` r4')->find();
        if(empty($result)) return Errors::DATA_NOT_FIND;
        $result=Common::removeEmpty($result);
        $result['region_name'] = $result['r4'].$result['r3'].$result['r2'].$result['r1'].$result['r'];
        unset($result['r']);
        unset($result['r1']);
        unset($result['r2']);
        unset($result['r3']);
        unset($result['r4']);
        return [true,$result];
    }

    static function getMaterialByCondition($data,$auth){
        $material=new MaterialModel();
        $query=$material->alias('m')->join('tb_user u','u.uid=m.input_uid')
            ->join('tb_region r','r.id = f.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left');
        if (Common::isWhere($data,'region')){
            if (!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
            $query->whereLike('m.region',$data['region'].'%');
        }else{
            $query->whereLike('m.region',$auth['s_region'].'%');
        };
        $query->order('m.input_time desc')
            ->field('m.*,u.name input_name,r.`name` r,r1.`name` r1,r2.`name` r2,r3.`name` r3,r4.`name` r4');
        $result=$query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        if(empty($result['data'])) return [true,$result];
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

    static function deleteMaterial($id){
        $material=new MaterialModel();
        $material->where('id',$id)->delete();
        return empty($material)?Errors::DELETE_ERROR:$material;
    }

    static function countMaterial($data,$auth){
        $query=MaterialModel::alias('m');
        if (Common::isWhere($data,'region')){
            if (!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
            $query->whereLike('m.region',$data['region'].'%');
        }else{
            $query->whereLike('m.region',$auth['s_region'].'%');
        };
        //$query->group('')
    }
}