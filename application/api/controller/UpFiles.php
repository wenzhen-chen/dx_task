<?php

namespace app\api\controller;

use app\common\business\upload\UploadImage;
use app\common\controller\BaseController;
use think\facade\Env;

class UpFiles extends BaseController
{
    /**
     * 上传二进制图片
     */
    public function upload()
    {
        try{
            $type = input('type','');
            $imageUpload = new UploadImage('file',$type,'');
            $info = $imageUpload->getFileInfo();
            if ($info['status']) {
                $code = 0;
                $msg = '上传成功';
                $url = $info['url'];
            } else {
                $code = -1;
                $msg = '上传失败';
                $url = '';
            }
            $this->_echoSuccessMessage($code, $msg,['url' => $url]);
        }catch (\Exception $e){
            $this->_echoErrorMessage($e->getMessage(), $e->getCode());
        }

    }

    public function file($save_path = '')
    {
        $fileKey = array_keys(request()->file());
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/目录下
        $info = $file->move(Env::get('root_path') . 'public/' . $save_path,$_FILES['file']['name']);
        if ($info) {
            $result['code'] = 0;
            $result['info'] = '文件上传成功!';
            $path = str_replace('\\', '/', $info->getSaveName());

            $result['url'] = config('api_url') . '/' . $save_path . $path;
            $result['ext'] = $info->getExtension();
            $result['size'] = byte_format($info->getSize(), 2);
            return $result;
        } else {
            // 上传失败获取错误信息
            $result['code'] = 1;
            $result['info'] = '文件上传失败!';
            $result['url'] = '';
            return $result;
        }
    }

    public function pic()
    {
        // 获取上传文件表单字段名
        $fileKey = array_keys(request()->file());
        // 获取表单上传文件
        $file = request()->file($fileKey['0']);
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $result['code'] = 1;
            $result['info'] = '图片上传成功!';
            $path = str_replace('\\', '/', $info->getSaveName());
            $result['url'] = '/uploads/' . $path;
            return json_encode($result, true);
        } else {
            // 上传失败获取错误信息
            $result['code'] = 0;
            $result['info'] = '图片上传失败!';
            $result['url'] = '';
            return json_encode($result, true);
        }
    }

    //编辑器图片上传
    public function editUpload()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $datalocal = $this->request->root() . '/uploads/' . $info->getSaveName();
            echo $datalocal;
            die;
            $url = "http://" . $_SERVER['HTTP_HOST'] . $datalocal;
            $extdata = pathinfo($url);
            $img = uploadBn::upimg($url, $extdata['extension']);
            return self::edituploadreturn($img, $info);
        } else {
            // 上传失败获取错误信息
            $result['code'] = 1;
            $result['msg'] = '图片上传失败!';
            $result['data'] = '';
            return json_encode($result, true);
        }
    }

    /*
    *
    * 上传返回数据
    * */
    public function edituploadreturn($img, $info)
    {
        if (isset($img['dest_file_name']) && !empty($img['dest_file_name'])) {
            $data = $img['dest_file_name'];
        }
        $result['code'] = 0;
        $result['msg'] = '图片上传成功!';
        $result['data']['src'] = $data;
        $result['data']['title'] = $data;
        return json_encode($result, true);

    }

    /*
   *
   * 上传返回数据
   * */
    public function uploadreturn($img, $info)
    {
        if (isset($img['dest_file_name']) && !empty($img['dest_file_name'])) {
            $data = $img['dest_file_name'];
        }
        $result['code'] = 1;
        $result['info'] = '文件上传成功!';
        $result['url'] = '/uploads/' . $data;
        $result['ext'] = $info->getExtension();
        $result['size'] = byte_format($info->getSize(), 2);
        return json_encode($result, true);

    }

    //多图上传
    public function upImages()
    {
        $fileKey = array_keys(request()->file());
        // 获取表单上传文件
        $file = request()->file($fileKey['0']);
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $result['code'] = 0;
            $result['msg'] = '图片上传成功!';
            $path = str_replace('\\', '/', $info->getSaveName());
            $result["src"] = $path;
            return $result;
        } else {
            // 上传失败获取错误信息
            $result['code'] = 1;
            $result['msg'] = '图片上传失败!';
            return $result;
        }
    }
}