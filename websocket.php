<?php


use Workerman\Worker;
require_once './vendor/workerman/workerman/Autoloader.php';
require_once './vendor/workerman/mysql/src/Connection.php';

// 初始化一个worker容器，监听1234端口
$worker = new Worker('websocket://0.0.0.0:1234');
// 这里进程数必须设置为1
$worker->count = 1;
// worker进程启动后建立一个内部通讯端口
$worker->onWorkerStart = function($worker)
{
    // 将db实例存储在全局变量中(也可以存储在某类的静态成员中)
    global $db;
    $db = new Workerman\MySQL\Connection('192.168.8.56', '3306', 'root', '123456', 'fire_service');
    // 开启一个内部端口，方便内部系统推送数据，Text协议格式 文本+换行符
    $inner_text_worker = new Worker('Text://0.0.0.0:5678');
    $inner_text_worker->onMessage = function($connection, $buffer)
    {
        global $worker;
        // $data数组格式，里面有uid，表示向那个uid的页面推送数据
        $data = json_decode($buffer, true);
        $accept = $data['accept'];
        if($accept == 'all'){//推送全部
            $ret = broadcast($data['region'],$buffer);
        }else if($accept == 'one'){//单点推送
            $ret = sendMessageByUid($data['to_uid'],$buffer);
        }
        // 返回推送结果
        $connection->send($ret);
    };
    $inner_text_worker->listen();
};
// 新增加一个属性，用来保存uid到connection的映射
$worker->uidConnections = array();
// 当有客户端发来消息时执行的回调函数
$worker->onMessage = function($connection, $data)use($worker)
{
    //$connection -> send($connection->uid.'连接成功，您的区域是:'.$connection->region);
    // 判断当前客户端是否已经验证,既是否设置了uid
    if(!isset($connection->uid))
    {
        global $db;
        $region = $db->select('region')->from('tb_user')->where('uid= :uid')->bindValues(array('uid'=>$data))->single();
        if(empty($region)){
            $connection->send('找不到用户数据，连接失败');
        }
        // 验证的把第一个包当做uid
        $connection->uid = $data;
        $connection->region = $region;
        /* 保存uid到connection的映射，这样可以方便的通过uid查找connection，
         * 实现针对特定uid推送数据
         */
        $worker->uidConnections[$connection->uid] = $connection;
        $connection->send(true);
    }
};

// 当有客户端连接断开时
$worker->onClose = function($connection)use($worker)
{
    global $worker;
    $connection->send('连接断开');
    if(isset($connection->uid))
    {
        // 连接断开时删除映射
        unset($worker->uidConnections[$connection->uid]);
    }
};

// 向所有验证的用户推送数据
function broadcast($region,$message)
{
    global $worker;
    foreach($worker->uidConnections as $key => $connection)
    {
        if(strlen($connection->region) <= strlen($region)){
            if(substr($region,0,strlen($connection->region)) ==$connection->region ){
                $connection->send($message);
            }
        }else{
            if(substr($connection->region,0,strlen($region)) ==$region ){
                $connection->send($message);
            }
        }
    }

}

// 针对uid推送数据
function sendMessageByUid($uid,$data)
{
    global $worker;
    if(isset($worker->uidConnections[$uid]))
    {
        $connection = $worker->uidConnections[$uid];
        $connection->send($data);
        return true;
    }
    return false;

}

// 运行所有的worker（其实当前只定义了一个）
Worker::runAll();