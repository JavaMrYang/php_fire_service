<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

// 加载基础文件
use app\fire\common\SocketService;
use app\fire\controller\Common;

require __DIR__ . '/../thinkphp/base.php';

// 支持事先使用静态方法设置Request对象和Config对象
/*$request=new Request();
$base_url=$request->baseUrl(); //获取基础url
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials:true');
$data=$request->post(); //获取post传值参数
$res= Common::authUrl($base_url,$data); //返回验证url信息
if (empty($res)) $res = Errors::Error('后端错误');*/
//$dbRes=['code' => $res[0] ? 's_ok' : 'error', 'var' => $res[1]];
//print json_encode($dbRes);

/*$socket=new SocketService();
$socket->start();*/
// 执行应用并响应
Container::get('app')->run()->send();

define('APP_DEBUG',true);
