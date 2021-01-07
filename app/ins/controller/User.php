<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 10:32
 */

namespace app\ins\controller;

use app\ins\model\School;

class User extends Admin{
    //获得当前用户的信息
    public function getUserInfo(){
        $user_model = \app\ins\model\User::field("account,avatar,name,sex,school_id,openid,unionid")->find($this->uid);

        return my_json($user_model->getData());
    }
    //保存当前用户的信息
    public function saveUserInfo(){
        $data = request()->only(["name","sex"]);
        validate(\app\ins\validate\User::class)->scene("EditUserinfo")->check($data);

        $school_model = School::scope("ins_id")->find($data['school_id']);
        if(!$school_model)
            return my_json([],-1,"学校数据不存在");

        $user_model = \app\ins\model\User::find($this->uid);
        $data['update_time'] = time();
        $user_model->save($data);

        return my_json([],0,"用户信息保存成功");
    }
    //修改密码
    public function updatePassword(){
        $post_data = request()->only(["old_password","password","repassword"]);
        validate(\app\ins\validate\User::class)->scene("UpdatePassword")->check($post_data);

        $user_model = \app\ins\model\User::find($this->uid);
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
    //获得菜单栏
    public function getMenus(){
        return my_json(session("user")['menu']);
    }
    //绑定微信
    public function bindWeixin(){

    }
}