<?php

namespace app\common\business\upload;

use app\extra\UpyunSDK\Upload as upyunSDK;

class Upload
{
    const MAX_UPLOAD_SIZE = 2 * 1024 * 1024;//文件大小限制
    public $savePath = "./uploads/";//保存地址

    //规定上传格式
    const IMAGE = 'image';//
    const MUSIC = 'music';
    const ARR_ALLOW_TYPE = [
        self::IMAGE => ['jpg', 'gif', 'jpeg', 'x-png', 'png', 'pjpeg'],
        self::MUSIC => ['mp3']
    ];

    /**
     * 执行上传到又拍云
     * @param $originFile
     * @return \app\extra\UpyunSDK\type
     */
    public static function UpYunUpload($originFile)
    {
        $upyun = new upyunSDK();
        $result = $upyun->writeFile($originFile);
        return $result;
    }

    /*q
     *
     * 上传图片到upyun
     *
     */
    public static function uploadav($base64_img, $isbase64 = true)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)) {
            $type = $result[2];
            $new_file = "./tmp/" . md5($base64_img) . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_img)))) {

                $upyunResult = self::UpYunUpload($new_file);
                $resourceHost = config('app.upyun.resourcesHost');
                $data = array(
                    'dest_file_name' => $upyunResult['dest_file_name'] ? $resourceHost . $upyunResult['dest_file_name'] : '',
                    'status' => 1,
                    'resource_host' => $resourceHost
                );
                if (file_exists($new_file)) {
                    unlink($new_file); //上传到又拍云后删除本地服务器图片
                }
                return $data;
            } else {
                throw new \Exception("上传失败", 103);
            }
        } else {
            throw new \Exception("图片参数格式不正确", 104);
        }
    }

    /**
     * 保存图片到本地
     * @param $post
     * @return mixed
     */
    public static function uploadavurllocal($post)
    {
        $url = $post['imgurl'];
        $body = self::_requestgetimg($url);
        $dir = "./uploads/" . date("Ymd") . "/";
        $dir2 = "/uploads/" . date("Ymd") . "/";
        $new_file = $dir . md5($body['body']) . ".png";
        $new_file2 = $dir2 . md5($body['body']) . ".png";
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($new_file, $body['body']);
        $data['dest_file_name'] = $new_file2;
        return $data;
    }

    /**
     * 二进制文件上传
     * @param $file
     * @param $type
     * @return array
     * @author wnezhen-chen
     * @time 2018-12-6
     */
    public function uploadFile($file, $type)
    {
        $save_path = $this->savePath . date("Ymd") . "/";
        if (!file_exists($save_path)) {//文件夹不存在，创建文件夹
            mkdir($save_path);
        }
        $file_type = pathinfo($file['name'])['extension'];//文件后缀
        //1、验证图片格式
        if (!in_array($file_type, self::ARR_ALLOW_TYPE[$type])) {
            return [
                'code' => -1001,
                'msg' => '格式不正确'
            ];
        }
        $save_path .= md5($file['name']) . '.' . $file_type;
        //2、验证文件是否通过http上传
        if (!is_uploaded_file($file['tmp_name'])) {
            return [
                'code' => -1002,
                'msg' => '请求错误'
            ];
        }
        //3、检测文件大小
        if ($file['size'] > self::MAX_UPLOAD_SIZE) {
            return [
                'code' => -1003,
                'msg' => '文件过大'
            ];
        }
        //4、保存文件
        if (!move_uploaded_file($file['tmp_name'], $save_path)) {
            return [
                'code' => -1004,
                'msg' => '保存失败'
            ];
        }
        return [
            'code' => 1000,
            'msg' => '上传成功',
            'path' => ltrim($save_path, ".")
        ];
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
    public static function _requestgetimg($url, $method = "GET", $params = array(), $timeout = 30, $extParams = array())
    {
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
        $result['body'] = curl_exec($ch);
        $result['header'] = curl_getinfo($ch);
        curl_close($ch);
        return $result;
    }

}
