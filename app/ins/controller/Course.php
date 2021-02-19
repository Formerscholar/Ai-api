<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//课程管理
use app\ins\model\BuyType;
use app\ins\model\CourseBuy;
use app\ins\model\School;
use app\ins\model\Student;
use app\ins\model\StudentResult;
use app\ins\model\StudentStudy;
use app\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use think\facade\Db;
use think\facade\Filesystem;

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
            $where[] = ['school_ids','find in set',$school_id];
        if($subject_id)
            $where[] = ['subject_id','=',$subject_id];

        $list = \app\ins\model\Course::get_page($where,"id,name,add_time,school_ids,subject_id","id DESC",$page,$limit);
        $list['list'] = \app\ins\model\Course::format_list($list['list']);

        return my_json($list);
    }
    //添加
    public function add(){
        $post_data = request()->except(["id"]);
        validate(\app\ins\validate\Course::class)->check($post_data);

        $post_data['school_ids'] = join(",",$post_data['school_ids']);
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

        $data['school_ids'] = join(",",$data['school_ids']);
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
        $has_hour = input("get.has_hour");

        if($keyword)
            $where[] = ['student_name','like',"%{$keyword}%"];
        if($school_id)
            $where[] = ["school_id",'=',$school_id];
        if($has_hour !== "")
        {
            if($has_hour)
            {
                $where[] = Db::raw("buy_hour - used_hour > 0");
            }
            else
            {
                $where[] = Db::raw("buy_hour - used_hour <= 0");
            }
        }

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
    //上传课时记录附件
    public function uploadBuy(){
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
    //检测批量数据,返回所有数据
    protected function checkImportData($datas){
        $re = [];
        foreach($datas as $d)
        {
            $tmp = [
                "name"  =>  $d[0],
                "school"  =>  $d[1],
                "course"  =>  $d[2],
                "hour"  =>  $d[3],
                "type"  =>  $d[4],
                "money"  =>  $d[5],

                "error" => 0,
                "message" => ""
            ];
            //检测姓名
            if(empty($tmp["name"]) || mb_strlen($tmp["name"]) > 20)
            {
                $tmp['error'] = 1;
                $tmp['message'] = "姓名为空或者长度不能超过20个字符";
            }
            else
            {
                $student_model = \app\ins\model\Student::scope("ins_id")->where("name",$tmp["name"])->find();
                if(!$student_model)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "未找到该学生";
                }
                $tmp['name_value'] = $student_model['id'];
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

            //检测课程
            if(!empty($tmp['course']))
            {
                $course_model = \app\ins\model\Course::scope("ins_id")->where("name",$tmp['course'])->find();
                if(!$course_model)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "未找到课程数据";
                }
                $tmp['course_value'] = $course_model['id'];
            }
            else
            {
                $tmp['error'] = 1;
                $tmp['message'] = "课程不能为空";
            }

            //检测购买课时
            if(!empty($tmp['hour']))
            {
                if(!is_numeric($tmp['hour']) || $tmp['hour'] <= 0)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "购买课时必须是大于0的数值";
                }
            }
            else
            {
                $tmp['error'] = 1;
                $tmp['message'] = "购买课时不能为空";
            }

            //检测课时类型
            if(!empty($tmp["type"]))
            {
                $buy_type_model = BuyType::scope("ins_id")->where("name",$tmp['type'])->find();
                if(!$buy_type_model)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "课时类型不存在";
                }
                $tmp['type_value'] = $buy_type_model['id'];
            }

            //检测金额
            if(!empty($tmp['money']))
            {
                if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $tmp['money']))
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "金额格式不正确";
                }
            }

            $re[] = $tmp;
        }

        return $re;
    }
    //导入课时记录
    public function importBuy(){
        $list = input("post.list");

        if(!is_array($list))
            return my_json([],-1,"导入数据格式不正确");
        if(empty($list))
            return my_json([],-1,"导入数据不能为空");

        $insert_data = [];
        $filter_list = $this->filterImportData($list,"name");

        foreach($filter_list as $key => $value)
        {
            if(!$value['error'])
                $insert_data[] = [
                    "ins_id"    =>  $this->ins_id,
                    "school_id" =>  $value['school_value'],
                    "course_id" =>  $value['course_value'],
                    "student_id"    =>  $value['name_value'],
                    "student_name"  =>  $value['name'],
                    "buy_hour"  =>  $value['hour'],
                    "type"  =>  $value['type_value'],
                    "pay_money" =>  $value['money'],
                    "add_time"  =>  time(),
                    "uid"   =>  $this->uid
                ];
        }
        $student_model = new CourseBuy();
        $student_model->saveAll($insert_data);

        return my_json();
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
                "name"  =>  $d['name'],
                "school"    =>  $d['school'],
                "course"    =>  $d['course'],
                "hour"  =>  $d['hour'],
                "type"  =>  $d['type'],
                "money" =>  $d['money'],

                "error" => 0,
                "message" => ""
            ];
            //检测姓名
            if(empty($tmp["name"]) || mb_strlen($tmp["name"]) > 20)
            {
                $tmp['error'] = 1;
                $tmp['message'] = "姓名为空或者长度不能超过20个字符";
            }
            else
            {
                $student_model = \app\ins\model\Student::scope("ins_id")->where("name",$tmp["name"])->find();
                if(!$student_model)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "未找到该学生";
                }
                $tmp['name_value'] = $student_model['id'];
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

            //检测课程
            if(!empty($tmp['course']))
            {
                $course_model = \app\ins\model\Course::scope("ins_id")->where("name",$tmp['course'])->find();
                if(!$course_model)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "未找到课程数据";
                }
                $tmp['course_value'] = $course_model['id'];
            }
            else
            {
                $tmp['error'] = 1;
                $tmp['message'] = "课程不能为空";
            }

            //检测购买课时
            if(!empty($tmp['hour']))
            {
                if(!is_numeric($tmp['hour']) || $tmp['hour'] <= 0)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "购买课时必须是大于0的数值";
                }
            }
            else
            {
                $tmp['error'] = 1;
                $tmp['message'] = "购买课时不能为空";
            }

            //检测课时类型
            if(!empty($tmp["type"]))
            {
                $buy_type_model = BuyType::scope("ins_id")->where("name",$tmp['type'])->find();
                if(!$buy_type_model)
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "课时类型不存在";
                }
                $tmp['type_value'] = $buy_type_model['id'];
            }

            //检测金额
            if(!empty($tmp['money']))
            {
                if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $tmp['money']))
                {
                    $tmp['error'] = 1;
                    $tmp['message'] = "金额格式不正确";
                }
            }
            $re[] = $tmp;
        }

        return $re;
    }
}