<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/30
 * Time: 10:43
 */
namespace app\fire\controller;



use app\fire\logic\HlyLatLngLogic;
use app\fire\logic\UserLogic;
use app\fire\model\location\DroneRunInfoModel;
use app\fire\model\location\FireLatLngModel;
use app\fire\model\location\HlyLatLngModel;
use app\fire\model\UserModel;
use think\Controller;
use think\Exception;
use think\facade\Cache;

class ReceiveAppInfoController extends Controller{
    /**
     * 接受app消息
     */
    function appInfos(){
        try{
            global $result; //定义返回结果集
            $drone_state=[]; //定义app上报的数组
            $drone=[];//定义无人机存储的数组
            $fire=[]; //定义消防员存储的数组
            $hly=[]; //定义护林员存储的数组
            $drone_state_list=[]; //定义redis缓存存储的数组
            $data=Common::getPostJson();
            if(gettype($data)=='string') $data=explode('&',$data);
            // 0飞机经度
            // 1飞机纬度
            // 2飞机偏航角
            // 3飞机相对高度
            // 4飞机起飞点纬度
            // 5飞机起飞点经度
            // 6推流名
            // 7手机纬度
            // 8手机经度
            // 9 转换后的经度
            // 10转换后的纬度
            // 11终端类型 0飞机直播 1手机森林防火直播 2手机护林员直播
            // 12 PM2.5
            // 13 PM10
            // 14 温度
            // 15 湿度
            // 16 甲醛
            // 17 TVOC
            $ip=Common::getIP();
            if(Common::isWhere($data,'appInfo')){
                $attr=explode('_',$data['appInfo']); //接收app端的信息

                $drone_state['ip']=$ip; //app端的IP
                $drone_state['homeLatitude']=$attr[4];//家纬度
                $drone_state['homeLongitude']=$attr[5]; //家经度
                $drone_state['altitude']=$attr[6]; //相对高度
                $drone_state['droneLatitude']=$attr[0]; //飞机纬度
                $drone_state['droneLongitude']=$attr[1];//飞机经度
                $drone_state['yaw']=$attr[2];//方位角
                $drone_state['publishName']=Helper_Spell::getChineseChar($attr[6],true);//推流名
                $drone_state['userLatitude']=$attr[7]; //手机端纬度
                $drone_state['userLongitude']=$attr[8];//手机端经度
                $drone_state['userDegree']=$attr[9];//中心点的维度
                $drone_state['userHeight']=$attr[10];//中心点的经度
                $drone_state['id']=Helper_Spell::getChineseChar($attr[6],true); //中文名用于显示
                $drone_state['liveModle']=trim($attr[11]); //直播模式  0飞机直播  1手机直播
                $drone_state['pm25']=$attr[12];//pm2.5
                $drone_state['pm10']=$attr[13];//pm10
                $drone_state['wendu']=$attr[14];//温度
                $drone_state['shidu']=$attr[15].'%'; //湿度
                $drone_state['jiaquan']=$attr[16];//甲醛
                $drone_state['tvoc']=$attr[17];
                $drone_state['isPublish']=$attr[18]; //是否在推流
                $drone_state['onLineTime']=Common::createSystemDate(); //上线时间
                $drone_state['city']=Common::isWhere($data,'city')?$data['city']:null;
                $drone_state['town']=Common::isWhere($data,'town')?$data['town']:null;
                $drone_state['village']=Common::isWhere($data,'village')?$data['village']:null;
                if(Common::isWhere($drone_state,'publishName')){
                    $user=UserModel::getUserDetailByTel($drone_state['publishName']);
                    if($user[0]){
                      $drone_state['region_name']=$user[1]['region_name'];
                      $drone_state['groupId']=$user[1]['region'];
                      $drone_state['publish_uid']=$user[1]['uid'];
                    }
                }
                $redis=Cache::store('redis');
                if($redis->has('drone_list')) $drone_state_list= Cache::store('redis')->get('drone_list');
                if(!empty($drone_state)) {
                    array_push($drone_state_list,$drone_state);
                    $redis->set('drone_list',$drone_state_list,3600);
                }
               $name=$redis->has($drone_state['publishName']);
                if(!$name){
                    $user=UserModel::getUserDetailByTel($drone_state['publishName']);
                    if($user[0]){
                        $redis->set($drone_state['publishName'],$user[1]['region']);
                        $redis->set($user[1]['region'],[$drone_state['publishName']
                        =>$drone_state['publishName']]);
                    }
                }else{
                    $userLatLng=[
                        'lat'=>$drone_state['userLatitude'],
                        'lng'=>$drone_state['userLongitude'],
                        'yaw'=>$drone_state['yaw'],
                        'name'=>$drone_state['publishName'],
                        'socketConn'=>0,
                        'appType'=>trim($attr[11]),
                        'areaName'=>$drone_state['region_name'],
                        'isPublish'=>$drone_state['isPublish'],
                    ];
                    if($drone_state['liveModle']==0){
                        $redis->set($drone_state['publishName'],$userLatLng);
                    }else{
                       $redis->set($drone_state['publishName'],$userLatLng);
                    }
                }
                if($attr[19]%3==0&&$attr[19]!=0&&$drone_state['liveModle']==0){  //表示无人机上传
                    $drone['publish_uid']=$drone_state['publish_uid'];
                    $drone['app_run_time']=str_replace(',',' ', $attr[20]);
                    $drone['drone_lat']=$attr[0];
                    $drone['drone_lng']=$attr[1];
                    $drone['relativeHeight']=$attr[6];
                    $drone['moble_lat']=$attr[7];
                    $drone['moble_lng']=$attr[8];
                    $drone['speed']=empty($drone_state['speed'])?'':$drone_state['speed'];
                    $drone['yaw']=$drone_state['yaw'];
                    $drone['regionName']=$drone_state['region_name'];
                    $drone['region']=$drone_state['groupId'];
                    $drone['time']=date('y-m-d H:i:s',time());
                    $droneRunInfo=new DroneRunInfoModel();
                    $droneRunInfo->save($drone); //保存无人机轨迹
                }
                if(!empty($drone_state['liveModle'])&&$drone_state['liveModle']==1&&$attr[19]%10==0){ //表示消防员上传
                    $fire['height']=empty($data['height'])?null:$data['height'];
                    if(Common::isWhere($drone_state,'userLatitude')&&!empty($drone_state['userLatitude']))
                        $fire['lat']=$drone_state['userLatitude'];
                    if(Common::isWhere($drone_state,'userLongitude')&&!empty($drone_state['userLongitude']))
                        $fire['lng']=$drone_state['userLongitude'];
                    $fire['fire_uid']=empty($drone_state['publish_uid']);
                    $fire['tel']=$drone_state['publishName'];
                    $fire['time']=Common::createSystemDate();
                    $fire['city']=Common::isWhere($data,'city')?$data['city']:null;
                    $fire['town']=Common::isWhere($data,'town')?$data['town']:null;
                    $fire['village']=Common::isWhere($data,'village')?$data['village']:null;
                    $fire_latlng=new FireLatLngModel();
                    $fire_latlng->save($fire);  //保存消防员轨迹
                }

                if(Common::isWhere($data,'type')&&$data['type']==2){ //表示护林员轨迹
                    $hly['height']=empty($data['height'])?null:$data['height'];
                    $hly['city']=Common::isWhere($data,'city')?$data['city']:null;
                    $hly['town']=Common::isWhere($data,'town')?$data['town']:null;
                    $hly['village']=Common::isWhere($data,'village')?$data['village']:null;
                    if(Common::isWhere($drone_state,'userLatitude')&&!empty($drone_state['userLatitude']))
                        $hly['lat']=$drone_state['userLatitude'];
                    if(Common::isWhere($drone_state,'userLongitude')&&!empty($drone_state['userLongitude']))
                        $hly['lng']=$drone_state['userLongitude'];
                    $hly['time']=Common::createSystemDate();
                    $hly_latlng=new HlyLatLngModel();
                    $hly_latlng->save($hly);
                }
                $result=[];
                $user=UserModel::getUserDetailByTel($drone_state['publishName']);
                if($user[0]){
                   $nameArray= Cache::store('redis')->get($user[1]['region']);
                    if(!empty($nameArray)){
                        foreach (array_keys($nameArray) as $key){
                           $userPoint=Cache::store('redis')->get($key); //从缓存里面取出用户经纬度数组
                            if(!empty($userPoint)) array_push($result,$userPoint);
                        }
                    }
                }
            }
            return Common::reJson([true,$result]);
        }catch (Exception $e){
          return Errors::Error($e->getMessage());
        }
    }

    /**
     * 获取护林员历史轨迹
     * @return string|\think\response\Json
     */
    function getHlyPath(){
        $auth = Common::auth(1);
        if (!$auth[0]) return Common::reJson($auth);
        $data=Common::getPostJson();
        $dbRes=HlyLatLngLogic::getHlyPathByCondition($data);
        return Common::reJson($dbRes);
    }

    /**
     * 获取护林员实时位置
     * @return string|\think\response\Json
     */
   function getHlyLocation(){
       $data=Common::getPostJson();
       global $_result,$_hlyList;
       $count=UserLogic::countByMidTotal(2); //获取护林员总数
       if($count[0]) $_result['hlyTotal']=$count['count_mid'];
       $drone_list=Cache::store('redis')->get('drone_list'); //从缓存中获取app获取信息列表
       foreach ($drone_list as $d){
           if($d['liveModle']==2){ //如果liveModel等于2，代表护林员
                if(HlyLatLngLogic::filterHlyByCondition($data,$d)){
                    array_push($_hlyList,HlyLatLngLogic::getHlyByDroneList($d));
                }
           }
       }
       $_result['total']=sizeof($_hlyList);
       if(Common::isWhere($data,'pageNo')){  //如果传入pageNo,则对他进行分页
           $start_page=($data['pageNo']-1)*20;
           $_hlyList=array_slice($_hlyList,$start_page,20);
       }
       $_result['hly_list']=$_hlyList;
       return Common::reJson([true,$_result]);
   }



}