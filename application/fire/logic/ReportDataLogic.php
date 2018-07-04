<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/29
 * Time: 14:31
 */

namespace app\fire\logic;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\hly\ReportDataModel;

class ReportDataLogic
{
    static function saveReportData($data,$auth){
        if(!Common::authRegion($data['region'],$auth['s_region'])) return Errors::AUTH_PREMISSION_REJECTED;
        $data['uid'] = $auth['s_uid'];
        $report_result = ReportDataModel::create($data);
        if($report_result->report_type == 1){
            $push = ['accept'=>'all', 'percent'=>$report_result->id , 'region'=>$report_result->region , 'type'=>'hly_report'];
            Common::fire_push($push);
        }
        return !empty($report_result) ? [true,$report_result->id]:[false,'上报数据失败'];
    }

    static function updateReportData($data,$auth){
        $result = Common::authLevel($data,$auth);
        if(!$result[0]) return $result;
        $reportData = new ReportDataModel;
        $report_result = $reportData->allowField(true)->save($data,['id'=>$data['id']]);
        return $report_result>0 ? [true,$report_result]:[false,'更新数据失败'];
    }

    static function queryReportDataList($data,$auth){
        if(!Common::authRegion($data['region'],$auth['s_region'])) return Errors::AUTH_PREMISSION_REJECTED;
        $tbRes = ReportDataModel::alias('rd')
            ->join('tb_region r','r.id = u.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left')
            ->join('tb_user u','rd.report_uid = u.uid')
            ->whereLike('rd.region',$data['region'].'%');
            if (Common::isWhere($data,'report_type'))
            $tbRes->where('rd.report_type',$data['report_type']);
            if (Common::isWhere($data,'begin_time'))
                $tbRes->where('rd.happen_time','>=',$data['begin_time']);
            if (Common::isWhere($data,'end_time'))
            $tbRes->where('rd.happen_time','<=',$data['end_time']);
        if(Common::isWhere($data,'name'))
            $tbRes->where('u.name',$data['name'])->whereOr('u.tel',$data['name']);
        $tbRes->field('u.uid,u.tel,rd.region,rd.report_type,rd.happen_time,rd.position,
        r4.name r4,r3.name r3,r2.name r2,r1.name r1,r.name r')
            ->order('rd.create_time', 'desc');
        $dataRes = $tbRes->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        if(empty($dataRes['data'])) return Errors::DATA_NOT_FIND;
        Common::removeEmpty($dataRes);
        foreach ($dataRes['data'] as $key => $value){
            $dataRes['data'][$key]['region_name'] = $value['r4'].$value['r3'].$value['r2'].$value['r1'].$value['r'];
            unset($dataRes['data'][$key]['r']);
            unset($dataRes['data'][$key]['r1']);
            unset($dataRes['data'][$key]['r2']);
            unset($dataRes['data'][$key]['r3']);
            unset($dataRes['data'][$key]['r4']);
        }
        return [true,$dataRes];
    }

    static function queryReportDataInfo($id){
        $reportDataInfo = ReportDataModel::alias('rd')
            ->join('tb_user u','rd.report_uid = u.uid')
            ->join('tb_region r','r.id = u.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left')
            ->field('rd.region,rd.happen_time,rd.position,rd.position_type,rd.position_type,
            rd.report_image,rd.report_desc,u.tel,u.name,r4.name r4,r3.name r3,r2.name r2,r1.name r1,r.name r')
            ->where('rd.id',$id)
            ->find();
        if(empty($reportDataInfo)) return Errors::DATA_NOT_FIND;
        Common::removeEmpty($reportDataInfo);
        $reportDataInfo['region_name'] = $reportDataInfo['r4.'].$reportDataInfo['r3'].$reportDataInfo['r2'].$reportDataInfo['r1'].$reportDataInfo['r'];
        return [true,$reportDataInfo];
    }

    static function deleteReportData($data,$auth){
        $result = Common::authLevel($data,$auth);
        if(!$result[0]) return $result;
        $result = ReportDataModel::destroy($data['id']);
        return $result>0 ? [true,$result]:[false,'删除失败'];
    }
}