<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 10:32
 */

namespace app\admin\controller;

use app\admin\model\Admin;
use app\ins\model\School;

class User extends \app\admin\controller\Admin{
    //获得当前用户的信息
    public function getUserInfo(){
        $user_model = Admin::field("account,true_name,avatar_file,mobile,email")->find($this->uid);

        return my_json($user_model->getData());
    }
    //修改密码
    public function updatePassword(){
        $post_data = request()->only(["old_password","password","repassword"]);
        validate(\app\admin\validate\Admin::class)->scene("update_password")->check($post_data);

        $user_model = Admin::find($this->uid);
        if(md5($post_data['old_password'].$user_model['salt']) != $user_model['password'])
            return my_json([],-1,"旧的密码不正确");

        $user_model['password'] = md5($post_data['password'].$user_model['salt']);
        $user_model->save();

        return my_json([]);
    }
    //修改头像
    public function updateAvatar(){
        return my_json([],-1,"暂未实现");
    }
}