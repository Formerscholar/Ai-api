<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//学生上课记录管理
use app\ins\model\Course;
use app\ins\model\CourseBuy;
use app\ins\model\Paper;
use app\ins\model\Question;
use app\ins\model\Student;
use app\ins\model\StudentResult;
use app\ins\model\StudentStudy;
use app\Request;
use think\facade\Db;

class Study extends Admin{

    //学生上课记录列表
    public function index(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        $keyword = input("get.keyword","");
        $start_time   = input("get.start_time");
        $end_time   = input("get.end_time");
        $where = [];

        if($keyword)
            $where[] = ['student_name','like',"%{$keyword}%"];
        if($start_time && $end_time && $start_time < $end_time)
            $where[] = ['add_time','BETWEEN',[$start_time,$end_time]];

        $list = StudentStudy::get_page($where,"*","add_time DESC",$page,$limit);
        $list['list'] = StudentStudy::format_list($list['list']);

        return my_json($list);
    }
    //学生上课记录详情
    public function detail(){
        $id = input("get.id",0,"int");

        $student_study_model = StudentStudy::find($id);
        if(!$student_study_model)
            return my_json([],-1,"学习记录数据不存在");

        $re = [];
        $re['study_data'] = current(StudentStudy::format_list([$student_study_model->getData()]));
        $re['questions'] = [];
        $question_ids = StudentResult::where("study_id",$id)->column("question_id");
        if($question_ids)
        {
            $re['questions'] = Question::where("id","in",$question_ids)->select();
        }

        return my_json($re);
    }
    //添加学生上课记录
    public function add(){
        $post_data = request()->post();
        validate(\app\ins\validate\StudentStudy::class)->scene("add")->check($post_data);

        $student_id = request()->post("student_id",0,"int");
        $student = Student::scope("ins_id")->find($student_id);
        if(!$student)
            return my_json([],-1,"未找到学生数据");

        $course_id = request()->post("course_id");
        $course = Course::scope("ins_id")->find($course_id);
        if(!$course)
            return my_json([],-1,"未找到课程数据");

        $course_buy = CourseBuy::scope("ins_id")->where("course_id",$course_id)->where("student_id",$student_id)->whereRaw("used_hour < buy_hour")->find();
        if(!$course_buy)
            return my_json([],-1,"未找到该学生的购买记录");

        $paper_id = request()->post("paper_id",0,"int");
        $paper = Paper::scope("ins_id")->find($paper_id);
        if(!$paper)
            return my_json([],-1,"未找到试卷数据");

        $questions = request()->post("questions",[]);

        $insert_study_data = [
            "ins_id"    =>  $this->ins_id,
            "course_id" =>  $course_id,
            "student_id"    =>  $student_id,
            "student_name"  =>  $student['name'],
            "content"   =>  $post_data['content'],
            "study_time"  =>  $post_data['study_time'],
            "add_time"  =>  time(),
            "paper_id"  =>  $paper_id,
            "uid"   =>  $this->uid,
            "mistake_count" =>  count($questions)
        ];
        $insert_result_data = [];
        foreach($questions as $question)
        {
            $insert_result_data[] = [
                "paper_id"  =>  $paper_id,
                "question_id"   =>  $question['question_id'],
                "subject_id"    =>  $question['subject_id'],
                "question_type"    =>  $question['question_type'],
                "question_know_point"    =>  $question['question_know_point'],
                "student_id"    =>  $student_id,
                "add_time"  =>  time(),
            ];
        }
        \think\facade\Db::startTrans();
        try {
            //插入学习记录数据
            $study_model = StudentStudy::create($insert_study_data);
            foreach($insert_result_data as $key => $val)
            {
                $insert_result_data[$key]['study_id'] = $study_model->id;
            }
            //插入学习记录错题题目关系数据
            $result_model = new StudentResult();
            $result_model->saveAll($insert_result_data);
            //更新学生课程课时
            CourseBuy::update(["used_hour" => Db::raw('used_hour+1')],["id"  =>  $course_buy['id']]);
            // 提交事务
            \think\facade\Db::commit();

            return my_json([],0,"操作成功");
        } catch (\Exception $e) {
            // 回滚事务
            \think\facade\Db::rollback();

            return my_json([],-1,$e->getMessage());
        }
    }
    //编辑学生上课记录
    public function edit(){
        $id = request()->get("id",0,"int");

        $re = [];

        $study = StudentStudy::scope("ins_id")->find($id);
        if(!$study)
            return my_json([],-1,"未找到学习记录");
        $re['study_data'] = $study->getData();

        $result = StudentResult::where("study_id",$id)->column("question_id");
        $re['result'] = $result;

        return my_json($re);
    }
    //编辑保存上课记录
    public function save(){
        $post_data = request()->except(["course_id","student_id"]);
        validate(\app\ins\validate\StudentStudy::class)->scene("edit")->check($post_data);

        $id = input("post.id",0,"int");
        $study = StudentStudy::scope("ins_id")->find($id);
        if(!$study)
            return my_json([],-1,"未找到学习记录");

        $paper_id = request()->post("paper_id",0,"int");
        $paper = Paper::scope("ins_id")->find($paper_id);
        if(!$paper)
            return my_json([],-1,"未找到试卷数据");

        $questions = request()->post("questions",[]);
        $update_study_data = [
            "content"   =>  $post_data['content'],
            "study_time"    =>  $post_data['study_time'],
            "paper_id"  =>  $post_data['paper_id'],
            "update_time"   =>  time(),
            "mistake_count" =>  count($questions)
        ];
        $insert_result_data = [];
        foreach($questions as $question)
        {
            $insert_result_data[] = [
                "study_id"  =>  $id,
                "paper_id"  =>  $paper_id,
                "question_id"   =>  $question['question_id'],
                "subject_id"    =>  $question['subject_id'],
                "question_type"    =>  $question['question_type'],
                "student_id"    =>  $study['student_id'],
                "add_time"  =>  time(),
            ];
        }
        \think\facade\Db::startTrans();
        try {
            //插入学习记录数据
            $study_model = StudentStudy::update($update_study_data,["id"    =>  $id]);
            //插入学习记录错题题目关系数据
            StudentResult::where("study_id",$id)->delete();
            $result_model = new StudentResult();
            $result_model->saveAll($insert_result_data);
            // 提交事务
            \think\facade\Db::commit();

            return my_json([],0,"操作成功");
        } catch (\Exception $e) {
            // 回滚事务
            \think\facade\Db::rollback();

            return my_json([],-1,"操作失败");
        }
    }
    //删除上课记录
    public function delete(){
        $id = input("get.id",0,"int");
        if($id && !is_array($id))
            $id = [$id];
        if(empty($id))
            return my_json([],-1,"未选择要删除的数据");

        //过滤id
        $delete_ids = StudentStudy::scope("ins_id")->where("id","in",$id)->column("id");

        StudentStudy::where("id","in",$delete_ids)->delete();
        StudentResult::where("study_id","in",$delete_ids)->delete();

        return my_json([],0,"删除上课记录成功");
    }
}