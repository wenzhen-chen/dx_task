<?php
/**
 * 文件上传基类
 */

namespace app\common\business\upload;
class BaseUpload
{
    protected $fileField; //文件域名
    protected $file; //文件上传对象
    protected $base64; //文件上传对象
    protected $oriName; //原始文件名
    protected $fileName; //新文件名
    protected $filePath; //完整文件名,即根目录开始的文件名
    protected $fullName; //相对文件名,即项目下的文件名，给web访问的文件名
    protected $fileSize; //文件大小
    protected $fileType; //文件类型
    protected $stateInfo; //上传状态信息,
    protected $rename = false;//是否重命名
    protected $remoteSpace;//第三方存储名称，如upyun：又拍云
    //1、大小限制 限制2M
    protected $maxSize = 2 * 1024 * 1024;
    //2、存储地址
    protected $savePath = '/uploads/';
    //3、格式限制
    protected $allowType = ['.jpg', '.gif', '.jpeg', '.x-png', '.png', '.pjpeg'];
    //4、状态映射
    protected $statusMap = [
        "SUCCESS" => 1000,//成功
        "ERROR_TYPE" => -1001,//格式不正确
        "ERROR_HTTP_LINK" => -1002,//链接不是http链接
        "OUT_SIZE" => -1003,//文件大小超出
        "SAVE_FAIL" => -1004,//保存失败
        "ERROR_CREATE_DIR" => -1005,//创建文件夹失败
        "ERROR_DIR_NOT_WRITE" => -1006,//无写入权限
        "ERROR_WRITE_CONTENT" => -1007,//文件写入失败
        "ERROR_FILE_NOT_FOUND" => -1008,//文件不存在
        "ERROR_TMP_FILE_NOT_FOUND" => -1009,//找不到临时文件
        "ERROR_TMP_FILE" => -1010,//非HTTP上传

    ];

    /**
     * BaseUpload constructor.
     * @param string $fileField 表单名称
     * @param string $type 上传类型 base64，默认二进制
     * @param string $remote_space 远程空间名称 默认又拍云
     */
    public function __construct($fileField, $type = "", $remote_space = 'uploadUpYun')
    {
        $this->fileField = $fileField;
        if ($type == "base64") {
            $this->upBase64();
        } else {
            $this->upFile();
        }
        $this->remoteSpace = $remote_space;
    }

    /**
     * 1、上传错误检查
     * @param $errCode
     * @return string
     */
    protected function getStateInfo($errCode)
    {
        return $this->statusMap[$errCode];
    }

    /**
     * 2、获取文件扩展名
     * @return string
     */
    protected function getFileExt()
    {
        return strtolower(strrchr($this->oriName, '.'));
    }

    /**
     * 3、获取文件名
     * @return string
     */
    protected function getFileName()
    {
        if ($this->rename) {
            $format = md5($this->oriName . time());
        } else {
            //1、过滤文件名的非法字符
            $oriName = substr($this->oriName, 0, strrpos($this->oriName, '.'));
            $format = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
        }
        $ext = $this->getFileExt();
        return $format . $ext;
    }

    /**
     * 4、获取文件完整路径
     * @return string
     */
    protected function getFilePath()
    {
        $rootPath = $_SERVER['DOCUMENT_ROOT'];
        return $rootPath . $this->savePath . $this->fileName;
    }

    /**
     * 5、获取相对文件名
     * @return string
     */
    protected function getFullName()
    {
        return $this->savePath . $this->fileName;
    }

    /**
     * 6、文件类型检测
     * @return bool
     */
    protected function checkType()
    {
        return in_array($this->getFileExt(), $this->allowType);
    }

    /**
     * 7、文件大小检测
     * @return bool
     */
    protected function checkSize()
    {
        return $this->fileSize <= ($this->maxSize);
    }

    /**
     * 8、获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo()
    {
        /** 是否上传到远程 */
        if ($this->remoteSpace) {
            $func = $this->remoteSpace;
            $local_image_url = $this->fullName;
            $this->fullName = UploadRemote::$func($local_image_url);
        }
        return
            [
                "status" => $this->stateInfo,
                "url" => $this->fullName,
                "type" => $this->fileType,
                "size" => $this->fileSize
            ];
    }

    /**
     * 第一种：base64上传
     */
    protected function upBase64()
    {
        $base64Data = $_POST[$this->fileField];
        $img = base64_decode($base64Data);
        $this->rename = true;
        $this->oriName = 'scrawl.png';
        $this->fileSize = strlen($img);
        $this->fileType = $this->getFileExt();
        $this->fileName = $this->getFileName();
        $this->filePath = $this->getFilePath();
        $this->fullName = $this->getFullName();
        $dirname = dirname($this->filePath);

        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("OUT_SIZE");
            return;
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITE");
            return;
        }
        //移动文件
        if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
        } else { //移动成功
            $this->stateInfo = $this->getStateInfo('SUCCESS');
        }

    }

    /**
     * 第二种：二进制上传
     */
    protected function upFile()
    {
        $file = $this->file = $_FILES[$this->fileField];
        //1、文件不存在
        if (!$file) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return;
        }
        //2、文件错误
        if ($this->file['error']) {
            $this->stateInfo = $this->getStateInfo('ERROR_FILE');
            return;
        } else if (!file_exists($file['tmp_name'])) {
            //3、找不到临时文件
            $this->stateInfo = $this->getStateInfo("ERROR_TMP_FILE_NOT_FOUND");
            return;
        } else if (!is_uploaded_file($file['tmp_name'])) {
            //4、非http上传
            $this->stateInfo = $this->getStateInfo("ERROR_TMP_FILE");
            return;
        }
        $this->oriName = $file['name'];
        $this->fileSize = $file['size'];
        $this->fileType = $this->getFileExt();
        $this->fileName = $this->getFileName();
        $this->filePath = $this->getFilePath();
        $this->fullName = $this->getFullName();
        $dirname = dirname($this->filePath);
        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("OUT_SIZE");
            return;
        }

        //检查是否不允许的文件格式
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE");
            return;
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITE");
            return;
        }
        //移动文件
        if (!(move_uploaded_file($file["tmp_name"], $this->filePath) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
        } else { //移动成功
            $this->stateInfo = $this->getStateInfo("SUCCESS");
        }
    }

}