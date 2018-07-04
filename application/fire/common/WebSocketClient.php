<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/22
 * Time: 11:47
 */
namespace app\fire\common;

use app\fire\controller\Common;
use think\worker\Server;

class WebSocketClient extends Server {
    public $collection=[];
    public $socket_obj=[];
    public $is_first=false;
    protected $socket = 'http://192.168.8.56:8686';
    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {
        if($this->is_first)
        $json=json_encode($data);
        $this->socket_obj['uid']=$json['uid'];
        $this->socket_obj['region']=$json['region'];

        $connection->send('我收到你的信息了');
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
        $this->is_first=true;
        $returnMsg['type']=0;
        $returnMsg['msg']='连接成功';
        $connection->send(json_encode($returnMsg));
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {

    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {

    }
}