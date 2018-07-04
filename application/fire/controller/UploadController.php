<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/25
 * Time: 15:46
 */

namespace app\fire\controller;


use app\fire\logic\UploadLogic;
use think\Controller;
use think\Error;
use think\facade\Request;
use ZipArchive;

class UploadController extends Controller
{
    /**
     * 上传方法
     * @return string|\think\response\Json
     */
    function fileUpload(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $data = Common::getPostJson();
        $file = request()->file('file');
        if (empty($file)) return Common::reJson(Errors::ATTACH_NOT_FIND);
        if (($data['file_ext'] == 'zip' || $data['file_ext'] == 'video' || $data['file_ext'] == 'other') ){
            if (count($file) > 1)  return Common::reJson([false,'上传数量错误']);
        }else{
            if (count($file) > 5)  return Common::reJson([false,'上传数量错误']);
        }
        foreach ($file as $key => $value){
            $result = $this->validate([$data['file_ext']=>$value], 'Upload.'.$data['file_ext']);
            if ($result !== true) return Common::reJson(Errors::Error($result));
        }
//        if (Common::isWhere($data,'uid')) {
//            $dbRes = UploadLogic::saveFile($data,$file,$data['uid']);
//        }else{}
        $dbRes = UploadLogic::saveFile($data,$file,$auth[1]['s_uid']);
        return Common::reJson($dbRes);
    }

    /**
     * 用户头像图片上传方法
     * @return string|\think\response\Json
     */
    function userImageUpload(){
        $file = request()->file('user_image');
        $result = UploadLogic::saveUserImage($file);
        return Common::reJson($result);
    }


    function openZip(){
        $dir="D:\\WorkSpace\\fire\\public\\uploads\\fire_upload\\extract\\0_1528794669_6d76c931af1c7143240e50d9007a9676";
        return json_encode(UploadLogic::openZip($dir));
    }
}