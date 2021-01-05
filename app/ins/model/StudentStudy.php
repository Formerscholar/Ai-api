<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 13:19
 */
namespace app\ins\model;

class StudentStudy extends Base{

    public function scopeIns_Id($query){
        $user = session("user");
        if($user)
        {
            $query->where("ins_id",$user['ins_id']);
        }
    }

    public static function format_list(Array $list = []){
        $course_ids = fetch_array_value($list,'course_id');
        $student_ids = fetch_array_value($list,'student_id');
        $paper_ids = fetch_array_value($list,'paper_id');

        $course_list = Course::get_all(["id" => $course_ids],"*");
        if($course_list)
            $course_list = array_column($course_list,null,"id");

        $student_list = Student::get_all(["id" => $student_ids],"id,name,mobile");
        $student_list = Student::format_list($student_list);
        if($student_list)
            $student_list = array_column($student_list,null,"id");

        $paper_list = Paper::format_list(Paper::get_all(["id" => $paper_ids],"*"));
        if($paper_list)
            $paper_list = array_column($paper_list,null,"id");

        $uids = fetch_array_value($list,'uid');
        $user_list = Course::get_all(["id" => $uids],"*");
        if($user_list)
            $user_list = array_column($user_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['course_id']))
                $item['course_data'] = isset($course_list[$item['course_id']])?$course_list[$item['course_id']]:[];
            if(isset($item['student_id']))
                $item['student_data'] = isset($student_list[$item['student_id']])?$student_list[$item['student_id']]:[];
            if(isset($item['paper_id']))
                $item['paper_data'] = isset($paper_list[$item['paper_id']])?$paper_list[$item['paper_id']]:[];
            if(isset($item['uid']))
                $item['user_data'] = isset($user_list[$item['uid']])?$user_list[$item['uid']]:[];
        }

        return $list;
    }
}