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
use app\ins\model\Role;
use app\ins\model\Team;
use app\ins\model\User;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\Db;

class Admin extends BaseController{
    public function initialize(){
        //检测登录
        $uid = $this->checkLogin();
        $user_model = User::find($uid);
        if(!$user_model)
            throw new ValidateException("未找到用户信息");

        $this->uid = $uid;
        $this->role_id = $user_model['role_id'];
        $this->ins_id = $user_model['ins_id'];
        $this->school_id = $user_model['school_id'];
        $this->subject_ids = $user_model['subject_ids'];
        $this->subject_id = $user_model['current_subject_id'];
        $this->team_ids = Team::whereFindInSet("uids",$this->uid)->column("id");
        //检测权限
        $this->checkRight();
        //加载系统的配置（数据库中的）
//        $list = db("config")->cache(true,60)->column('data','name');
//        config($list,'xhadmin');
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
    //检查权限
    protected function checkRight(){
        $controller = $this->request->controller(true);
        $action = $this->request->action(true);
        $app = app('http')->getName();

        //判断如果是非公共接口，则进入鉴权流程
        if($controller == "block")
        {

        }
        else
        {
            $right_model = Right::where("app",$app)->where("controller",$controller)->where("action",$action)->find();
            if(!$right_model || $right_model->isEmpty())
                throw new ValidateException("权限码不存在");

            $right_ids = [];

            $role_model = Role::find($this->role_id);
            if($role_model)
            {
                $right_str = $role_model['rights'];
                if($role_model['menus'])
                {
                    $menus_collection  = Db::name("menu")->where("id","in",trim($role_model['menus'],","))->where("is_show","Y")->order("sort ASC")->select();
                    if($menus_collection && !$menus_collection->isEmpty())
                    {
                        $menus_list = $menus_collection->toArray();
                        foreach($menus_list as $m)
                        {
                            $right_str .= ",".$m['rights'].",";
                        }
                    }
                }
                if($right_str)
                    $right_ids = array_filter(array_unique(explode(",",$right_str)));
                //过滤掉不存在的权限码
                $right_ids = Db::name("right")->where("id","in",join(",",$right_ids))->column("id");
            }

            if(!in_array($right_model['id'],$right_ids))
                throw new ValidateException("接口访问无权限");
        }
    }
}