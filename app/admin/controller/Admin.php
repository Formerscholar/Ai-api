<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 15:21
 */
namespace app\admin\controller;

use app\BaseController;

use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;

class Admin extends BaseController{
    public function initialize(){
        $controller = $this->request->controller(true);
        $action = $this->request->action(true);
        $app = app('http')->getName();

        $uid = $this->checkLogin();
        $admin_model = \app\admin\model\Admin::find($uid);
        if(!$admin_model)
            throw new ValidateException("未找到用户信息");

        $this->uid = $uid;
        $this->route = "{$app}/{$controller}/{$action}";
    }
    //检查登录状态
    protected function checkLogin(){
        $cookie_user = cookie('user');
        $cookie_user_sign = cookie('user_sign');

        //基于cookie方式登录
        $decode_cookie_user = json_decode(base64_decode($cookie_user),true);
        if($decode_cookie_user && $cookie_user && $cookie_user_sign == data_auth_sign($decode_cookie_user))
        {
            return $decode_cookie_user['id'];
        }
        else
        {
            throw new ValidateException("登录超时");
        }
    }
}