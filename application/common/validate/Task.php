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
        'groupId' => 'require',
        'taskId' => 'require',
        'images' => 'require',
    ];
    protected $field = [
    ];
    protected $scene = [
        'groupTask' => ['title', 'desc', 'link', 'price', 'number', 'images', 'groupId'],
        'submitTask' => ['taskId','images'],
    ];

}
