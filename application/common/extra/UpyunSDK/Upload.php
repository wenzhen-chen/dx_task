<?php

namespace app\extra\UpyunSDK;

use app\common\business\upload\Upload as uploadBn;

class Upload {

    public $_bucket;
    public $_user;
    public $_password;

    public function __construct() {
        $this->_bucket = Config::getBucket();
        $this->_user = Config::getUser();
        $this->_password = Config::getPassword();
    }

    /**
     * 上传图片
     * @param type $origiFile
     * @return type
     */
    public function writeFile($origiFile) {
        $upyun = new UpYun($this->_bucket, $this->_user, $this->_password);
        $option = array(
//            UpYun::CONTENT_MD5 => $this->getFileMd5($origiFile), //校验文件完整性
        );

        $fp = fopen($origiFile, 'rb'); //文件资源
        $destFile = $this->getDestFileNameweb($origiFile); //上传到又拍云后的文件名
        $result = $upyun->writeFile($destFile, $fp, true, $option); //上传到又拍云，自动创建目录
        $data = array();
        if (is_array($result)) {
            $result = array_change_key_case($result, CASE_LOWER); //返回的数组键值为X-Upyun-xxx，统一替换为小写
            $data['x-upyun-width'] = isset($result['x-upyun-width']) ? $result['x-upyun-width'] : '';
            $data['x-upyun-height'] = isset($result['x-upyun-height']) ? $result['x-upyun-height'] : '';
            $data['dest_file_name'] = $destFile;
        } elseif (is_bool($result) && $result == true) {
            $data['x-upyun-width'] = '';
            $data['x-upyun-height'] = '';
            $data['dest_file_name'] = $destFile;
        } else {
            $data['x-upyun-width'] = '';
            $data['x-upyun-height'] = '';
            $data['dest_file_name'] = '';
        }
        fclose($fp);
        return $data;
    }

    /**
     * 上传app
     * @param type $origiFile
     * @return type
     */
    public function writeFileApp($origiFile, $dirname) {
        $bucket = Config::getUpyunConfig('upyun.app.bucket');
        $user = Config::getUpyunConfig('upyun.app.user');
        $password = Config::getUpyunConfig('upyun.app.password');
        $upyun = new UpYun($bucket, $user, $password, null, 120);
        $option = array(
            UpYun::CONTENT_MD5 => $this->getFileMd5($origiFile)//校验文件完整性
        );
        $fp = fopen($origiFile, 'rb'); //文件资源
        $destFile = $this->getAppDestFileName($origiFile, $dirname); //上传到又拍云后的文件名
        $result = $upyun->writeFile($destFile, $fp, true, $option); //上传到又拍云，自动创建目录
        //$result['dest_file_name'] = $destFile;
        fclose($fp);
        return array('dest_file_name' => $destFile);
    }

    /**
     * 上传app
     * @param type $origiFile
     * @return 
     * imgurl
     * imgnewname 上传到又拍云后的文件名
     */
    public function writeFileUrl($post) {
        $url = $post['imgurl'];
        $destFile = $post['imgnewname'];
        $upyun = new UpYun($this->_bucket, $this->_user, $this->_password);
        $body = $this->_requestgetimg($url); 
        $option = array(
                // UpYun::CONTENT_MD5 => $this->getFileMd5($url)//校验文件完整性
        );
        $result = $upyun->writeFile($destFile, $body, true, $option); //上传到又拍云，自动创建目录

        return array('dest_file_name' => $destFile);
    }

    /**
     * 获取文件的md5
     * @param type $file
     * @return type
     */
    public function getFileMd5($file) {
        $fileContent = $this->_requestgetimg($file);
        return md5($fileContent);
    }

    /**
     * 获取远程图片
     * 
     * @param string $url
     * @param string $method
     * @param array $params
     * @param int $timeout
     * @return boolean
     */
    public function _requestgetimg($url, $method = "GET", $params = array(), $timeout = 30, $extParams = array()) {
        $url = $url;
        $paramString = http_build_query($params, '', '&');
        if (strtoupper($method) == "GET" && $params) {
            $url .= "?" . $paramString;
        }
        $ch = curl_init($url);
        if (strtoupper($method) == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramString);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if (!empty($extParams['headers'])) {
            $headerArr = array();
            foreach ($extParams['headers'] as $k => $v) {
                $headerArr[] = $k . ':' . $v;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        }
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

        if (!empty($extParams["cookies"])) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->analyzeCookie($extParams["cookies"]));
        }
        //检测是否是https访问
        if (strpos($url, 'https') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        $result = curl_exec($ch); 
        curl_close($ch);
        return $result;
    }

    /**
     * 获取目标文件名
     * @param type $file
     * @return type
     */
    public function getDestFileName($file) {
        $date = date('/Y/m/d', time());
        $md5Str = $this->getFileMd5($file);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return $date . '/' . $md5Str . '.' . $ext;
    }

    /**
     * 获取目标文件名
     * @param type $file
     * @return type
     */
    public function getDestFileNameweb($file) {
        $date = date('/Y/m/d', time());
        $md5Str = md5($file);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return $date . '/' . $md5Str . '.jpg';
    }

    /**
     * 获取app目标文件名
     * @param type $file
     * @return type
     */
    public function getAppDestFileName($file, $dirname) {
        $fileinfo = pathinfo($file);
        if (empty($dirname)) {
            if ($fileinfo['extension'] == 'apk') {
                $name = '/apk/' . $fileinfo['basename'];
            } else {
                $name = '/iostest/' . $fileinfo['basename'];
            }
        } else {
            $name = '/' . $dirname . '/' . $fileinfo['basename'];
        }
        return $name;
    }

    /**
     * 上传图片
     */
    public static function upimg($imgurl,$ext="jpg") {
        //生成图片
        $resourceHost = config('app.upyun.resourcesHost');
        $createpath = "/" . date("Y") . "/" . date("m") . "/" . date("d") . "/";
        $newurl = $resourceHost . $createpath . md5($imgurl) .".". $ext;
        $temp['imgurl'] = $imgurl;
        $temp['newimgurl'] = $newurl;
        $temp['imgnewname'] = $createpath . md5($imgurl) .".".$ext;
        $img = uploadBn::uploadavurl($temp);
        
        if (isset($img['dest_file_name']) && !empty($img['dest_file_name'])) {
            return $img;
        }
        return $img;
    }
    /**
     * 上传安装包
     */
    public static function upsystem($imgurl,$filename) {
        //生成图片
        $resourceHost = config('upyun.systemHost');

        $newurl = $resourceHost;
        $temp['imgurl'] = $imgurl;
        $temp['newimgurl'] = $newurl.$filename;
        $temp['imgnewname'] =  '/qiumi/down/'.$filename;
        $img = uploadBn::uploadavurl($temp);
        if (isset($img['dest_file_name']) && !empty($img['dest_file_name'])) {
            return $img;
        }
        return $img;
    }
    

}
