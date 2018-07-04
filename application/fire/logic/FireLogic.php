<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/16
 * Time: 15:37
 */

namespace app\fire\logic;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\fire\FireFinishModel;
use app\fire\model\fire\FireTraceImageModel;
use app\fire\model\fire\FireTraceMaterialsModel;
use app\fire\model\fire\FireTraceModel;
use app\fire\model\fire\FireUploadImageModel;
use app\fire\model\fire\FireUploadModel;
use app\fire\model\overall\HotModel;
use app\fire\model\RegionModel;
use app\fire\validate\BaseValidate;
use think\Db;
use think\Exception;
use think\facade\Log;

class FireLogic
{
    /**
     * @param $data
     * @return array
     */
    static function saveFireUpload($data,$auth){
        try{

            if(!Common::authRegion($data['region'],$auth['s_region'])) return [false,'无法添加其他区域的火情信息'];
            //上报人为自己
            $data['user_id'] = $auth['s_uid'];
            Db::startTrans();
            $fire = FireUploadModel::create($data,true);
            if($fire){
                
                Db::table('tb_file_image');

//                if(Common::isWhere($data,'hot_id')){
//                    $hot=new HotModel();
//                    $hot->save(['is_fire'=>1]
//                        ,['hot_id'=>$data['hot_id']]);
//                }
//                $result = $fire->uploadImage()->save($data);
//                if($result){
                Db::commit();
                $push = ['accept'=>'all', 'percent'=>$fire->id , 'region'=>$data['region'] , 'type'=>'fire'];
                Common::fire_push($push);
                return [true,$fire->id];
            }else{
                Db::rollback();
                return [false,"上传失败"];
            }
        }catch (Exception $exception){
            Db::rollback();
            return [false,$exception->getMessage()];
        }
    }

    /**
     * @param $data
     * @return array
     */
    static function updateFireUpload($data,$auth){
        try{
            //判断上报区域是否在自己管理范围内
            $fire = FireUploadModel::get($data['id'],'uploadImage');
            if(empty($fire)) return Errors::DATA_NOT_FIND;
            if(!Common::authRegion($data['region'],$auth['s_region'])||!Common::authRegion($fire['region'],$auth['s_region']))
                return [false,'无法修改其他区域的火情信息'];
            Db::startTrans();
            $fire->happen_time = $data['happen_time'];
            $fire->region = $data['region'];
            $fire->position = $data['position'];
            $fire->position_type = $data['position_type'];
            $fire->fire_level = $data['fire_level'];
            $fire->fire_type = $data['fire_type'];
            $fire->fire_cause = $data['fire_cause'];
            $fire->fire_area = $data['fire_area'];
            $result = $fire->save();
            unset($data['id']);
            $result = $fire->uploadImage->save($data);
            if($result){
                Db::commit();
                return [true,$fire->id];
            }else{
                Db::rollback();
                return [false,"修改数据失败"];
            }
        }catch (Exception $exception){
            Db::rollback();
            echo $exception->getMessage();
            return [false,"修改数据失败"];
        }
    }

    /**
     * @param $data
     * @return array
     * @throws \think\exception\DbException
     */
    static function queryFireUploadList($data,$auth){
        //判断查询上报区域是否在自己管理范围内
        $tbRes = FireUploadModel::alias('fu')
            ->join('tb_user u','fu.user_id = u.uid')
            ->join('tb_region r','r.id = fb.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left');
            if(Common::isWhere($data,'status')) $tbRes->where('fu.status',$data['status']);
            if (Common::isWhere($data,'region')){
                if (!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
                $tbRes->whereLike('fu.region',$data['region'].'%');
            }else{
                $tbRes->whereLike('fu.region',$auth['s_region'].'%');
            }
            if(Common::isWhere($data,'fire_type')) $tbRes->where('fu.fire_type',$data['fire_type']);
            if(Common::isWhere($data,'fire_level')) $tbRes->where('fu.fire_level',$data['fire_level']);
            if(Common::isWhere($data,'begin_time')) $tbRes->where('fu.happen_time','>=',$data['begin_time']);
            if(Common::isWhere($data,'end_time')) $tbRes->where('fu.happen_time','<=',$data['end_time']);
            $tbRes->order('fu.happen_time','desc')
                  ->field('fu.id,fu.region,fu.fire_level,fu.fire_type,fu.fire_area,fu.happen_time,u.name,u.tel,fu.status,
                  r4.name r4,r3.name r3,r2.name r2,r1.name r1,r.name r');
        $dataRes = $tbRes->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        if(empty($dataRes)) return  Errors::DATA_NOT_FIND;
        foreach ($dataRes['data'] as $key => $value){
            $dataRes['data'][$key]['region_name'] = $value['r4'].$value['r3'].$value['r2'].$value['r1'].$value['r'];
            unset($dataRes['data'][$key]['r']);
            unset($dataRes['data'][$key]['r1']);
            unset($dataRes['data'][$key]['r2']);
            unset($dataRes['data'][$key]['r3']);
            unset($dataRes['data'][$key]['r4']);
        }
        Common::removeEmpty($dataRes);
        return [true, $dataRes];
    }

    static function queryFireUploadInfo($id){
        $result = FireUploadModel::get($id,'uploadImage');
        if(empty($result)) return Errors::DATA_NOT_FIND;
        Common::removeEmpty($result);
        return [true,$result];
    }

    static function deleteFire($fire_id,$auth){
        try{
            Db::startTrans();
            $fire = FireUploadModel::get($fire_id,['trace','finish']);
            if(empty($fire)) return Errors::DATA_NOT_FIND;
            if(!Common::authRegion($fire['region'],$auth['s_region'])) return [false,'无法删除其他区域的火情信息'];
            $upload_result = $fire->delete();
            $trace_result = $fire->trace()->delete();
            dump($upload_result.'-'.$trace_result);die;
            $finish_result = $fire->finish()->delete();
            if($upload_result > 0 && $trace_result > 0 && $finish_result>0){
                Db::commit();
                return [true,'火情信息已删除'];
            }else{
                Db::rollback();
                return [false,'网络错误，火情信息删除失败'];
            }
        }catch (Exception $exception){
            echo $exception->getMessage();
            Db::rollback();
            return [false,$exception->getMessage()];
        }
    }

    static function saveFireTrace($data){
        try{
            Db::startTrans();
            $fire_upload = FireUploadModel::get($data['fire_id']);
            if(empty($fire_upload)) return Errors::DATA_NOT_FIND;
            if($fire_upload->status >= 2) return [false,'无法提交无效数据'];
            $fire = FireTraceModel::create($data,true);
            $fire->traceImage()->save($data);
            $fire->traceMaterials()->save($data);
            FireUploadModel::update(['status'=>2],['id'=>$fire->fire_id]);
            Db::commit();
            return [true,$fire->id];
        }catch (Exception $exception){
            Db::rollback();
            Log::info($exception->getMessage());
            return [false,"上传火情跟踪信息失败"];
        }
    }

    static function updateFireTrace($data){
        try{
            Db::startTrans();
            $fire = FireTraceModel::get($data['id']);
            if(empty($fire)) return Errors::DATA_NOT_FIND;
            if($fire->status == 3) return [false,'火情结束，无法修改'];
            $fire_trace = new FireTraceModel();
            $fire_trace
                ->allowField(['trace_time','trace_weather','position','position_type',
                    'woods_area','tree_species','tree_species','tree_fire','fire_level',
                    'quench_time','commander_name','commander_job','commander_member',
                    'firemen_name','firemen_num'])
                ->save($data,['id'=>$data['id']]);
            unset($data['id']);
            $fire->traceImage->save($data);
            $fire->traceMaterials->save($data);
            Db::commit();
            return [true,$fire->id];
        }catch (Exception $exception){
            Db::rollback();
            return [false,$exception->getMessage()];
        }
    }

    static function queryFireTraceInfo($id){
        $result = FireTraceModel::get($id,['traceImage','traceMaterials']);
        if (empty($result)) return Errors::DATA_NOT_FIND;
        Common::removeEmpty($result);
        return  [true,$result] ;
    }

    static function saveFireFinish($data){
        try{
            Db::startTrans();
            $fire_upload = FireUploadModel::get($data['fire_id']);
            if(empty($fire_upload)) return [false,'无法提交无效数据'];
            if($fire_upload->status == 3) return [false,'无法提交无效数据'];
            $fire = FireFinishModel::create($data,true);
            $fire->finishImage()->save($data);
            FireUploadModel::update(['status'=>3],['id'=>$fire->fire_id]);
            FireTraceModel::update(['status'=>3],['id'=>$fire->fire_id]);
            Db::commit();
            return [true,$fire->id];
        }catch (Exception $exception){
            Db::rollback();
            return [false,$exception->getMessage()];
        }
    }

    static function queryFireFinishInfo($id){
        $result = FireFinishModel::get($id,'finishImage');
        return !empty($result) ? [true,$result] :  Errors::DATA_NOT_FIND;
    }

    static function queryFireCount($data){
        $query = null;
        $time_type = 'finish_time';
        if($data['type'] == 1){
            $sql = "SELECT
                        date_format( happen_time, '%Y-%m' ) time,
                        count( id ) fireUploadNo ,
                        count(DISTINCT IF(fire_level = '1' , id, null)) fireLevel1,
                        count(DISTINCT IF(fire_level = '2' , id, null)) fireLevel2,
                        count(DISTINCT IF(fire_level = '3' , id, null)) fireLevel3,
                        count(DISTINCT IF(fire_level = '4' , id, null)) fireLevel4
                    FROM
                        tb_fire_upload
                    WHERE
                        date_format( happen_time, '%Y-%m' ) BETWEEN :begin_time AND :end_time 
                        AND delete_time IS NULL";
            $time_type = 'happen_time';
        }else if($data['type'] == 2){
            $sql = "SELECT
                        date_format( happen_time, '%Y-%m' ) time,
                        sum(fire_area) fire_area
                    FROM
                        tb_fire_finish
                    WHERE
                        date_format( finish_time, '%Y-%m' ) BETWEEN :begin_time AND :end_time 
                        AND delete_time IS NULL ";
        }else if($data['type'] == 3){
            $sql = "SELECT
                        date_format( happen_time, '%Y-%m' ) time,
                        sum(death_people) death_people
                    FROM
                        tb_fire_finish
                    WHERE
                        date_format( finish_time, '%Y-%m' ) BETWEEN :begin_time AND :end_time 
                        AND delete_time IS NULL ";
        }else if($data['type'] == 4){
            $sql = "SELECT
                        date_format( happen_time, '%Y-%m' ) time,
                        sum(bruise_people) bruise_people
                    FROM
                        tb_fire_finish
                    WHERE
                        date_format( finish_time, '%Y-%m' ) BETWEEN :begin_time AND :end_time 
                        AND delete_time IS NULL ";
        }else if($data['type'] == 5){
            $sql = "SELECT
                        date_format( happen_time, '%Y-%m' ) time,
                        sum(financial_loss) financial_loss
                    FROM
                        tb_fire_finish
                    WHERE
                        date_format( finish_time, '%Y-%m' ) BETWEEN :begin_time AND :end_time 
                        AND delete_time IS NULL ";
        }
        $result = array();
        foreach ($data['region'] as $key => $value){
            $result_name = RegionModel::get($value);
            if (empty($result_name)) return Errors::DATA_NOT_FIND;
            $sqls = $sql." AND region like :region GROUP BY date_format( $time_type, '%Y-%m' )";
            $query = Db::query($sqls,['begin_time'=>$data['begin_time'],'end_time'=>$data['begin_time'],'region'=>$value."%"]);
            if (empty($query)) continue;
            $result[] = ['city'=>$result_name->name,'data'=>$query];
        }
        return [true,$result];
    }

    function queryFireHeatMap($region){
        if(Common::authRegion($region)) return [false,'无法查询其他区域的火情热力图'];
        $querySql='
            SELECT
                SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(positions, ";", 1),",",1),2) longitude,
                SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(positions, ";", 1),",",-1),")",1) latitude
            FROM
                tb_fire_upload
            where
                region like '.$region.'%';
        $this_month = 'and where date_format(happen_time, "%Y%m") = date_format(now() , "%Y%m")';
        $last_month = 'and where PERIOD_DIFF( date_format( now() , "%Y%m" ) , date_format( happen_time, "%Y%m" ) ) =1';
        $this_query = Db::query($querySql.$this_month);
        $last_query = Db::query($querySql.$last_month);
        return !empty($this_query)||!empty($last_query) ? [true , ['this'=>$this_query,'last'=>$last_query]] : Errors::DATA_NOT_FIND;
    }


//    private static function fire_push($push){
//        // 建立socket连接到内部推送端口
//        $client = stream_socket_client('tcp://127.0.0.1:5678', $errno, $errmsg, 1);
//        // 推送的数据，包含uid字段，表示是给这个uid推送
//        //$data = array('accept'=>'all', 'percent'=>$id , 'region'=>$region , 'type'=>'fire');
//        // 发送数据，注意5678端口是Text协议的端口，Text协议需要在数据末尾加上换行符
//        fwrite($client, json_encode($push)."\n");
//        // 读取推送结果
//        echo fread($client, 8192);
//    }
}