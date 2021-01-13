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

        $user = session('user');
        //cookie验证保持登录状态
        if(empty($user))
        {
            if($user = $this->checkCookie())
            {
                $user_model = \app\admin\model\Admin::find($user['id']);
                if($user_model)
                {
                    \app\admin\model\Admin::doLogin($user_model->getData());
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
        $this->route = "{$app}/{$controller}/{$action}";
    }
    protected function checkCookie(){
        $user = cookie('user');
        if(empty($user))
            return false;

        $user = json_decode(base64_decode($user),true);

        return cookie("user_sign") == data_auth_sign($user) ? $user : false;
    }
}