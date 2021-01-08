<?php
declare (strict_types = 1);

namespace app\ins\controller;

use app\BaseController;
use app\ins\model\Institution;
use app\ins\model\Question;
use app\ins\model\Role;
use app\ins\model\Teacher;
use app\ins\model\User;
use org\Exercises;
use think\Request;
use WeChat\Oauth;

class Login extends BaseController
{
    //默认登录方式：手机号+密码
    public function index(){
        $account = input("post.account");
        $password = input("post.password");

        //账户验证
        $user_model = User::getByAccount($account);
        if(!$user_model)
            return my_json([],-1,"未找到该账号的信息");
        if(!$user_model['is_enable'])
            return my_json([],-1,"用户已禁用");
        if($user_model['password'] != md5($password.$user_model['salt']))
            return my_json([],-1,"密码不正确");

        //机构验证
        $ins_model = Institution::find($user_model['ins_id']);
        if(!$ins_model)
            return my_json([],-1,"未找到机构信息");
        if(!$ins_model['is_enable'])
            return my_json([],-1,"该机构禁止登录使用");

        //执行登录
        User::doLogin($user_model->getData());

        return my_json([
            "id"    =>  $user_model['id'],
            "name"  =>  $user_model['name'],
            "role_id"   =>  $user_model['role_id'],
            "openid" =>  $user_model['openid'],
            "unionid" =>  $user_model['unionid'],
            "avatar" =>  $user_model['avatar'],
            "school_id" =>  $user_model['school_id'],
            "subject_id"    =>  $user_model['subject_id'],
        ],0,"登录成功");
    }
    //获得微信授权登录地址,给前端生成二维码
    public function getWxAuthUrl(){
        $wx_oauth = new Oauth(config("wx_config"));
        $url = $wx_oauth->getOauthRedirect("http://ins.aictb.com/ins/login/wxlogin",'STATE','snsapi_userinfo');

        return my_json(["url"   =>  $url]);
    }
    //微信授权登录
    public function wxLogin(){
        $code = input("get.code");
        if(!$code)
            return my_json([],-1,"code不能为空");

        $wx_oauth = new Oauth(config("wx_config"));
        $res = $wx_oauth->getOauthAccessToken($code);
        $access_token = $res['access_token'];
        $openid = $res['openid'];

        $user_info = $wx_oauth->getUserInfo($access_token,$openid);
        if(!empty($user_info))
        {
            return my_json([],-1,"用户授权信息为空");
        }
        if(!isset($user_info['unionid']))
        {
            return my_json([],-1,"未找到授权信息unioni值");
        }

        $user_model = User::where("unionid",$user_info['unionid'])->find();
        if($user_model)
        {
            //检查账户信息
            if($user_model['is_disabed'])
                return my_json([],-1,"用户已禁用");

            //执行登录
            User::doLogin($user_model->getData());
        }
        else
        {
            return my_json([],-1,"未找到与该微信绑定的账户信息");
        }
    }
    //退出
    public function logout(){
        //执行登出
        User::doLogout();

        return my_json([],0,"登出成功");
    }
}
