<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/11
 * Time: 10:30
 */

namespace app\fire\logic;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\fire\FireUploadModel;
use app\fire\model\typical_case\TypicalCaseModel;
use think\Db;
use think\Exception;

class TypicalCaseLogic
{
    /**
     * 保存典型案例信息
     * @param $data
     * @param $auth
     * @return array
     * @throws \think\exception\DbException
     */
    static function saveTypicalCase($data,$auth){
        if (!Common::authRegion($data['region'],$auth['s_region']))
            return Errors::REGION_PREMISSION_REJECTED;
        $fire_upload = FireUploadModel::get($data['fire_id']);
        if(empty($fire_upload)) return [false,'火情id错误'];
        $data['user_id'] = $auth['s_uid'];
        $result = TypicalCaseModel::create($data);
        if ($result) {
            return [true,$result->id];
        }else{
            return [false,'添加失败'];
        }
    }

    /**
     * 删除典型案例信息
     * @param $id
     * @param $auth
     * @return array
     * @throws \think\exception\DbException
     */
    static function deleteTypicalCase($id,$auth){
        $query = TypicalCaseModel::get($id);
        if (empty($query)) return Errors::DATA_NOT_FIND;
        if (!Common::authRegion($query->region,$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
        // 软删除
        $result = $query->delete();
        return $result > 0?[true,'删除成功']:[false,'删除失败'];
    }

    /**
     * 修改典型案例
     * @param $data
     * @param $auth
     * @return array
     */
    static function updateTypicalCase($data,$auth){
        try {
            Db::startTrans();
            $typical_case = new TypicalCaseModel;
            $fire_assess = $typical_case::save($data,['id'=>$data['id']]);
            Db::commit();
            return [true,$fire_assess];
        }catch (Exception $exception){
            Db::rollback();
            return Errors::Error($exception->getMessage());
        }
    }

    /**
     * 查询典型案例详细
     * @param $data
     * @param $auth
     * @return array
     * @throws \think\exception\DbException
     */
    public static function queryTypicalCaseList($data,$auth){
        $query = TypicalCaseModel::with('user');
        if (Common::isWhere($data,'region')){
            if (!Common::authRegion($data['region'],$auth['s_region']))
                return Errors::REGION_PREMISSION_REJECTED;
            $query->whereLike('region',$data['region'].'%');
        }else{
            $query->whereLike('region',$auth['s_region'].'%');
        }
        if (Common::isWhere($data,'begin_time'))
            $query->where('create_time','>=',$data['begin_time']);
        if (Common::isWhere($data,'end_time'))
            $query->where('create_time','<=',$data['end_time']);
        $query = $query->order('create_time','desc');
        $dataRes = $query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        return  empty($dataRes)? Errors::DATA_NOT_FIND:[true,Common::removeEmpty($dataRes)];
    }


    /**
     * 查询典型案例详细
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public static function queryTypicalCaseInfo($id){
        $result = TypicalCaseModel::get($id,'user');
        return empty($result)? Errors::DATA_NOT_FIND:[true,$result->toArray()];
    }
}