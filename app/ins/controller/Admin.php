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

class Admin extends BaseController{
    public function initialize(){
        $controller = $this->request->controller(true);
        $action = $this->request->action(true);
        $app = app('http')->getName();

        $user = session('user');
        //cookie验证保持登录状态
        if(empty($user))
        {
            if($user = $this->checkCookie())
            {
                $user_model = User::find($user['id']);
                if($user_model)
                {
                    User::doLogin($user_model->getData());
                }
                else
                {
                    throw new ValidateException("未找到用户信息");
                }
            }
            else
            {
                throw new ValidateException("登录超时");
            }
        }
        $this->uid = $user['id'];
        $this->ins_id = $user['ins_id'];
        $this->subject_id = $user['subject_id'];
        $this->route = "{$app}/{$controller}/{$action}";

        //判断如果是非公共接口，则进入鉴权流程
        if($controller == "block")
        {

        }
        else
        {
            $right_model = Right::where("app",$app)->where("controller",$controller)->where("action",$action)->find();
            if(!$right_model || $right_model->isEmpty())
                throw new ValidateException("权限码不存在");
            if(!in_array($right_model['id'],$user['rights']))
                throw new ValidateException("接口访问无权限");
        }


        //加载系统的配置（数据库中的）
//        $list = db("config")->cache(true,60)->column('data','name');
//        config($list,'xhadmin');
    }
    protected function checkCookie(){
        $user = cookie('user');
        if(empty($user))
            return false;

        $user = json_decode(base64_decode($user),true);

        return cookie("user_sign") == data_auth_sign($user) ? $user : false;
    }
}