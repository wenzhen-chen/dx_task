<?php

namespace app\common\validate;

use think\Validate;

class User extends Validate
{

    protected $rule = [
        'mobile' => 'regex:(1[3-8])[0-9]{9}|length:11',
        'password' => 'length:6,20',
        'code' => 'number',
    ];
    protected $field = [
    ];
    protected $scene = [
        'login' => ['mobile', 'password'],
        'register' => ['mobile', 'password','code'],
        'forget_password' => ['mobile', 'password','code']
    ];

}
