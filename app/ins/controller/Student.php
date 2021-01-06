<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//学生管理
use app\ins\model\Knowledge;
use app\ins\model\QuestionCategory;
use app\ins\model\StudentResult;
use app\ins\model\StudentStudy;
use app\ins\model\Subject;
use app\ins\model\Team;
use app\ins\model\User;
use app\Request;
use think\facade\Db;

class Student extends Admin{
    //学生列表
    public function index(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");
        $keyword = input("get.keyword","");
        $team_id   = input("get.team_id",0,"int");
        $school_id   = input("get.school_id",0,"int");
        $start_time   = input("get.start_time");
        $end_time   = input("get.end_time");

        $where[] = ["ins_id","=",$this->ins_id];
        $where[] = ['is_delete','=',0];
        if($keyword)
            $where[] = ['name|mobile','like',"%{$keyword}%"];
        if($team_id)
            $where[] = ['team_ids','find in set',$team_id];
        if($school_id)
            $where[] = ['school_id','=',$school_id];
        if($start_time && $end_time && $start_time < $end_time)
            $where[] = ['add_time','BETWEEN',[$start_time,$end_time]];

        $list = \app\ins\model\Student::get_page($where,"*","add_time DESC",$page,$limit);
        $list['list'] = \app\ins\model\Student::format_list($list['list']);

        return my_json($list);
    }
    //获得编辑学生信息
    public function edit(){
        $id = input("get.id",0,"int");
        $student_model = \app\ins\model\Student::scope("ins_id")->find($id);
        if(!$student_model)
            return my_json([],-1,"学生数据不存在");

        return my_json($student_model->getData());
    }
    //编辑保存学生
    public function save(){
        $data = request()->post();

        validate(\app\ins\validate\Student::class)->check($data);

        $student_model = \app\ins\model\Student::scope("ins_id")->find($data['id']);
        if(!$student_model)
            return my_json([],-1,"学生数据不存在");

        $data['ins_id'] = $this->ins_id;
        $data['team_ids'] = join(",",$data['team_ids']);
        $data['uids'] = join(",",$data['uids']);
        $data['update_time'] = time();
        $student_model->save($data);

        //更新老师学生数
        $old_uids = $student_model['uids'];
        $new_uids = $data['uids'];
        $update_user_data = [];
        $user = new User();
        foreach($old_uids['uids'] as $uid)
        {
            $update_user_data[] = [
                "id"    =>  $uid,
                "student_count" =>  Db::raw('student_count - 1'),
            ];
        }
        $user->saveAll($update_user_data);
        $update_user_data = [];
        foreach($new_uids['uids'] as $uid)
        {
            $update_user_data[] = [
                "id"    =>  $uid,
                "student_count" =>  Db::raw('student_count + 1'),
            ];
        }
        $user->saveAll($update_user_data);


        return my_json([],0,"编辑学生成功");
    }
    //添加学生
    public function add(){
        $post_data = request()->except(["id"]);
        validate(\app\ins\validate\Student::class)->check($post_data);

        $post_data['team_ids'] = join(",",$post_data['team_ids']);
        $post_data['uids'] = join(",",$post_data['uids']);
        $post_data['ins_id'] = $this->ins_id;
        $post_data['add_time'] = time();
        $student_model = \app\ins\model\Student::create($post_data);

        //更新老师的学生数
        $user = new User();
        $update_user_data = [];
        foreach($post_data['uids'] as $uid)
        {
            $update_user_data[] = [
                "id"    =>  $uid,
                "student_count" =>  Db::raw('student_count + 1'),
            ];
        }
        $user->saveAll($update_user_data);

        return my_json(["id"    =>  $student_model->id],0,"添加学生成功");
    }

    //删除学生
    public function delete(){
        $id = input("get.id",0,"int");
        if($id && !is_array($id))
            $id = [$id];
        if(empty($id))
            return my_json([],-1,"未选择要删除的数据");

        $student = \app\ins\model\Student::scope('ins_id')->where("id","in",$id)->field("id,uids")->select()->toArray();
        $filter_ids = array_column($student,"id");
        $filter_uids = array_column($student,"uids");

        $model = new \app\ins\model\Student();
        $batch_data = [];
        foreach($filter_ids as $i)
        {
            $batch_data[] = [
                "id"    =>  $i,
                "is_delete" => 1,
                "delete_time" => time()
            ];
        }
        $model->saveAll($batch_data);

        //更新老师的学生数
        $new_uids = array_filter(explode(",",join(",",$filter_uids)));

        $user = new User();
        $update_user_data = [];
        foreach($new_uids as $uid)
        {
            $update_user_data[] = [
                "id"    =>  $uid,
                "student_count" =>  Db::raw('student_count + 1'),
            ];
        }
        $user->saveAll($update_user_data);

        return my_json([],0,"删除学生成功");
    }
    //详情
    public function detail(){
        $id = input("get.id",0,"int");
        $student = \app\ins\model\Student::scope("ins_id")->find($id);
        if(!$student)
            return my_json([],-1,"学生数据不存在");

        $re = [];
        //基本信息
        $re['info'] = current(\app\ins\model\Student::format_list([$student->getData()]));
        $re['info']['team_data'] = Team::where("uids",'find in set',$id)->field("id,name")->select()->toArray();
        //上课记录
        $re['study_list'] = StudentStudy::format_list(StudentStudy::where("student_id",$id)->select()->toArray()) ;

        return my_json($re);
    }
    //学情报告
    public function report(){
        $id = input("get.id",0,"int");
        $subject_id = input("get.subject_id",0,"int");
        $start_time = input("get.start_time");
        $end_time = input("get.end_time");

        $student = \app\ins\model\Student::scope("ins_id")->find($id);
        if(!$student)
            return my_json([],-1,"未找到学生数据");

        $where = [];
        $where[] = ["student_id","=",$id];
        if($subject_id)
            $where[] = ["subject_id","=",$subject_id];
        if($start_time && $end_time && $start_time < $end_time)
            $where[] = ['add_time','between',[$start_time,$end_time]];

        //按题目类型统计
//        $result = StudentResult::where($where)->fieldRaw("question_type,count(*) as count")->group("question_type")->select()->toArray();
//        $total_count = StudentResult::where($where)->count();
//        $type_list = QuestionCategory::getTypeList(array_column($result,"question_type"));
//
//        $re = [];
//        $re['title'] = "学情报告";
//        $re['xAxis'] = [];
//        $re['yAxis'] = [];
//        foreach($result as $item)
//        {
//            $re['xAxis'][] = $type_list[$item['question_type']]['title'];
//            $re['yAxis'][] = [
//                "value" =>  bcdiv($item['count'],$total_count,2),
//                "name"  =>  $type_list[$item['question_type']]['title'],
//            ];
//        }
        //按知识点统计
        $total_count = StudentResult::where($where)->count();
        $know_point_ids = StudentResult::where($where)->column("question_know_point");
        $know_point_ids = array_filter(array_unique(explode(",",join(",",$know_point_ids))));
        $know_point_list = Knowledge::where("id","in",$know_point_ids)->orderRaw("field(id,".join(",",$know_point_ids).")")->select();

        if($know_point_list)
            $know_point_list = array_column($know_point_list->toArray(),null,"id");
        $result =[];
        $other_count = $total_count;
        foreach($know_point_ids as $know_point_id)
        {
            $count = StudentResult::where($where)->where("question_know_point","find in set",$know_point_id)->count();
            $result[] = [
                "id"    =>  $know_point_id,
                "name"  =>  isset($know_point_list[$know_point_id])?$know_point_list[$know_point_id]['title']:"未知知识点",
                "count" => $count
            ];
            $other_count = $total_count - $count;
        }
        if($other_count)
            $result[] = [
                "id"    =>  "",
                "name"  =>  "空知识点",
                "count" =>  $other_count
            ];

        $re = [];
        $re['title'] = "学情报告";
        $re['xAxis'] = [];
        $re['yAxis'] = [];
        foreach($result as $item)
        {
            $re['xAxis'][] = $item['name'];
            $re['yAxis'][] = [
                "value" =>  bcdiv($item['count'],$total_count,2),
                "name"  =>  $item['name'],
            ];
        }
        
        return my_json($re);
    }
}