<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/9
 * Time: 15:54
 */

namespace app\fire\logic;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\fire\FireUploadModel;
use app\fire\model\fire_assess\FireAssessModel;

class FireAssessLogic
{
    /**
     * 添加火情评估
     * @param $data
     * @param $auth
     * @return array
     * @throws \think\exception\DbException
     */
    static function saveFireAssess($data,$auth){
        //获得火情的区域
        $fire = FireUploadModel::get($data['fire_id']);
        if (empty($fire)) return Errors::DATA_NOT_FIND;
        if ($fire->status != 3) return [false,'无法评估未结束的火情'];
        if ($fire->status != 3) return [false,'无法评估未结束的火情'];
        if (Common::authRegion($fire->region,$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
        $result =  FireAssessModel::saveFireAssess($data);
        return $result;
    }

    /**
     * 获得火情评估详情
     * @param $data
     * @return array
     * @throws \think\exception\DbException
     */
    static function queryFireAssessInfo($data){
        $fire = FireAssessModel::get($data['id']);
        return empty($fire)? Errors::DATA_NOT_FIND:[true,$fire->toArray()];
    }

    /**
     * @param $data
     * @param $auth
     * @return array
     * @throws \think\exception\DbException
     */
    static function updateFireAssess($data,$auth){
        //获得火情的区域
        $fire_assess = FireAssessModel::get($data['id']);
        if (empty($fire_assess)) return Errors::DATA_NOT_FIND;
        $fire = FireUploadModel::get($fire_assess->fire_id);
        if (empty($fire)) return Errors::DATA_NOT_FIND;
        if (Common::authRegion($fire->region,$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
        $result =  FireAssessModel::updateFireAssess($data);
        return $result;
    }
}