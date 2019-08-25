<?php

namespace app\common\validate;

use think\Validate;

class Task extends Validate
{

    protected $rule = [
        'title' => 'require',
        'desc' => 'require',
        'link' => 'require',
        'price' => 'require',
        'number' => 'require',
        'images' => 'require',
        'moduleId' => 'require',
    ];
    protected $field = [
    ];
    protected $scene = [
        'groupTask' => ['title', 'desc', 'link', 'price', 'number', 'images', 'moduleId'],
        'register' => ['mobile', 'password','code'],
        'forget_password' => ['mobile', 'password','code']
    ];

}
