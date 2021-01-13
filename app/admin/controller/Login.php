<?php
namespace app\admin\controller;

use app\admin\model\Admin;
use app\BaseController;

class Login extends BaseController
{
    //默认登录方式：手机号+密码
    public function index(){
        $account = input("post.username");
        $password = input("post.password");

        //账户验证
        $user_model = Admin::getByAccount($account);
        if(!$user_model)
            return my_json([],-1,"未找到该账号的信息");
        if(!$user_model['is_enable'])
            return my_json([],-1,"用户已禁用");
        if($user_model['password'] != md5($password.$user_model['salt']))
            return my_json([],-1,"密码不正确");

        //执行登录
        Admin::doLogin($user_model->getData());

        return my_json($user_model->getData(),0,"登录成功");
    }
    //退出
    public function logout(){
        //执行登出
        Admin::doLogout();

        return my_json([],0,"登出成功");
    }
}
