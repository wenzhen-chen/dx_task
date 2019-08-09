<?php

namespace app\extra\UpyunSDK;

class Config {

    public static function getBucket() {
        $config = config('app.upyun');
        return $config['bucket'];
    }

    public static function getUser() {
        $config = config('app.upyun');
        return $config['user'];
    }

    public static function getPassword() {
        $config = config('app.upyun');
        return $config['password']; 
    }

    public static function getUpyunConfig($name) {
        return config($name);
    }

}
