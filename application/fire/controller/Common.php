<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/4
 * Time: 16:03
 */

namespace app\fire\controller;

use app\fire\model\AuthModel;
use app\fire\model\RegionModel;
use app\fire\model\UserModel;
use think\Controller;
use think\Exception;
use think\facade\Cache;
use think\facade\Request;
use think\facade\Session;
use think\Validate;
class Common extends Controller
{
    /**
     * 获取数据
     */
    static function getPostJson()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: OPTIONS, GET, POST');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        header('Access-Control-Allow-Credentials:true');
        $data = input('post.');
        return $data;
    }
    /**
     * 返回数据
     */
    static function reJson($res, $isTxt = false)
    {

        $res = self::reJson2($res);
        return $isTxt ? json_encode($res) : json($res);
    }
    private static function reJson2(array $res)
    {
        if (empty($res)) $res = Errors::Error('后端错误');
        return ['code' => $res[0] ? 's_ok' : 'error', 'var' => $res[1]];
    }
    /**
     * 唯一32位字符串
     */
    static function uniqStr()
    {
        return md5(uniqid(mt_rand(), true));
    }
    /**
     * 盐值
     */
    static function getRandChar($length)
    {
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) $str .= $strPol[rand(0, $max)];
        return $str;
    }
    /**
     * 是否登录以及管理员判断
     * $pids 管理员编号
     */
    static function auth($pid = null)
    {
        $url_token = Request::param('s_token');
        $data = Cache::get($url_token);
        if(empty($data) ) return Errors::AUTH_FAILED;
        $msg = [
            's_uid.require' => "auth uid require",
            's_uid.length' => "auth uid length 32",
            's_token.require' => "auth token require",
            's_token.length' => "auth token length 32",
            's_client.require' => "auth client require",
            's_client.in' => "auth client in:1,2,3,4,5"
        ];
        $validate = new Validate([
            's_uid' => 'require|length:32',
            's_token' => 'require|length:32',
            's_client'=>'require|in:1,2,3,4,5'
        ], $msg);
        if (!$validate->check($data)) return Errors::Error($validate->getError(), "身份认证错误,请重新登录");
        $auth = AuthModel::where('uid', $data['s_uid'])
                    ->where('s_token', $data['s_token'])
                    ->where('client', $data['s_client'])->field('s_update_time')->find();
        if (!empty($auth)) {
            $distance = time() - strtotime($auth['s_update_time']);
            if ($distance > 7 * 24 * 60 * 60) return Errors::AUTH_EXPIRED;
            //查找权限返回,根据uid查权限
            if (empty($pid)) return [true , $data];
            return $pid != $data['s_role'] ? [true , $data] : Errors::AUTH_PREMISSION_REJECTED;
        }
        return [false,"身份认证失败,请重新登录"];
    }

    /**
     * 区域公共判断接口
     * @param $region
     * @return bool
     */
    static function authRegion($region,$s_region){
        //传递的参数区域必须属于当前用户的区域范围内
        if(substr($region,0,strlen($s_region)) == $s_region){
            return true;
        };
        return false;
    }

    /**
     * 鍵值是否存在
     * @param $data
     * @param $key
     * @return bool
     */
    static function isWhere($data, $key)
    {
        return array_key_exists($key, $data) && (!empty($data[$key] or $data[$key] === 0 or $data[$key] === '0'));
    }

    /**
     * 图片上传
     * @param $file 文件
     * @param $folder 路径
     * @param $preName 文件名
     * @return array
     */
    static function upload($file, $folder, $preName)
    {
        $info = $file->move($folder,$preName,true,false);
        if (!$info) return Errors::FILE_SAVE_ERROR;
        return [true , $preName];
    }

    /**
     * 图片校验
     * @param $imageCount
     * @param $image
     * @return array
     */
    static function checkImage($imageCount, $image)
    {
        if ($imageCount > 5) return Errors::IMAGE_COUNT_ERROR;
        if (empty($image)) return Errors::IMAGE_NOT_FIND;
        if (!$image->checkImg()) return Errors::FILE_TYPE_ERROR;
        if (!$image->checkSize(2 * 1024 * 1024)) return Errors::IMAGE_FILE_SIZE_ERROR;
        return [true ];
    }

    /**
     * @param $image 图片
     * @param $folder 路径
     * @param $file_name 图片名
     * @return array
     */
    static function uploadImage($image, $folder, $file_name)
    {
        $a = self::checkImage(0, $image);
        if (!$a[0]) return $a;
        $ext = substr(strrchr($image->getinfo()['name'],'.'),1);
        $preName =  $file_name.'.'. $ext;
        return self::upload($image,$folder, $preName);
    }

    /**
     * accept   all / one
     * percent  推送的事件id
     * region   区域
     * type     推送事件的类型   fire:火情   task:任务
     * @param $push
     * @return bool|string
     */
    static function fire_push($push){
        // 建立socket连接到内部推送端口
        $client = stream_socket_client('tcp://127.0.0.1:5678', $errno, $errmsg, 1);
        // 推送的数据，包含uid字段，表示是给这个uid推送
        //$data = array('accept'=>'all', 'percent'=>$id , 'region'=>$region , 'type'=>'fire');
        // 发送数据，注意5678端口是Text协议的端口，Text协议需要在数据末尾加上换行符
        fwrite($client, json_encode($push)."\n");
        // 读取推送结果
        return fread($client, 8192);
    }

    public function ListRegion()
    {
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $region = $auth[0]['s_region'];
        $dbRes=RegionModel::field("id as  value, name as label , parentId ")
            ->whereLike('id','43%')->select();
        return json($this->list_to_tree($dbRes,$region));
    }

    public function list_to_tree($list,$region, $pk='value',$pid = 'parentId',$child = 'children',$root=0) {
        // 创建Tree
        $tree = array();
        if(is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                if(strlen($data['value']) <= strlen($region)){
                    if ($data['value'] != substr($region,0,strlen($data['value']))){
                        $list[$key]['disabled'] = true;
                    }
                }else{
                    if ($region != substr($data['value'],0,strlen($region))){
                        $list[$key]['disabled'] = true;
                    }
                }
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    static function removeEmpty($tbRes){
        array_walk_recursive($tbRes, function (& $val, $key ) {
            if ($val === null) {
                $val = '';
            }
        });
        return $tbRes;
    }

    /**
     * 公共权限的判断  除用户管理外
     */
    static function authLevel($data,$auth){
        //信息为自己的，跳过验证
        if($data['user_id'] == $auth['s_uid']) return [true,''];
        //获得当前用户角色
        $user_level = RegionModel::get($auth['s_region'])['level'];
        //超级管理员不进行判断
        if($auth['s_role'] == 3) {
            return [true];
        }elseif ($auth['s_role'] == 2){//管理员
            //获得这条信息的所有人信息
            $data_detail = UserModel::alias('u')
                        ->join('tb_region r','u.region = r.id')
                        ->join('tb_user_role ur','u.uid = ur.uid')
                        ->where('u.uid',$data['user_id'])
                        ->field('ur.rid,r.level,r.region')
                        ->find();
            if(!Common::authRegion($data['region'],$auth['s_region'])) return Errors::AUTH_PREMISSION_REJECTED;
            if (empty($data_detail)) return [ false  , ["网络错误,找不到该用户数据"]];

            if($data_detail['rid'] == 2){//信息的所有人为普通用户
                return [true,''];
            }elseif ($data_detail['rid'] == 1){//信息的所有人为管理员
                if($data_detail['level'] > $user_level){
                    return [true];
                }
            }
        }
        return [false,"权限拒绝"];
    }
    /**
     * 读取excel文件
     * @param $filename
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    static function read_excel($filename)
    {
        //设置excel格式
        $reader = \PHPExcel_IOFactory::createReader('Excel5');
        //载入excel文件
        $excel = $reader->load($filename);
        //读取第一张表
        $sheet = $excel->getSheet(0);
        //获取总行数
        $row_num = $sheet->getHighestRow();
        //获取总列数
        $col_num = $sheet->getHighestColumn();

        $data = []; //数组形式获取表格数据
        for($col='A';$col<=$col_num;$col++)
        {
            //从第二行开始，去除表头（若无表头则从第一行开始）
            for($row=2;$row<=$row_num;$row++)
            {
                $data[$row-2][] = $sheet->getCell($col.$row)->getValue();
            }
        }
        return $data;
    }

    /**
     * 将经纬度的度分秒形式转成地理位置
     * @param $str
     * @return float|int
     */
    static function parseString($str){
        $du1=explode("°",$str);
        $du=$du1[0];
        $dus=explode('′',$du1[1]);
        $fen=$dus[0];
        $miao=str_replace('""','',$dus[1]);
        $miao=substr($miao,0,strlen($miao)-1);
        return $du+($fen/60)+$miao/60/60;
    }

    static function createSystemDate(){
        return date('y-m-d H:i:s',time());
    }

    // 定义一个函数获取客户端IP地址
    static function getIP(){
        global $ip;
        if (getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if(getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if(getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else $ip = "Unknow IP";
        return $ip;
    }

    /**
     * PHP计算两个时间段是否有交集（边界重叠不算）
     *
     * @param string $beginTime1 开始时间1
     * @param string $endTime1 结束时间1
     * @param string $beginTime2 开始时间2
     * @param string $endTime2 结束时间2
     * @return bool
     */
   static function is_time_cross($beginTime1 = '', $endTime1 = '', $beginTime2 = '', $endTime2 = '') {
       $status = $beginTime2 - $beginTime1;
       if($status>0){
           $status2 = $beginTime2 - $endTime1;
           if($status2>0){
               return false;
           }else{
               return true;
           }
       }else{
           $status2 = $beginTime1 - $endTime2;
           if($status2>0){
               return false;
           }else{
               return true;
           }
       }
   }

    /**
     * 验证url是否有权限，已弃用
     * @param $base_url
     * @param $data
     * @return array
     */
    static function authUrl($base_url,$data){
        $intercept_model=['fire', //火情模块
            'fire_office', //防火办公室
            'floor_monitor',//地面监控点
            'history_video',//历史视频
            'hot','on_work',//热点，打卡
            'region','report_data','task' //区域，上报数据，任务
        ];
        $intercept_update_method=['edit','save','delete','remove','add','update'];
        $intercept_select_method=['get','select','find'];
        $url_array=explode('/',$base_url);

        foreach($intercept_model as $model){
            if(strcasecmp($url_array[2],$model)==0){
                global $auth;
                foreach ($intercept_update_method as $method){ //验证更新方法，用管理员进行判断
                    if(strpos($url_array[3],$method)===0){
                        $auth=Common::auth(1);
                        if(!$auth[0]) return $auth;
                    }
                }
                foreach ($intercept_select_method as $value){
                    if(strpos($url_array[3],$value)===0){
                        $auth=Common::auth();
                        if(!$auth[0]) return $auth;
                    }
                }
                if(Common::isWhere($data,'region')){ //如果参数中有region，则进行区域验证
                    if(!Common::authRegion($data['region'],$auth[1]['s_region']))
                        return [false,'您操作的区域与您所在的区域不一致'];
                }

            }
        }
    }


    static function changeHtml($content,$uid,$path=null){
        $root_path = ROOT_PATH . 'public' . DS . 'uploads' . DS;
        if (empty($path))
            $path =  'typical_case' . DS .strtotime('now').'_'.$uid.'.html';
        $my_file = fopen($root_path.$path, "w") or die("Unable to open file!");
        $txt = $content."\n";
        fwrite($my_file, $txt);
        fclose($my_file);
        return $path;

    }

    static function getMessageByTask($task){
        global $region,$region_name;
        $task=Common::removeEmpty($task);
        $add_time=$task['task_add_time'];
        if(Common::isWhere($task,'task_region')) $region=RegionModel::getRegionNameById($task['task_region']);
        if($region[0]) $region_name=$region[1]['region_name'];
        return $add_time.' '.$region_name.'发生火灾，请立即派人前往灭火!';
    }

}