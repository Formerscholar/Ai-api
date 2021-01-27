<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 11:25
 */
namespace app\admin\model;

use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class Admin extends Base
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    //登录
    public static function doLogin(Array $user = []){
        if(empty($user))
            return false;

        cookie("user",base64_encode(json_encode($user)));
        cookie("user_sign",data_auth_sign($user));
    }
    //登出
    public static function doLogout(){
        cookie("user",null);
        cookie("user_sign",null);
    }
}