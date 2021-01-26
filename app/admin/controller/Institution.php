<?php
namespace app\admin\controller;

use app\admin\model\Meal;
use app\admin\model\School;
use app\admin\model\Student;
use app\admin\model\Teacher;
use app\admin\model\Team;
use app\BaseController;

class Institution extends Admin
{
    public function index()
    {
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        $where = [];
        $keyword = input("get.keyword","");
        if($keyword)
            $where[] = ['name','like',"%{$keyword}%"];

        $list = \app\admin\model\Institution::get_page($where,"*","id desc",$page,$limit);
        $list['list'] = \app\admin\model\Institution::format_list($list['list']);

        return my_json($list);
    }

    public function add(){
        $post_data = request()->except(["id"]);
        validate(\app\admin\validate\Institution::class)->check($post_data);

        if($post_data['mobile'])
        {
            if(Teacher::where("account",$post_data['mobile'])->count())
                return my_json([],-1,"该手机号码已经存在");
        }

        \think\facade\Db::startTrans();
        try {
            $post_data['add_time'] = time();
            $post_data['grade_ids'] = join(",",$post_data['grade_ids']);
            $model = \app\ins\model\Institution::create($post_data);

            $new_school_data = [
                "ins_id"    =>  $model->id,
                "name"  =>  "默认校区",
                "add_time"  =>  time(),
                "address"   =>  $post_data['address'],
            ];
            $school_model = School::create($new_school_data);

            if($post_data['mobile'])
            {
                $new_user_data = [
                    "ins_id"    =>  $model->id,
                    "account"   =>  $post_data['mobile'],
                    "password"  =>  md5(config("my.default_password").config("my.password_secrect")),
                    "salt"  =>  config("my.password_secrect"),
                    "add_time"  =>  time(),
                    "name"  =>  $post_data['mobile'],
                    "school_id" =>  $school_model->id,
                    "role_id"   =>  1,
                ];
                $user_model = Teacher::create($new_user_data);
            }

            // 提交事务
            \think\facade\Db::commit();
            return my_json(["id"    =>  $model->id],0,"添加机构成功");
        } catch (\Exception $e) {
            echo $e->getMessage();
            // 回滚事务
            \think\facade\Db::rollback();
            return my_json([],-1,"添加机构成功失败");
        }
    }

    public function edit(){
        $id = input("get.id",0,"int");
        $model = \app\admin\model\Institution::find($id);
        if(!$model)
            return my_json([],-1,"机构数据不存在");

        return my_json($model->getData());
    }

    public function save(){
        $post_data = request()->post();
        validate(\app\admin\validate\Institution::class)->check($post_data);

        $model = \app\admin\model\Institution::find($post_data['id']);
        if(!$model)
            return my_json([],-1,"机构数据不存在");

        $post_data['update_time'] = time();
        $post_data['grade_ids'] = join(",",$post_data['grade_ids']);
        $model->save($post_data);

        return my_json([],0,"编辑机构数据成功");
    }

    public function delete(){
        $id = input("get.id",0,"int");
        if($id && !is_array($id))
            $id = [$id];
        if(empty($id))
            return my_json([],-1,"未选择要删除的数据");

        $model = new \app\admin\model\Institution();
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

        return my_json([],0,"删除机构成功");
    }

    public function setMeal(){
        $id = input("post.id",0,"int");
        $meal_id = input("post.meal_id",0,"int");

        $ins = \app\admin\model\Institution::find($id);
        if(!$ins)
            return my_json([],-1,"未找到机构信息");

        $meal = Meal::find($meal_id);
        if(!$meal)
            return my_json([],-1,"未找到套餐信息");

        $update_data = [
            "meal_id"   =>  $meal_id,
            "meal_name" =>  $meal['name'],
            "max_student_count" =>  $meal['max_student_count'],
            "expire_time"   =>  $meal['days'] * 24 * 3600 + time(),
            "update_time"   =>  time(),
        ];
        $ins->save($update_data);

        return my_json([]);
    }
    //机构详情
    public function detail(){
        $id = input("get.id",0,"int");

        $ins = \app\admin\model\Institution::find($id);
        if(!$ins)
            return my_json([],-1,"未找到机构信息");

        $school_list = School::where("ins_id",$id)->field("id,name")->select()->toArray();

        foreach($school_list as $key=>$val)
        {
            $school_list[$key]['student_count'] = Student::where("school_id",$val['id'])->count();
            $school_list[$key]['teacher_count'] = Teacher::where("school_id",$val['id'])->count();
        }

        return my_json(["detail"    =>  $ins->getData(),"school_data" => $school_list]);
    }

    public function student(){
        $id = input("get.id",0,"int");
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");
        $keyword = input("get.keyword","");

        $ins = \app\admin\model\Institution::find($id);
        if(!$ins)
            return my_json([],-1,"未找到机构信息");

        $where = [];
        $where[] = ["ins_id","=",$id];
        if($keyword)
            $where[] = ['name','like',"%{$keyword}%"];

        $list = Student::get_page($where,"*","id desc",$page,$limit);
        $list['list'] = Student::format_list($list['list']);

        return my_json($list);
    }

    public function teacher(){
        $id = input("get.id",0,"int");
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");
        $keyword = input("get.keyword","");

        $ins = \app\admin\model\Institution::find($id);
        if(!$ins)
            return my_json([],-1,"未找到机构信息");

        $where = [];
        $where[] = ["ins_id","=",$id];
        if($keyword)
            $where[] = ['name','like',"%{$keyword}%"];

        $list = Teacher::get_page($where,"*","id desc",$page,$limit);
        $list['list'] = Teacher::format_list($list['list']);
        foreach($list['list'] as $key => $val)
        {
            $list['list'][$key]['team_data'] = Team::where("uids","find in set",$val['id'])->field("id,name")->select();
        }

        return my_json($list);
    }
}
