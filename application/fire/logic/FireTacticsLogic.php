<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/4
 * Time: 8:38
 */

namespace app\fire\logic;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\fire_tactics\FireTacticsImageModel;
use app\fire\model\fire_tactics\FireTacticsModel;
use think\Db;
use think\Error;
use think\Exception;

class FireTacticsLogic
{
    static function saveFireTactics($data,$auth){
        try{
            if (!Common::authRegion($data['region'],$auth['s_region']))
                return Errors::REGION_PREMISSION_REJECTED;
            Db::startTrans();
            $data['user_id'] = $auth['s_uid'];
            $result = FireTacticsModel::create($data);
            if(!isset($result->id)) return [false,'添加失败'];
            $result->tacticsImage()->save(['tactics_image'=>$data['tactics_image']]);
            return isset($result->id)? [true,$result->id]:[false,'添加失败'];
        }catch (Exception $exception){
            Db::rollback();
            return Errors::Error($exception->getMessage());
        }
    }

    static function deleteFireTactics($id,$auth){
        $data = FireTacticsModel::get($id);
        if (empty($data)) return Errors::DATA_NOT_FIND;
        if (!Common::authRegion($data->region,$auth['s_region']))
            return Errors::REGION_PREMISSION_REJECTED;
        $result = FireTacticsModel::destroy($id);
        return $result>0? [true,$result]:[false,'删除失败'];
    }

    static function updateFireTactics($data,$auth){
        $info = FireTacticsModel::get($data['id']);
        if (empty($data)) return Errors::DATA_NOT_FIND;
        if (!Common::authLevel($info->toArray(),$auth)[0]) return Errors::AUTH_PREMISSION_EMPTY;
        if (!Common::authRegion($info->region,$auth['s_region']))
            return Errors::REGION_PREMISSION_REJECTED;
        $result = FireTacticsImageModel::create(['fire_tactics_id'=>$data['id'],'tactics_image'=>$data['tactics_image']]);
        return isset($result->id)? [true,$result]:[false,'绘制失败'];
    }

    static function queryFireTacticsList($data,$auth){
        $subsql = FireTacticsImageModel::field('max(create_time) max_time')
            ->group('fire_tactics_id')
            ->order('create_time','desc')
            ->buildSql();
        $subsql = FireTacticsImageModel::alias('fti')
            ->join([$subsql => 'fti1'],'fti1.max_time = fti.create_time')
            ->group('fire_tactics_id')
            ->buildSql();
        $query = FireTacticsModel::alias('ft')
            ->join('tb_user u','u.uid = ft.user_id')
            ->join('tb_region r','r.id = u.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left')
            ->join([$subsql=> 'fti'] ,'ft.id = fti.fire_tactics_id');
        if (Common::isWhere($data,'region')){
            if (!Common::authRegion($data['region'],$auth['s_region']))
                return Errors::REGION_PREMISSION_REJECTED;
            $query->whereLike('ft.region',$data['region'].'%');
        }else{
            $query->whereLike('ft.region',$auth['s_region'].'%');
        }
        if (Common::isWhere($data,'name'))
            $query->whereLike('u.name','%'.$data['name'].'%')
                ->whereOr('u.tel','like',$data['name'].'%');
        if(Common::isWhere($data,'begin_time')) $query->where('ft.create_time','>=',$data['begin_time']);
        if(Common::isWhere($data,'end_time')) $query->where('ft.create_time','<=',$data['end_time']);
        $query = $query->field('ft.id,ft.name fname,fti.tactics_image,u.name,ft.user_id,ft.create_time,ft.region,
        r4.name r4,r3.name r3,r2.name r2,r1.name r1,r.name r')
            ->order('ft.create_time','desc');
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
        return [true, $dataRes];
    }

    static function queryFireTacticsInfo($id){
        $result = FireTacticsModel::get($id,['user','tacticsImage']);
        if (empty($result)) return Errors::DATA_NOT_FIND;
        Common::removeEmpty($result);
        return [true,$result];
    }
}