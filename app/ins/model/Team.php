<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 9:09
 */
namespace app\ins\model;

use think\Model;
use think\model\concern\SoftDelete;

class Team extends Base{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    public static function format_list(Array $list = []){
        $school_ids = fetch_array_value($list,'school_id');
        $school_list = School::get_all(["id" => $school_ids],"id,name");
        if($school_list)
            $school_list = array_column($school_list,null,"id");

        $course_ids = fetch_array_value($list,'course_id');
        $course_list = Course::get_all(["id" => $course_ids],"id,name");
        if($course_list)
            $course_list = array_column($course_list,null,"id");

        $uids = fetch_array_value($list,'uids');
        $user_list = User::get_all(["id" => array_filter(array_unique(explode(",",join(",",$uids))))],"id,name,student_count,point");
        if($user_list)
            $user_list = array_column($user_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['school_id']))
            {
                $item['school_data'] = isset($school_list[$item['school_id']])?$school_list[$item['school_id']]:[];
            }
            if(isset($item['course_id']))
                $item['course_data'] = isset($course_list[$item['course_id']]) ? $course_list[$item['course_id']] : [];

            if(isset($item['uids']))
            {
                $item['user_data'] = [];
                $item['uids'] = array_map(function($v){ return (int)$v; },array_filter(explode(",",$item['uids'])));

                foreach($item['uids'] as $uid)
                {
                    if(isset($user_list[$uid]))
                        $item['user_data'][] = $user_list[$uid];
                }
            }
        }

        return $list;
    }
}