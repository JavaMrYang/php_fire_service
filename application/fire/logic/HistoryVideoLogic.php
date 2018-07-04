<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/8
 * Time: 15:26
 */
namespace app\fire\logic;

use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\video\HistoryVideoModel;
use think\Db;
use think\Exception;

class HistoryVideoLogic{
   static function addHistoryVideo($data,$auth){
       if(!Common::authRegion($data['region'],$auth['s_region'])) return [false,'您不能添加其他区域的视频'];
       $history_video=new HistoryVideoModel();
       if(!Common::isWhere($data,'upload_time'))  //如果上传时间为空，则把当前系统时间赋值给他
           $data['upload_time']=Common::createSystemDate();
       $data['upload_uid']=$auth['s_uid'];
       $data['status']=1;
       $history_video->save($data);
       return empty($history_video)?Errors::ADD_ERROR:[true,$history_video['id']];
   }

   static function editHistoryVideo($data,$auth){
       $history=HistoryVideoModel::get($data['id']);
       if(empty($history)) return [false,'视频id传入有误，没有这条数据'];
       if(!Common::authRegion($history['region'],$auth['region'])) return Errors::REGION_PREMISSION_REJECTED;
       $history_video=new HistoryVideoModel();
       $history_video->allowField(true)->save($data,
           ['id'=>$data['id']]);
       return empty($history_video)?Errors::UPDATE_ERROR:[true,$history_video->toArray()];
   }

   static function getHistoryVideoById($id){
       try{
         $result=Db::table('tb_history_video')->alias('v')
             ->join('tb_region r','r.id = v.region')
             ->join('tb_region r1','r1.id = r.parentId','left')
             ->join('tb_region r2','r2.id = r1.parentId','left')
             ->join('tb_region r3','r3.id = r2.parentId','left')
             ->join('tb_region r4','r4.id = r3.parentId','left')
             ->join('tb_user u','v.upload_uid=u.uid','left')
             ->where('v.id',$id)
             ->field('v.*,r.`name` r,r1.`name` r1,r2.`name` r2,r3.`name` r3,r4.`name` r4
             ,u.name as upload_name,u.tel as upload_tel')->find();
         if(empty($result)) return Errors::DATA_NOT_FIND;
           $result=Common::removeEmpty($result);
           $result['region_name'] = $result['r4'].$result['r3'].$result['r2'].$result['r1'].$result['r'];
           unset($result['r']);
           unset($result['r1']);
           unset($result['r2']);
           unset($result['r3']);
           unset($result['r4']);
         return [true,$result];
       }catch (Exception $e){
           return Errors::Error($e->getMessage());
       }
   }

    static function getHistoryVideoByCondition($data,$auth){
        try{
            $query=Db::table('tb_history_video')->alias('v')
                ->join('tb_region r','r.id = v.region')
                ->join('tb_region r1','r1.id = r.parentId','left')
                ->join('tb_region r2','r2.id = r1.parentId','left')
                ->join('tb_region r3','r3.id = r2.parentId','left')
                ->join('tb_region r4','r4.id = r3.parentId','left')
                ->join('tb_user u','v.upload_uid=u.uid','left')
                ->where('v.status','<>','-1');
                if(Common::isWhere($data,'region')){
                    if(!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
                    $query->where('v.region','like','%'.$data['region'].'%');
                }else{
                    $query->whereLike('v.region','%'.$auth['s_region'].'%');
                }
            if(Common::isWhere($data,'video_type')){
                $query->where('video_type',$data['video_type']);
            }
            if(Common::isWhere($data,'start_time')){
                $query->where('upload_time','>=',$data['start_time']);
            }
            if(Common::isWhere($data,'end_time')){
                $query->where('upload_time','<=',$data['end_time']);
            }
            $query->field('v.*,u.name as addName,u.tel,r.`name` r,r1.`name` r1,r2.`name` r2,r3.`name` r3,r4.`name` r4')
                ->order('v.upload_time','desc')->group('v.id');
            $result=$query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
            if(empty($result['data'])) return [true, $result];
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
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

    static function deleteVideo($data,$auth){
        try {
            $video=HistoryVideoModel::get($data['id']);
            if(empty($video)) return Errors::DATA_NOT_FIND;
            if(!Common::authRegion($video['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
            $history_video=new HistoryVideoModel();
            $data['status']=-1;
            $history_video->allowField(true)->save($data,
                    ['id'=>$data['id']]);
            return empty($history_video)?Errors::DELETE_ERROR:[true,$history_video->toArray()];
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }
}