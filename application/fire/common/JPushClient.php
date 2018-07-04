<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/12
 * Time: 10:35
 */
namespace app\fire\common;
use app\fire\controller\Errors;
use JPush\Client;
use think\Exception;

/**
 * php消息推送工具类
 * Class JPushClient
 * @package app\fire\common
 */
class JPushClient{
    private $app_key; //极光的app_key
    private $master_secret;  //极光的master_secret
    private $url = "https://api.jpush.cn/v3/push";
    private $client;

    public function __construct($app_key=null,$master_secret=null,$url=null)
    {
       if($app_key) $this->app_key=$app_key;
       if($master_secret) $this->master_secret=$master_secret;
        $this->url=empty($url)?$this->url:$url;
        $this->client=new Client($this->app_key,$this->master_secret);
    }

    public function push($receiver='all',$title,$message,$m_time=86400){
        try{
            global $res;  //定义消息返回内容
            $jg_push = $this->client->push()
                ->setPlatform('all');  //设置发送消息平台
            if(is_string($receiver)&&strcasecmp($receiver,'all')==0){ //如果是all，则发送所有人
                $res=$jg_push->addAllAudience()
                ->setNotificationAlert($message);
            }elseif (!empty($receiver)){  //如果是数组则发送指定用户数组
                $res=$jg_push->addAlias($receiver)
                    ->setNotificationAlert($message);
            }
            var_dump($res);
            $res=$res->addAndroidNotification($message,$title)->
            options(array(
                'time_to_live'=>$m_time,  //离线消息的时长
            ))->send();
            if($res){       //得到返回值--成功已否后面判断
                return $res;
            }else{          //未得到返回值--返回失败
                return false;
            }
        }catch (Exception $exception){
            return Errors::Error($exception->getMessage());
        }
    }

}