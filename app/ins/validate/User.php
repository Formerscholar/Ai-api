<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\ins\validate;

use think\Validate;

class User extends Validate{
    protected $rule = [
        'account'  =>  'require|length:11|unique:user',
        'password' =>  'regex:^[0-9a-zA-Z]{6,10}$',
        'repassword'=>'confirm:password',
        'name' =>  'require|max:20',
        'sex' =>  'in:1,2',
        'point' =>  'number',
        'is_enable' =>  'in:0,1',
        'school_id' =>  'require|integer',
        'subject_id' =>  'require|integer',
    ];
    protected $message  =   [
        'account.require' => '手机号必填',
        'account.length' => '手机号格式为11位的手机号码',
        'account.unique'    =>  '该账号已经存在',
        'password.regex' => '密码格式为6到11位的字母和数字',
        'repassword.confirm' => '确认密码与密码不符',
    ];
    protected $scene = [
        'add'  =>  ['account','password','repassword','name','sex','point','is_enable','school_id','subject_id'],
    ];
    // 老师
    public function sceneEdit()
    {
        return $this->only(['password','name','sex','is_enable','school_id','subject_id'])
            ->remove('password', 'require');
    }
    // 当前用户
    public function sceneEditUserinfo(){
        return $this->only(["name","sex","school_id"]);
    }
}