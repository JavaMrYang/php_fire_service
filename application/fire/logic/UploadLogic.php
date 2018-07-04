<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/25
 * Time: 15:48
 */

namespace app\fire\logic;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use think\Db;
use think\Exception;
use think\facade\Validate;
use ZipArchive;

class UploadLogic
{
    /**
     * @param $data 文件上传路径和文件类型
     * @param $files 上传的文件
     * @param $uid 上传文件的用户id
     * @return array
     */
    static function saveFile($data,$files,$uid,$status=0){
        $fire_path = ROOT_PATH . 'public' . DS . 'uploads';
        if ($data['path'] == null || $data['file_ext'] == null) return Errors::IMAGES_INSERT_ERROR;
        $path = $fire_path .DS. $data['path'] .DS. $data['file_ext'];
        $upload_name =[];
        foreach ($files as $key => $file){
            if($path == null) return Errors::IMAGES_INSERT_ERROR;
            //文件名统一采用   时间戳 + 上传用户的uid
            $file_name = $key.'_'.strtotime('now').'_'.$uid;
            $upload_result = $file->move($path,$file_name,true,false);
            $fireName = $data['path'] .DS. $data['file_ext'] . DS . $upload_result->getFilename();
            if($data['file_ext'] == 'zip'){
                // 保存的文件夹名称
                $folder_name = $fire_path . DS . $data['path'] . DS . 'extract' .DS . $file_name;
                self::unZip($folder_name,$fireName);
                $folderName = $data['path'] . DS . 'extract' .DS . $file_name;
                $fireName = [$folderName,$fireName];
            }
            if($upload_result){
                array_push($upload_name,$fireName);
            }
        }
        try{
            Db::startTrans();
            $upload_path = [];
            if ($data['file_ext'] != 'zip'){
                foreach ($upload_name as $value){
                    $query = Db::table("tb_file_".$data['file_ext'])->insertGetId(['path'=>$value,'status'=>$status,"create_time" => date('Y-m-d H:i:s', time())]);
                    array_push($upload_path,$query);
                }
            }else{
                $query = Db::table('tb_file_zip')->insert(['folder_path'=>$upload_name[0][0],'file_path'=>$upload_name[0][1],'status'=>$status,"create_time" => date('Y-m-d H:i:s', time())]);
                array_push($upload_path,$query);
            }
            Db::commit();
            return [true , $upload_path];
        }catch (Exception $exception){
            Db::rollback();
            return [false,'上传失败'];
        }
    }

    static function saveUserImage($file){
        if (count($file)>2) return Errors::IMAGE_COUNT_ERROR;
        $check_result = self::checkImage($file);
        if (!$check_result[0]) return $check_result;
        $upload_name =[];
        $fire_path = ROOT_PATH . 'public' . DS . 'uploads' . DS .'user_image';
        $file_name = Common::uniqStr();
        $upload_result = $file->move($fire_path,$file_name,true,false);
        if($upload_result){
            array_push($upload_name,$upload_result->getFilename());
        }
        return [true , $upload_name];
    }


    /**
     * 图片校验
     * @param $image
     * @return array
     */
    private static function checkImage($image)
    {
        if (empty($image)) return Errors::IMAGE_NOT_FIND;
        if (!$image->checkImg()) return Errors::FILE_TYPE_ERROR;
        if (!$image->checkSize(2 * 1024 * 1024)) return Errors::IMAGE_FILE_SIZE_ERROR;
        return [true];
    }

    private static function unZip($folder_name,$fireName){
        $zip = new ZipArchive();
        $zip_path = ROOT_PATH . 'public' . DS . 'uploads' . DS . $fireName ;
        $res = $zip->open($zip_path);
        if ($res){
            $zip->extractTo( $folder_name);
            $zip->close();
            return true;
        }else{
            return false;
        }
    }

    static function openZip($dir){
        $path = self::listAllFiles($dir);
        $result = array();
        foreach ($path as $file){
            // 获得文件内容
            $xml = simplexml_load_file($file);
            // 读取document标签
            $values = $xml->Document->Folder->GroundOverlay;
            $latLonAltBox = $values->LatLonBox;
            $icon = $values->Icon;
            $href=(String)$icon->href;
            $href = str_replace('../..',$dir,$href);
            $xml_result = [
                'north'=>(string)$latLonAltBox->north,
                'south'=>(string)$latLonAltBox->south,
                'east'=>(string)$latLonAltBox->east,
                'west'=>(string)$latLonAltBox->west,
                'href'=>$href
            ];
            $result[] = $xml_result;
        }
        return $result;
    }

    private static function listAllFiles($dir){
        $files=array();
        $queue=array($dir);
        while($data=each($queue)){
            $path=$data['value'];
            if(is_dir($path) && $handle=opendir($path)){
                while($file=readdir($handle)){
                    if($file=='.'||$file=='..') continue;
                    $real_path=$path.'/'.$file;
                    if (substr($file,-3) == 'kml'){
                        $files[] = $real_path;
                    }
                    if (is_dir($real_path)) $queue[] = $real_path;
                }
            }
            closedir($handle);
        }
        return $files;
    }
}