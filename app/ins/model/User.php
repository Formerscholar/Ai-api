<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 11:25
 */
namespace app\ins\model;

use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class User extends Base
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    public function scopeIns_Id($query){
        $user = session("user");
        if($user)
        {
            $query->where("ins_id",$user['ins_id']);
        }
    }

    //登录
    public static function doLogin(Array $user = []){
        if(empty($user))
            return false;

        $menu = [];
        $right_ids = [];

        $role_model = Role::find($user['role_id']);
        if($role_model)
        {
            if($role_model['menus'])
            {
                $right_str = $role_model['rights'];

                $menus_collection  = Db::name("menu")->where("id","in",trim($role_model['menus'],","))->where("is_show","Y")->order("sort ASC")->select();
                if($menus_collection && !$menus_collection->isEmpty())
                {
                    $menus_list = $menus_collection->toArray();
                    foreach($menus_list as $m)
                    {
                        $menu[] = [
                            "id"    =>  $m['id'],
                            "name"  =>  $m['name'],
                            "route" =>  $m['route'],
                            "desc"  =>  $m['desc'],
                            "pid"   =>  $m['pid'],
                        ];
                        $right_str .= ",".$m['rights'].",";
                    }
                }

                if($right_str)
                    $right_ids = array_filter(array_unique(explode(",",$right_str)));

                //过滤掉不存在的权限码
                $right_ids = Db::name("right")->where("id","in",join(",",$right_ids))->column("id");
            }
        }
        $user['menu'] = $menu;
        $user['rights'] = $right_ids;

        session("user",$user);
        session("user_sign",data_auth_sign($user));
        cookie("user",base64_encode(json_encode($user)));
        cookie("user_sign",data_auth_sign($user));
    }
    //登出
    public static function doLogout(){
        session("user",null);
        session("user_sign",null);

        cookie("user",null);
        cookie("user_sign",null);
    }
    //用户绑定，weixin  绑定微信'
    public static function userBind($type = "weixin"){

    }

    public static function format_list(Array $list = []){
        $subject_ids = fetch_array_value($list,'subject_id');
        $school_ids = fetch_array_value($list,'school_id');

        $subject_list = Subject::get_all(["id" => $subject_ids],"id,name,title,icon1,icon2");
        if($subject_list)
            $subject_list = array_column($subject_list,null,"id");

        $school_list = School::get_all(["id" => $school_ids],"id,name");
        if($school_list)
            $school_list = array_column($school_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['subject_id']))
                $item['subject_data'] = isset($subject_list[$item['subject_id']]) ? $subject_list[$item['subject_id']] : [];
            if(isset($item['school_id']))
                $item['school_data'] = isset($school_list[$item['school_id']]) ? $school_list[$item['school_id']] : [];
        }

        return $list;
    }
}