<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 10:32
 */

namespace app\ins\controller;

//老师管理
use app\admin\model\Role;
use app\ins\model\School;
use app\ins\model\StudentStudy;
use app\ins\model\Subject;
use app\ins\model\Team;
use app\ins\model\User;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\facade\Filesystem;

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
        $re = $this->checkImportData($datas);

        return my_json($re);
    }
    //导入数据
    public function import(){
        $list = input("post.list");

        if(!is_array($list))
            return my_json([],-1,"导入老师数据格式不正确");
        if(empty($list))
            return my_json([],-1,"导入老师数据不能为空");

        $filter_list = $this->filterImportData($list,"mobile");
        $insert_data = [];
        foreach($filter_list as $key => $value)
        {
            if(!$value['error'])
                $insert_data[] = [
                    "ins_id"    =>  $this->ins_id,
                    "account"   => $value['mobile'],
                    "password"  =>  md5(config("my.default_password").config("my.password_secrect")),
                    "salt"  =>  config("my.password_secrect"),
                    "name"  =>  $value['name'],
                    "add_time"  =>  time(),
                    "role_id"   =>  $value["role_value"],
                    "school_id" =>  $value["school_value"],
                    "subject_ids"   =>  empty($value["subject_value"])?"":join(",",$value['subject_value']),
                ];
        }
        $teacher_model = new \app\admin\model\Teacher();
        $teacher_model->saveAll($insert_data);

        return my_json();
    }
    //检测批量数据,返回所有数据
    protected function checkImportData($datas){
        $re = [];
        foreach($datas as $d)
        {
            $tmp = [
                "mobile"  =>  $d[0],
                "name"  =>  $d[1],
                "school"  =>  $d[2],
                "subject"  =>  $d[3],
                "role"  =>  $d[4],

                "error" => 0,
                "message" => ""
            ];
            //检测手机号码
            if(empty($tmp["mobile"]))
            {
                $tmp['error'] = 1;
                $tmp['message'] = "手机号码不能为空";
            }
            else if(!preg_match('/1[34578]{1}\d{9}/', $tmp["mobile"]))
            {
                $tmp['error'] = 1;
                $tmp['message'] = "手机号码格式不正确";
            }
            else{
                $teacher_exist = User::where("account",$tmp["mobile"])->count();
                if($teacher_exist)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "手机号码已经存在";
                }
            }
            //检测姓名
            if(empty($tmp["name"]) || mb_strlen($tmp["name"]) > 20)
            {
                $tmp['error'] = 1;
                $tmp['message'] = "姓名为空或者长度不能超过20个字符";
            }
            //检测校区
            if(!empty($tmp["school"]))
            {
                $school_model = School::scope("ins_id")->where("name",$tmp["school"])->find();
                if(!$school_model)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "未找到校区数据";
                }
                else
                    $tmp['school_value'] = $school_model['id'];
            }
            else
            {
                $tmp['error'] = 1;
                $tmp['message'] = "校区不能为空";
            }
            //检测学科
            if(!empty($tmp["subject"]))
            {
                $subject_names = explode("，",$tmp["subject"]);//中文的逗号

                $subject_ids = Subject::whereIn("title",$subject_names)->column("id");
                if(!empty($subject_ids))
                    $tmp['subject_value'] = $subject_ids;
                else
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "未找到学科数据";
                }
            }
            //检测角色
            if(!empty($tmp["role"]))
            {
                $role_model = Role::where("name",$tmp['role'])->find();
                if(!$role_model)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "未找到角色数据";
                }
                $tmp['role_value'] = $role_model['id'];
            }
            else
            {
                $tmp['error'] = 1;
                $tmp['message'] = "角色不能为空";
            }

            $re[] = $tmp;
        }

        return $re;
    }
    //过滤批量数据,返回过滤后的数据
    //$filter_key 按照哪个键去除重复值
    protected function filterImportData($datas,$filter_key){
        //根据$filter_key，过滤重复的值
        $filter_list = [];

        if($filter_key)
        {
            foreach($datas as $key => $value)
            {
                if(!empty($value[$filter_key]))
                {
                    $filter_list[trim($value[$filter_key],"")] = $value;
                }
            }
        }
        $filter_list = array_values($filter_list);
        $re = [];
        foreach($filter_list as $d)
        {
            $tmp = [
                "mobile"  =>  $d['mobile'],
                "name"  =>  $d['name'],
                "school"  =>  $d['school'],
                "subject"  =>  $d['subject'],
                "role"  =>  $d['role'],

                "error" => 0,
                "message" => ""
            ];
            //检测手机号码
            if(empty($tmp["mobile"]))
            {
                $tmp['error'] = 1;
                $tmp['message'] = "手机号码不能为空";
            }
            else if(!preg_match('/1[34578]{1}\d{9}/', $tmp["mobile"]))
            {
                $tmp['error'] = 1;
                $tmp['message'] = "手机号码格式不正确";
            }
            else{
                $teacher_exist = User::where("account",$tmp["mobile"])->count();
                if($teacher_exist)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "手机号码已经存在";
                }
            }
            //检测姓名
            if(empty($tmp["name"]) || mb_strlen($tmp["name"]) > 20)
            {
                $tmp['error'] = 1;
                $tmp['message'] = "姓名为空或者长度不能超过20个字符";
            }
            //检测校区
            if(!empty($tmp["school"]))
            {
                $school_model = School::scope("ins_id")->where("name",$tmp["school"])->find();
                if(!$school_model)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "未找到校区数据";
                }
                $tmp['school_value'] = $school_model['id'];
            }
            else
            {
                $tmp['error'] = 1;
                $tmp['message'] = "校区不能为空";
            }
            //检测学科
            if(!empty($tmp["subject"]))
            {
                $subject_names = explode("，",$tmp["subject"]);//中文的逗号

                $subject_ids = Subject::whereIn("title",$subject_names)->column("id");
                if(!empty($subject_ids))
                    $tmp['subject_value'] = $subject_ids;
            }
            //检测角色
            if(!empty($tmp["role"]))
            {
                $role_model = Role::where("name",$tmp['role'])->find();
                if(!$role_model)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "未找到角色数据";
                }
                $tmp['role_value'] = $role_model['id'];
            }
            else
            {
                $tmp['error'] = 1;
                $tmp['message'] = "角色不能为空";
            }

            $re[] = $tmp;
        }

        return $re;
    }
}