<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/16
 * Time: 15:38
 */

namespace app\fire\validate;


class Upload extends BaseValidate
{
    protected $rule = [
        'image' => 'file|fileMime:image/png,image/jpg,image/jpeg|fileSize:2097152',
        'video' => 'file|fileExt:mp4|fileSize:209715200',
        'zip' => 'file|fileExt:zip'
    ];

    protected $message = [
        'image.file' => '必须是文件类型',
        'image.fileMime' => '文件类型必须是图片',
        'image.fileExt' => '文件后缀必须是图片格式',
        'image.fileSize' => '图片大小不能超过2M',
        'video.file' => '必须是文件类型',
        'video.fileExt'     => '文件后缀必须是MP4格式',
        'video.fileSize'     => '文件大小不能超过200M',
        'zip.file' => '必须是文件类型',
        'zip.fileExt'     => '文件后缀必须是ZIP格式',
    ];

    protected $scene = [
        'image'=>['image'],
        'video'=>['video'],
        'zip'=>['zip'],
    ];
}