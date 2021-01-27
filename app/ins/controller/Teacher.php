<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 10:32
 */

namespace app\ins\controller;

//老师管理
use app\ins\model\StudentStudy;
use app\ins\model\Team;
use app\ins\model\User;

class Teacher extends Admin{
    //老师列表
    public function index(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");
        $keyword = input("get.keyword","");
        $school_id   = input("get.school_id",0,"int");
        $subject_id   = input("get.subject_id",0,"int");

        $where[] = ["ins_id","=",$this->ins_id];
        $where[] = ['is_delete','=',0];
        if($keyword)
            $where[] = ['name|account','like',"%{$keyword}%"];
        if($school_id)
            $where[] = ['school_id','=',$school_id];
        if($subject_id)
            $where[] = ['subject_id','=',$subject_id];

        $list = User::get_page($where,"*","id DESC",$page,$limit);
        $list['list'] = User::format_list($list['list']);
        foreach($list['list'] as $key => $val)
        {
            $list['list'][$key]['team_data'] = Team::where("uids","find in set",$val['id'])->field("id,name")->select();
        }

        return my_json($list);
    }
    //老师编辑
    public function edit(){
        $id = input("get.id");

        $user_model = User::scope("ins_id")->field("*")->find($id);

        return my_json($user_model->getData());
    }
    //老师编辑保存
    public function save(){
        $data = request()->post();

        validate(\app\ins\validate\User::class)->scene("edit")->check($data);

        $user_model = User::scope("ins_id")->find($data['id']);
        if(!$user_model)
            return my_json([],-1,"老师数据不存在");

        $data['subject_ids'] = join(",",$data['subject_ids']);
        $data['update_time'] = time();
        if(isset($data['password']) && $data['password'])
            $data['password'] = md5($data['password'].$user_model['salt']);

        $user_model->save($data);

        return my_json([],0,"老师编辑保存成功");
    }
    //老师添加
    public function add(){
        $post_data = request()->except(["id"]);
        validate(\app\ins\validate\User::class)->scene("add")->check($post_data);

        $post_data['subject_ids'] = empty($post_data['subject_ids'])?"":join(",",$post_data['subject_ids']);
        $post_data['password'] = md5(config("my.default_password").config("my.password_secrect"));
        $post_data['salt'] = config("my.password_secrect");

        $post_data['ins_id'] = $this->ins_id;
        $post_data['role_id'] = 2;//角色：老师
        $post_data['add_time'] = time();
        $student_model = User::create($post_data);

        return my_json(["id"    =>  $student_model->id],0,"添加老师成功");
    }
    //老师删除
    public function delete(){
        $id = input("get.id",0,"int");
        if($id && !is_array($id))
            $id = [$id];
        if(empty($id))
            return my_json([],-1,"未选择要删除的数据");

        if(User::where("id","in",$id)->where("role_id",1)->count())
            return my_json([],-1,'不能删除机构管理员角色的人员');

        $model = new User();
        $batch_data = [];
        foreach($id as $i)
        {
            $batch_data[] = [
                "id"    =>  $i,
                "is_delete" => 1,
                "delete_time" => time()
            ];
        }
        $model->saveAll($batch_data);

        return my_json([],0,"删除老师成功");
    }
    //老师详情
    public function detail(){
        $id = input("get.id",0,"int");
        $teacher = User::scope("ins_id")->find($id);
        if(!$teacher)
            return my_json([],-1,"老师数据不存在");

        $re = [];
        //基本信息
        $re['info'] = current(User::format_list([$teacher->getData()]));
        $re['info']['team_data'] = Team::where("uids",'find in set',$id)->field("id,name")->select()->toArray();
        //上课记录
        $re['study_list'] = StudentStudy::format_list(StudentStudy::where("uid",$id)->select()->toArray()) ;

        return my_json($re);
    }
}