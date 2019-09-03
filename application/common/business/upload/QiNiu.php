<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/4
 * Time: 0:52
 */

namespace app\common\business\upload;

require_once PATH_TO_EXTEND . '/qiniu/autoload.php';

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class QiNiu
{
    protected $AccessKey = 'd4lNY9C7NoillJkSWU0EPuHyA9UgkysZfK7myhxs';
    protected $SecretKey = 'Umq3O9er1MLPasiDl4wM6vhYLkxB3L6_DkpFTSfc';
    protected $bucket = 'doujin';
    public $token = '';
    public $local_file_path = '';//要移动到远程的图片地址
    public $file_name = '';//远程存储的文件名

    public function __construct()
    {
        $auth = new Auth($this->AccessKey, $this->SecretKey);
        // 生成上传Token
        $this->token = $auth->uploadToken($this->bucket,null,3600);
    }

    /**
     * @throws \Exception
     */
    public function upload(){
        $uploadModel = new UploadManager();
        $res = $uploadModel->putFile($this->token,$this->file_name,$this->local_file_path);
        return $res[0]['key'];
    }
}