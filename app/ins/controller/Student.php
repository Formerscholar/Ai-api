<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//学生管理
use app\ins\model\CourseBuy;
use app\ins\model\Grade;
use app\ins\model\Knowledge;
use app\ins\model\Question;
use app\ins\model\QuestionCategory;
use app\ins\model\School;
use app\ins\model\StudentResult;
use app\ins\model\StudentStudy;
use app\ins\model\Subject;
use app\ins\model\Team;
use app\ins\model\User;
use app\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\facade\Db;
use think\facade\Filesystem;

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

        $list = \app\ins\model\Student::get_page($where,"*","id DESC",$page,$limit);
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

        $post_data['team_ids'] = !empty($post_data['team_ids']) ?join(",",$post_data['team_ids']) : '';
        $post_data['uids'] = !empty($post_data['uids']) ?join(",",$post_data['uids']) : '';
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
        return my_json($re);
    }
    //详情-上课记录
    public function studyList(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");
        $id = input("get.id",0,"int");

        $where[] = ["student_id","=",$id];
        $list = StudentStudy::get_page($where,"*","id DESC",$page,$limit);
        $list['list'] = StudentStudy::format_list($list['list']);

        return my_json($list);
    }
    //详情-课程购买记录
    public function buyList(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");
        $id = input("get.id",0,"int");

        $where[] = ["student_id","=",$id];
        $list = CourseBuy::get_page($where,"*","id DESC",$page,$limit);
        $list['list'] = CourseBuy::format_list($list['list']);

        return my_json($list);
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
        if(!empty($know_point_ids))
            $know_point_list = Knowledge::where("id","in",$know_point_ids)->orderRaw("field(id,".join(",",$know_point_ids).")")->select();
        else
            $know_point_list = [];

        if(!empty($know_point_list))
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
        $re['student_data'] = $student->getData();
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
    //将题目加入到学生错题集中
    public function addMistake(){
        $post_data = request()->only(["question_id","student_ids"]);

        $question_model = Question::find($post_data['question_id']);
        if(!$question_model)
            return my_json([],-1,"未找到题目数据");

        if(empty($post_data['student_ids']) || !is_array($post_data['student_ids']))
            return my_json([],-1,"学生数据不能为空");

        $student_mistake_model = StudentResult::where("student_id","in",$post_data['student_ids'])->where("question_id",$post_data['question_id'])->select();
        $filter_student_ids = array_diff($post_data['student_ids'],array_column($student_mistake_model->toArray(),"student_id"));

        $insert_data = [];
        foreach($filter_student_ids as $id)
        {
            $insert_data[] = [
                "study_id"  =>  0,
                "teacher_id"    =>  $this->uid,
                "paper_id"  =>  0,
                "student_id"    =>  $id,
                "question_id"   =>  $post_data['question_id'],
                "subject_id"    =>  $question_model['subject_id'],
                "question_type" =>  $question_model['type'],
                "question_know_point"   =>  empty($question_model['know_point'])?"":$question_model['know_point'],
                "add_time"      =>  time()
            ];
        }

        $student_result_model = new StudentResult();
        $student_result_model->saveAll($insert_data);

        return my_json([]);
    }
    //将题目从学生错题集中移除
    public function removeMistake(){
        $post_data = request()->only(["question_id","student_id"]);

        $student_result_model = StudentResult::where("student_id",$post_data['student_id'])->where("question_id",$post_data['question_id'])->find();
        if(!$student_result_model)
            return my_json([],-1,"未找到错题数据");

        StudentResult::where("id",$student_result_model['id'])->delete();
        return my_json();
    }
    //上传
    public function upload(){
        $file = request()->file('file');
        if(empty($file))
            return my_json([],-1,"未检测到上传文件");

        $result = validate([
            'file'  =>  ['fileSize:102400,fileExt:xlsx,xls']
        ])->check(["file"   =>  $file]);
        if(!$result)
            return my_json([],-1,"检测附件未通过");

        //上传到服务器,
        $path = Filesystem::disk('public_html')->putFile('upload',$file);

        $reader = new Xlsx();
        $spreadsheet = $reader->load($path);
        $datas = $spreadsheet->getActiveSheet()->toArray();

        //去掉标题
        array_shift($datas);

        //检测导入的数据,同时赋值
        $re = [];
        foreach($datas as $d)
        {
            $tmp = [
                "name"  =>  $d[0],
                "sex"  =>  $d[1],
                "school"  =>  $d[2],
                "team"  =>  $d[3],
                "teacher"  =>  $d[4],
                "mobile"  =>  $d[5],
                "saler"  =>  $d[6],
            ];
            //检测姓名
            if(empty($tmp["name"]) || mb_strlen($tmp["name"]) > 20)
                continue;

            //检测性别
            if(!in_array($tmp["sex"],['男','女']))
                continue;
            $tmp['sex_value'] = $tmp["sex"] == '男' ? 1:2;

            //检测校区
            if(!empty($tmp["school"]))
            {
                $school_model = School::scope("ins_id")->where("name",$tmp["school"])->find();
                if(!$school_model)
                    continue;
                $tmp['school_value'] = $school_model['id'];
            }
            else
                continue;

            //检测班级
            if(!empty($tmp["team"]))
            {
                $team_model = Team::where("name",$tmp["team"])->find();
                if(!$team_model)
                    continue;

                $tmp['team_value'] = $team_model['id'];
            }

            //检测老师
            if(!empty($tmp['teacher']))
            {
                $teacher_model = User::scope("ins_id")->where("name",$tmp["teacher"])->find();
                if(!$teacher_model)
                    continue;

                $tmp['teacher_value'] = $teacher_model['id'];
            }

            //检测联系方式
            if(!empty($tmp['mobile']) && strlen($tmp['mobile']) != 11)
                continue;
            //检测销售人员
            if(!empty($tmp['saler']))
            {
                $saler_model = User::scope("ins_id")->where("name",$tmp["saler"])->find();
                if(!$saler_model)
                    continue;

                $tmp['saler_value'] = $saler_model['id'];
            }
            $re[] = $tmp;
        }

        return my_json($re);
    }
    //导入学生数据
    public function import(){
        $list = input("post.list");

        if(!is_array($list))
            return my_json([],-1,"导入学生数据格式不正确");
        if(empty($list))
            return my_json([],-1,"导入学生数据不能为空");

        $insert_data = [];
        foreach($list as $key => $value)
        {
            $insert_data[] = [
                "ins_id"    =>  $this->ins_id,
                "name"  =>  $value['name'],
                "sex"   =>  $value['sex_value'],
                "school_id" =>  $value['school_value'],
                "team_ids" =>  empty($value['team_value']) ? "" : $value['team_value'],
                "uids" =>  empty($value['teacher_value']) ? "":$value['teacher_value'],
                "mobile"    =>  $value['mobile'],
                "saler" =>  $value['saler_value'],
                "add_time"  =>  time(),
            ];
        }
        $student_model = new \app\ins\model\Student();
        $student_model->saveAll($insert_data);

        return my_json();
    }
}