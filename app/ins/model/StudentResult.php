<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 13:19
 */
namespace app\ins\model;


class StudentResult extends Base{

    public static function format_list(Array $list = []){
        $student_ids = fetch_array_value($list,'student_id');
        $paper_ids = fetch_array_value($list,'paper_id');
        $question_ids = fetch_array_value($list,'question_id');
        $subject_ids = fetch_array_value($list,'subject_id');

        $student_list = Student::get_all(["id" => $student_ids],"id,name,mobile");
        $student_list = Student::format_list($student_list);
        if($student_list)
            $student_list = array_column($student_list,null,"id");

        $paper_list = Paper::get_all(["id" => $paper_ids],"*");
        $paper_list = Paper::format_list($paper_list);
        if($paper_list)
            $paper_list = array_column($paper_list,null,"id");

        $question_list = Question::get_all(["id"    =>  $question_ids],"id,content_text");
        if($question_list)
            $question_list = array_column($question_list,null,"id");

        $subject_list = Subject::get_all(["id" => $subject_ids],"id,name,title,icon1,icon2");
        if($subject_list)
            $subject_list = array_column($subject_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['student_id']))
                $item['student_data'] = isset($student_list[$item['student_id']])?$student_list[$item['student_id']]:[];
            if(isset($item['paper_id']))
                $item['paper_data'] = isset($paper_list[$item['paper_id']])?$paper_list[$item['paper_id']]:[];
            if(isset($item['question_id']))
                $item['question_data'] = isset($question_list[$item['question_id']]) ? $question_list[$item['question_id']] : [];
            if(isset($item['subject_id']))
                $item['subject_data'] = isset($subject_list[$item['subject_id']]) ? $subject_list[$item['subject_id']] : [];
        }

        return $list;
    }
}