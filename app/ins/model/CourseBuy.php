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

class CourseBuy extends Base{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    public static function format_list(Array $list = []){
        $course_ids = fetch_array_value($list,'course_id');
        $student_ids = fetch_array_value($list,'student_id');
        $uids = fetch_array_value($list,'uid');

        $course_list = Course::get_all(["id" => $course_ids],"*");
        if($course_list)
            $course_list = array_column($course_list,null,"id");

        $student_list = Student::get_all(["id" => $student_ids],"*");
        if($student_list)
            $student_list = array_column($student_list,null,"id");

        $user_list = User::get_all(["id" => $uids],"id,name,student_count,point");
        if($user_list)
            $user_list = array_column($user_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['course_id']))
                $item['course_data'] = isset($course_list[$item['course_id']])?$course_list[$item['course_id']]:[];
            if(isset($item['student_id']))
                $item['student_data'] = isset($student_list[$item['student_id']])?$student_list[$item['student_id']]:[];
            if(isset($item['uid']))
                $item['user_data'] = isset($user_list[$item['uid']])?$user_list[$item['uid']]:[];
        }

        return $list;
    }
}