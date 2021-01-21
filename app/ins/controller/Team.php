<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//班级管理
use app\ins\model\Student;
use app\ins\model\StudentResult;
use app\ins\model\StudentStudy;
use app\Request;

class Team extends Admin{

    //列表
    public function index(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        $where[] = ["ins_id","=",$this->ins_id];
        $keyword = input("get.keyword","");
        $school_id = input("get.school_id",0,"int");
        if($keyword)
            $where[] = ['name','like',"%{$keyword}%"];
        if($school_id)
            $where[] = ['school_id',"=",$school_id];

        $list = \app\ins\model\Team::get_page($where,"*","add_time DESC",$page,$limit);
        $list['list'] = \app\ins\model\Team::format_list($list['list']);

        return my_json($list);
    }
    //添加
    public function add(){
        $post_data = request()->except(["id"]);
        validate(\app\ins\validate\Team::class)->check($post_data);

        $post_data['ins_id'] = $this->ins_id;
        $post_data['add_time'] = time();
        $post_data['uids'] = join(",",$post_data['uids']);
        $student_model = \app\ins\model\Team::create($post_data);

        return my_json(["id"    =>  $student_model->id],0,"添加班级成功");
    }
    //编辑
    public function edit(){
        $id = input("get.id",0,"int");
        $model = \app\ins\model\Team::scope("ins_id")->find($id);
        if(!$model)
            return my_json([],-1,"班级数据不存在");

        return my_json($model->getData());
    }
    //编辑保存
    public function save(){
        $data = request()->post();

        validate(\app\ins\validate\Team::class)->check($data);

        $model = \app\ins\model\Team::scope("ins_id")->find($data['id']);
        if(!$model)
            return my_json([],-1,"班级数据不存在");

        $data['update_time'] = time();
        $data['uids'] = join(",",$data['uids']);
        $model->save($data);

        return my_json([],0,"编辑班级成功");
    }
    //删除
    public function delete(){
        $id = input("get.id",0,"int");
        if($id && !is_array($id))
            $id = [$id];
        if(empty($id))
            return my_json([],-1,"未选择要删除的数据");

        $model = new \app\ins\model\Team();
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

        return my_json([],0,"删除班级成功");
    }
    //详情
    public function detail(){
        $id = input("get.id",0,"int");

        $team_model = \app\ins\model\Team::find($id);
        if(!$team_model)
            return my_json([],-1,"未找到班级数据");

        $re = current(\app\ins\model\Team::format_list([$team_model->getData()]));
        return my_json($re);
    }
    //详情下学生列表
    public function detailStudentList(){
        $page = input("get.page",0,"int");
        $limit = input("get.limit",10,"int");
        $id = input("get.id",0,"int");

        $where = [];
        $where[] = ["team_ids","find in set",$id];
        $student_list = Student::get_page($where,"*","id DESC",$page,$limit);
        $student_list['list'] = Student::format_list($student_list['list']);

        return my_json($student_list);
    }
}