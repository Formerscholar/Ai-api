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
    //用户绑定，weixin  绑定微信'
    public static function userBind($type = "weixin"){

    }

    public static function format_list(Array $list = []){
        $subject_ids = fetch_array_value($list,'subject_ids');
        $school_ids = fetch_array_value($list,'school_id');

        $subject_list = Subject::get_all(["id" => array_filter(array_unique(explode(",",join(",",$subject_ids))))],"id,name,title,icon1,icon2");
        if($subject_list)
            $subject_list = array_column($subject_list,null,"id");

        $school_list = School::get_all(["id" => $school_ids],"id,name");
        if($school_list)
            $school_list = array_column($school_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['subject_ids']))
            {
                $item['subject_data'] = [];
                $item['subject_ids'] = array_map(function($v){ return (int)$v; },array_filter(explode(",",$item['subject_ids'])));

                foreach($item['subject_ids'] as $subject_id)
                {
                    if(isset($subject_list[$subject_id]))
                        $item['subject_data'][] = $subject_list[$subject_id];
                }
            }
            if(isset($item['school_id']))
                $item['school_data'] = isset($school_list[$item['school_id']]) ? $school_list[$item['school_id']] : [];
        }

        return $list;
    }
}