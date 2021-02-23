<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//学情报告
use aictb\Api;
use app\ins\model\Knowledge;
use app\ins\model\LocalSubject;
use app\ins\model\Student;
use app\ins\model\StudentResult;
use app\ins\model\StudentStudy;
use app\ins\model\Team;
use app\Request;
use think\facade\Db;

class Report extends Admin{
    //获得查询条件
    public function getCondition(){
        //查询条件
        $condition = [];

        //老师拥有的班级
        $condition['team'] = Team::scope("ins_id")->whereFindInSet("uids",$this->uid)->field("id,name")->select()->toArray();
        //老师拥有的学科
        $curr_subject_ids = explode(",",$this->subject_ids);
        if(empty($this->subject_ids) || empty($curr_subject_ids))
            return my_json([],-1,"未设置老师科目信息");
        $condition['subject'] = LocalSubject::whereIn("id",$curr_subject_ids)->where("is_show",1)->field("id,title")->order("sort asc")->select()->toArray();
        //老师负责班级下的学生
        $team_ids = array_column($condition['team'],"id");
        $condition['student'] = [];
        if(!empty($team_ids))
        {
            $wh = [];
            $tmp = [];
            foreach($team_ids as $key => $val)
            {
                $tmp[] = "FIND_IN_SET({$val},team_ids)";
            }
            $wh[] = Db::raw(join(" OR ",$tmp));
            $condition['student'] = Student::scope("ins_id")->where($wh)->field("id,name")->select()->toArray();
        }

        return my_json($condition);
    }
    //
    public function index(){
        $team_id = input("get.team_id",0,"int");
        $subject_id = input("get.subject_id",0,"int");
        $student_id = input("get.student_id",0,"int");
        $start_time = input("get.start_time");
        $end_time = input("get.end_time");

        $re = [];
        $re['title'] = "错题报告数据";
        $re['count'] = 0;
        $re['xAxis'] = [];
        $re['yAxis'] = [];
        $re['knowledge'] = [];

        $current_team_ids = Team::scope("ins_id")->whereFindInSet("uids",$this->uid)->column("id");
        if(empty($current_team_ids))
            return my_json([],-1,"未找到老师负责的班级");

        if($team_id && in_array($team_id,$current_team_ids))
        {
            $student_ids = Student::scope("ins_id")->whereFindInSet("team_ids",$team_id)->column("id");
        }
        else
        {
            $wh = [];
            $tmp = [];
            foreach($current_team_ids as $key => $val)
            {
                $tmp[] = "FIND_IN_SET({$val},team_ids)";
            }
            $wh[] = Db::raw(join(" OR ",$tmp));
            $student_ids = Student::scope("ins_id")->where($wh)->column("id");
        }
        if(empty($student_ids))
            return my_json($re);

        $where = [];
        $where[] = ["student_id","in",$student_ids];
        if($subject_id)
            $where[] = ["subject_id","=",$subject_id];
        if($student_id)
            $where[] = ["student_id","=",$student_id];
        if($start_time && $end_time && $start_time < $end_time)
            $where[] = ['add_time','between',[$start_time,$end_time]];

        //按知识点统计
        $total_count = StudentResult::where($where)->count();
        $know_point_ids = StudentResult::where($where)->column("question_know_point");
        $know_point_ids = array_filter(array_unique(explode(",",join(",",$know_point_ids))));
        if(!empty($know_point_ids))
            $know_point_list = Knowledge::where("id","in",$know_point_ids)->orderRaw("field(id,".join(",",$know_point_ids).")")->select()->toArray();
        else
            $know_point_list = [];
        if(!empty($know_point_list))
            $know_point_list = array_column($know_point_list->toArray(),null,"id");

        $result =[];
        $have_know_count = 0;
        foreach($know_point_ids as $know_point_id)
        {
            $count = StudentResult::where($where)->where("question_know_point","find in set",$know_point_id)->count();
            $re['count'] += $count;
            $result[] = [
                "id"    =>  $know_point_id,
                "name"  =>  isset($know_point_list[$know_point_id])?$know_point_list[$know_point_id]['title']:"未知知识点",
                "count" => $count
            ];
            $have_know_count += $count;
        }

        if(($total_count - $have_know_count) > 0)
            $result[] = [
                "id"    =>  "",
                "name"  =>  "无知识点",
                "count" =>  $total_count - $have_know_count
            ];

        foreach($result as $item)
        {
            $re['xAxis'][] = $item['name'];
            $re['yAxis'][] = [
                "value" =>  bcdiv($item['count'],$total_count,2),
                "name"  =>  $item['name'],
            ];
            $re['knowledge'][] = [
                "id"    =>  $item['id'],
                "name"  =>  $item['name'],
                "count" =>  $item['count'],
                "rate"  =>  bcdiv($item['count'],$total_count,2),
            ];
        }

        return my_json($re);
    }
    //根据条件返回题目列表
    public function getQuestionList(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",20,"int");

        $team_id = input("get.team_id",0,"int");
        $subject_id = input("get.subject_id",0,"int");
        $student_id = input("get.student_id",0,"int");
        $start_time = input("get.start_time");
        $end_time = input("get.end_time");

        $current_team_ids = Team::scope("ins_id")->whereFindInSet("uids",$this->uid)->column("id");
        if(empty($current_team_ids))
            return my_json([],-1,"未找到老师负责的班级");

        if($team_id && in_array($team_id,$current_team_ids))
        {
            $student_ids = Student::scope("ins_id")->whereFindInSet("team_ids",$team_id)->column("id");
        }
        else
        {
            $wh = [];
            $tmp = [];
            foreach($current_team_ids as $key => $val)
            {
                $tmp[] = "FIND_IN_SET({$val},team_ids)";
            }
            $wh[] = Db::raw(join(" OR ",$tmp));
            $student_ids = Student::scope("ins_id")->where($wh)->column("id");
        }

        $where = [];
        $where[] = ["student_id","in",$student_ids];
        if($subject_id)
            $where[] = ["subject_id","=",$subject_id];
        if($student_id)
            $where[] = ["student_id","=",$student_id];
        if($start_time && $end_time && $start_time < $end_time)
            $where[] = ['add_time','between',[$start_time,$end_time]];

        $question_ids = StudentResult::where($where)->page($page)->limit($limit)->column("question_id");
        if(!empty($question_ids))
        {
            $ctb = new Api();
            $result = $ctb->getExercisesDetail(["ids" => array_unique($question_ids)]);
            if(!$result)
                return my_json([],-1,$ctb->getError());
            $result = array_column($result,null,"id");
        }
        $list = StudentResult::get_page($where,"*","id desc",$page,$limit);

        foreach($list['list'] as &$item)
        {
            $item['content_all'] = isset($result[$item['question_id']])?$result[$item['question_id']]['content_all']:"";
        }

        return my_json($list);
    }
}