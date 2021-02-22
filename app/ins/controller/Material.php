<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//课件教案
use aictb\Api;
use app\ins\model\Institution;
use app\ins\model\LocalGrade;
use app\ins\model\LocalSubject;
use app\ins\model\Paper;
use app\ins\model\Student;
use app\ins\model\StudentResult;
use app\ins\model\StudentStudy;
use app\ins\model\User;
use app\Request;

class Material extends Admin{
    //获得搜索条件
    public function getCondition(){
        //查询条件
        $condition = [];

        $curr_subject_ids = explode(",",$this->subject_ids);
        if(empty($this->subject_ids) || empty($curr_subject_ids))
            return my_json([],-1,"未设置老师科目信息");
        $condition['subject'] = LocalSubject::whereIn("id",$curr_subject_ids)->where("is_show",1)->field("id,title")->order("sort asc")->select()->toArray();

        $default_subject_id = $this->subject_id;
        if(empty($default_subject_id))
            $default_subject_id = current($curr_subject_ids);
        $condition['default_subject_id'] = $default_subject_id;

        $curr_grade_ids = current(Institution::where("id",$this->ins_id)->column("grade_ids"));
        if(empty($curr_grade_ids))
            return my_json([],-1,"未设置机构开通班级");

        $curr_grade_ids = explode(",",$curr_grade_ids);
        $condition['grade'] = LocalGrade::get_all([
            ['is_enable','=',1],
            ['is_delete','=',0],
            ["id","in",$curr_grade_ids],
        ],"id,name","sort ASC");//年级

        return my_json($condition);
    }
    //列表
    public function index(){
        $params = request()->only(["subject_id","grade_id","category_id","keyword","page"]);
        $ins_data = Institution::find($this->ins_id)->getData();
        $params['province_id'] = $ins_data['province'];

        //更新用户当前科目id
        if($this->subject_id != $params['subject_id'])
        {
            $this->subject_id = $params['subject_id'];
            User::update(["current_subject_id"  =>  $params['subject_id']],["id" =>  $this->uid]);
        }

        $api = new Api();
        $re = $api->getMaterialList($params);
        if(!$re)
            return my_json([],-1,$api->getError());
        //验证是否已经同步至我的课件
        if(!empty($re['data']))
        {
            $user_paper_list = \app\ins\model\Material::scope("ins_id")->where("uid",$this->uid)->where("is_sync",1)->field("id,sync_id,sync_time")->select()->toArray();
            $user_paper_list = array_column($user_paper_list,null,"sync_id");

            foreach($re['data'] as $key => $value)
            {
                if(isset($user_paper_list[$value['id']]))
                {
                    $re['data'][$key]['is_sync'] = 1;
                    $re['data'][$key]['local_id'] = $user_paper_list[$value['id']]['id'];
                    $re['data'][$key]['sync_time'] = $user_paper_list[$value['id']]['sync_time'];
                }
                else
                    $re['data'][$key]['is_sync'] = 0;
            }
        }
        return my_json($re);
    }
    //同步至本地
    public function syncToLocal(){
        $post_data = input("post.");
        validate(\app\ins\validate\Material::class)->scene("sync")->check($post_data);

        $material_model = \app\ins\model\Material::where("sync_id",$post_data['id'])->where("uid",$this->uid)->find();
        if($material_model)
            return my_json([],-1,"该课件已经同步了");

        $file_type = "";
        if($post_data['suffix'] == 1)
            $file_type = "WORD";
        else if($post_data['suffix'] == 2)
            $file_type = "PDF";
        else if($post_data['suffix'] == 3)
            $file_type = "PPT";

        $insert_data = [
            "ins_id"    =>  $this->ins_id,
            "school_id" =>  $this->school_id,
            "subject_id"    =>  $post_data['subject_id'],
            "grade_id"    =>  $post_data['grade_id'],
            "name"    =>  $post_data['name'],
            "info"    =>  $post_data['desc'],
            "file_src"    =>  $post_data['file_url'],
            "file_type"    =>  $file_type,
            "file_size"    =>  $post_data['size'],
            "add_time"    =>  time(),
            "uid"   =>  $this->uid,
            "is_sync"   =>  1,
            "sync_id"   =>  $post_data['id'],
            "sync_time" =>  time()
        ];

        $material_model = \app\ins\model\Material::create($insert_data);
        return my_json(["id"    =>  $material_model->id],0,"同步成功");
    }
    //详情
    public function detail(){

    }

    //我的课件
    public function my(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");
        $keyword = input("get.keyword","");
        $subject_id   = input("get.subject_id",0,"int");
        $grade_id   = input("get.grade_id",0,"int");

        //更新用户当前科目id
        if($this->subject_id != $subject_id)
        {
            $this->subject_id = $subject_id;
            User::update(["current_subject_id"  =>  $subject_id],["id" =>  $this->uid]);
        }

        $where[] = ["ins_id","=",$this->ins_id];
        $where[] = ['is_delete','=',0];
        if($keyword)
            $where[] = ['name','like',"%{$keyword}%"];

        if($subject_id)
            $where[] = ['subject_id','=',$subject_id];
        if($grade_id)
            $where[] = ['grade_id','=',$grade_id];

        $list = \app\ins\model\Material::get_page($where,"*","id DESC",$page,$limit);

        return my_json($list);
    }
    //上传课件
    public function upload(){

    }
    //删除我的课件
    public function delete(){

    }
}