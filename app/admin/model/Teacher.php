<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 11:25
 */
namespace app\admin\model;

use think\Model;
use think\model\concern\SoftDelete;

class Teacher extends Base
{
    protected $name = "user";

    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    public static function format_list(Array $list = []){
        $subject_ids = fetch_array_value($list,'subject_id');
        $school_ids = fetch_array_value($list,'school_id');
        $role_ids = fetch_array_value($list,'role_id');

        $subject_list = Subject::get_all(["id" => $subject_ids],"id,name,title,icon1,icon2");
        if($subject_list)
            $subject_list = array_column($subject_list,null,"id");

        $school_list = School::get_all(["id" => $school_ids],"id,name");
        if($school_list)
            $school_list = array_column($school_list,null,"id");

        $role_list = Role::get_all(["id"    =>  $role_ids],"id,name");
        if($role_list)
            $role_list = array_column($role_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['subject_id']))
                $item['subject_data'] = isset($subject_list[$item['subject_id']]) ? $subject_list[$item['subject_id']] : [];
            if(isset($item['school_id']))
                $item['school_data'] = isset($school_list[$item['school_id']]) ? $school_list[$item['school_id']] : [];
            if(isset($item['role_id']))
                $item['role_data'] = isset($role_list[$item['role_id']]) ? $role_list[$item['role_id']]:[];
        }

        return $list;
    }
}