<?php
/**
 * 图片上传类
 */
namespace app\common\business\upload;
class UploadImage extends BaseUpload
{

    /**
     * 构造函数
     * UploadImage constructor.
     * @param $fileField
     * @param string $type
     * @param string $remote_space
     */
    public function __construct($fileField, $type = "",$remote_space = '')
    {
        //1、重写存储路径
        $this->savePath = $this->savePath . date('Ymd' ,time()).'/';
        $this->rename = true;
        parent::__construct($fileField, $type,$remote_space);
    }
}