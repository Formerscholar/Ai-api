<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 15:21
 */
namespace app\ins\controller;

use app\BaseController;
use app\ins\model\Right;
use app\ins\model\User;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Config;

class Admin extends BaseController{
    public function initialize(){
        $controller = $this->request->controller(true);
        $action = $this->request->action(true);
        $app = app('http')->getName();

        $this->checkLogin();
        $this->route = "{$app}/{$controller}/{$action}";

        //判断如果是非公共接口，则进入鉴权流程
        if($controller == "block")
        {

        }
        else
        {
            $rights = session("rights");
            $right_model = Right::where("app",$app)->where("controller",$controller)->where("action",$action)->find();
            if(!$right_model || $right_model->isEmpty())
                throw new ValidateException("权限码不存在");
            if(!in_array($right_model['id'],$rights))
                throw new ValidateException("接口访问无权限");
        }


        //加载系统的配置（数据库中的）
//        $list = db("config")->cache(true,60)->column('data','name');
//        config($list,'xhadmin');
    }
    //检查登录状态
    protected function checkLogin(){
        $session_user = session('user');
        $session_user_sign = session('user_sign');
        $session_start_time = session('session_start_time');
        $cookie_user = cookie('user');
        $cookie_user_sign = cookie('user_sign');

//        不进行登录验证，仅测试用
//        $this->uid = $session_user['id'];
//        $this->ins_id = $session_user['ins_id'];
//        $this->school_id = $session_user['school_id'];
//        $this->subject_ids = $session_user['subject_ids'];
//        $this->subject_id = $session_user['current_subject_id'];

        //基于cookie方式登录,同时检测session中的数据
        $decode_cookie_user = json_decode(base64_decode($cookie_user),true);
        if($decode_cookie_user && $cookie_user && $cookie_user_sign == data_auth_sign($decode_cookie_user))
        {
            $this->uid = 1;
            $user_model = User::find($session_user['id']);
            if(!$user_model)
                throw new ValidateException("未找到用户信息");

            if($session_user_sign != data_auth_sign($session_user) || time() - $session_start_time > Config::get("session.expire"))
            {
                session('user', null);
                session('user_sign', null);
                cookie('user',null);
                cookie('user_sign',null);

                throw new ValidateException("登录超时");
            }

            //根据哪些用户信息改变了，需要重新登录
            if(isset($decode_cookie_user['role_id']) && $decode_cookie_user['role_id'] != $user_model['role_id'])
                throw new ValidateException("检测到用户信息改变，需要重新登录");

            $this->ins_id = $user_model['ins_id'];
            $this->school_id = $user_model['school_id'];
            $this->subject_ids = $user_model['subject_ids'];
            $this->subject_id = $user_model['current_subject_id'];
        }
        else
        {
            throw new ValidateException("登录超时");
        }
    }
    protected function getCookie(){
        $user = cookie('user');
        if(empty($user))
            return false;

        $user = json_decode(base64_decode($user),true);

        return cookie("user_sign") == data_auth_sign($user) ? $user : false;
    }
}