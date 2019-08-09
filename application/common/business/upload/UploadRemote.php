<?php
/**
 * 第三方空间上传
 */

namespace app\common\business\upload;

use app\extra\UpyunSDK\Upload as upYun;

class UploadRemote
{
    const upYun = 'uploadUpYun';//又拍云方法名
    /**
     * 上传到又拍云
     * @param $local_image //本地图片地址
     * @return array
     * @author wenzhen-chen
     * @time 2019-1-14
     */
    public static function uploadUpYun($local_image)
    {
        $upYun = new upYun();
        $data['imgurl'] = config('api_url').$local_image;//本地图片地址【带域名】
        $data['imgnewname'] = $local_image;//第三方图片地址【不带域名】
        $result = $upYun->writeFileUrl($data);
        return $result['dest_file_name'];
    }
}