<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 13:08
 */
namespace app\ins\model;

use think\Model;

class Base extends Model{
    public function scopeIns_Id($query){
        $cookie_user = cookie("user");
        $decode_cookie_user = json_decode(base64_decode($cookie_user),true);

        $query->where("ins_id",$decode_cookie_user['ins_id']);
    }
    public function scopeSchool_Id($query){
        $cookie_user = cookie("user");
        $decode_cookie_user = json_decode(base64_decode($cookie_user),true);

        $query->where("school_id",$decode_cookie_user['school_id']);
    }

    public static function get_sum($where = [], $field){
        return self::where($where)->sum($field);
    }
    public static function get_count($where = []){
        return self::where($where)->count();
    }
    public static function get_all($where = [],$field = "*",$order="id DESC"){
        $list = self::where($where)->field($field)->order($order)->select();
        return $list?$list->toArray():[];
    }
    public static function get_list($where =[],$field = "*",$order = "id DDESC",$page = 1,$limit = 20){
        $list = self::where($where)->field($field)->order($order)->page($page)->limit($limit)->select();
        return $list?$list->toArray():[];
    }
    public static function get_page($where = [],$filed = "*",$order = "id DESC",$page = 1,$limit = 20){
        $data['list'] = self::get_list($where,$filed,$order,$page = $page,$limit = $limit);
        $data['count'] = self::get_count($where);
        $data['total_page'] = ceil($data['count']/$limit);
        $data['page'] = $page;

        return $data;
    }
}