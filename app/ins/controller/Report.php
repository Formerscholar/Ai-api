<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//学情报告
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
    //老师角色
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
            $know_point_list = Knowledge::where("id","in",$know_point_ids)->orderRaw("field(id,".join(",",$know_point_ids).")")->select();
        else
            $know_point_list = [];

        return my_json($re);
    }
}