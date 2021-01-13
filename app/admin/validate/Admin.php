<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\admin\validate;

use think\Validate;

class Admin extends Validate{
    protected $rule = [
        'account'  =>  'require|length:11|unique:user',
        'password' =>  'regex:^[0-9a-zA-Z]{6,10}$',
        'repassword'=>'confirm:password',
        'true_name' =>  'require|max:20',
        'mobile' =>  'mobile',
        'email' =>  'email',
    ];
    protected $message  =   [
        'repassword.confirm'    =>  "确认密码与新密码不一样"
    ];
    protected $scene = [
        'add'  =>  ['account','password','repassword','true_name','mobile','email'],
        'update_password'   =>  ["password","repassword"]
    ];
}