<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//课程管理
use app\ins\model\CourseBuy;
use app\ins\model\Student;
use app\ins\model\StudentResult;
use app\ins\model\StudentStudy;
use app\Request;

class Course extends Admin{

    //列表
    public function index(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        $where[] = ["ins_id","=",$this->ins_id];
        $keyword = input("get.keyword","");
        $school_id = input("get.school_id",0,"int");
        $subject_id = input("get.subject_id",0,"int");
        if($keyword)
            $where[] = ['name','like',"%{$keyword}%"];
        if($school_id)
            $where[] = ['school_id','like',"%{$school_id}%"];
        if($subject_id)
            $where[] = ['subject_id','like',"%{$subject_id}%"];

        $list = \app\ins\model\Course::get_page($where,"id,name,add_time,school_id,subject_id","id DESC",$page,$limit);
        $list['list'] = \app\ins\model\Course::format_list($list['list']);

        return my_json($list);
    }
    //添加
    public function add(){
        $post_data = request()->except(["id"]);
        validate(\app\ins\validate\Course::class)->check($post_data);

        $post_data['uids'] = $this->uid;
        $post_data['ins_id'] = $this->ins_id;
        $post_data['add_time'] = time();
        $student_model = \app\ins\model\Course::create($post_data);

        return my_json(["id"    =>  $student_model->id],0,"添加课程成功");
    }
    //编辑
    public function edit(){
        $id = input("get.id",0,"int");
        $model = \app\ins\model\Course::scope("ins_id")->find($id);
        if(!$model)
            return my_json([],-1,"课程数据不存在");

        return my_json($model->getData());
    }
    //编辑保存
    public function save(){
        $data = request()->post();

        validate(\app\ins\validate\Course::class)->check($data);

        $model = \app\ins\model\Course::scope("ins_id")->find($data['id']);
        if(!$model)
            return my_json([],-1,"课程数据不存在");

        $data['update_time'] = time();
        $model->save($data);

        return my_json([],0,"编辑课程成功");
    }
    //删除
    public function delete(){
        $id = input("get.id",0,"int");
        if($id && !is_array($id))
            $id = [$id];
        if(empty($id))
            return my_json([],-1,"未选择要删除的数据");

        $model = new \app\ins\model\Course();
        $batch_data = [];
        foreach($id as $i)
        {
            if($i)
                $batch_data[] = [
                    "id"    =>  $i,
                    "is_delete" => 1,
                    "delete_time" => time()
                ];
        }
        $model->saveAll($batch_data);

        return my_json([],0,"删除课程成功");
    }

    //课程购买记录列表
    public function buyList(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        $where[] = ["ins_id","=",$this->ins_id];
        $keyword = input("get.keyword","");
        $school_id = input("get.school_id",0,"int");
        if($keyword)
            $where[] = ['student_name','like',"%{$keyword}%"];
        if($school_id)
            $where[] = ["school_id",'=',$school_id];

        $list = CourseBuy::get_page($where,"*","add_time DESC",$page,$limit);
        $list['list'] = CourseBuy::format_list($list['list']);

        return my_json($list);
    }

    //课程购买记录添加
    public function addBuy(){
        $post_data = request()->except(["id"]);
        validate(\app\ins\validate\CourseBuy::class)->scene("add")->check($post_data);

        $student_model = Student::find($post_data['student_id']);
        if(!$student_model)
            return my_json([],-1,"未找到学生信息");

        $c = CourseBuy::scope("ins_id")->where([
            "course_id" =>  $post_data['course_id'],
            "student_id" =>  $post_data['student_id']
        ])->whereRaw("used_hour < buy_hour")->count();

        if($c)
            return my_json([],-1,"学生已经购买了该课程");

        $post_data['student_name'] = $student_model['name'];
        $post_data['uid'] = $this->uid;
        $post_data['ins_id'] = $this->ins_id;
        $post_data['add_time'] = time();
        $model = \app\ins\model\CourseBuy::create($post_data);

        return my_json(["id"    =>  $model->id],0,"添加购买记录成功");
    }

    //课程购买记录编辑
    public function editBuy(){
        $id = input("get.id",0,"int");
        $model = \app\ins\model\CourseBuy::scope("ins_id")->find($id);
        if(!$model)
            return my_json([],-1,"购买记录数据不存在");

        return my_json($model->getData());
    }

    //课程购买记录编辑保存
    public function saveBuy(){
        $data = request()->post();

        validate(\app\ins\validate\CourseBuy::class)->scene("edit")->check($data);

        $model = CourseBuy::scope("ins_id")->find($data['id']);
        if(!$model)
            return my_json([],-1,"购买记录数据不存在");

        $data['update_time'] = time();
        $model->save($data);

        return my_json([],0,"编辑购买记录成功");
    }

    //课程购买记录删除
    public function deleteBuy(){
        $id = input("get.id",0,"int");
        if($id && !is_array($id))
            $id = [$id];

        if(empty($id))
            return my_json([],-1,"未选择要删除的数据");

        $model = new CourseBuy();
        $batch_data = [];
        foreach($id as $i)
        {
            if($i)
                $batch_data[] = [
                    "id"    =>  $i,
                    "is_delete" => 1,
                    "delete_time" => time()
                ];
        }
        $model->saveAll($batch_data);

        return my_json([],0,"删除购买记录成功");
    }
}