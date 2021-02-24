<?php
declare (strict_types = 1);

namespace app\ins\controller;

use aictb\Api;
use app\BaseController;
use app\ins\model\Basket;
use app\ins\model\Grade;
use app\ins\model\Institution;
use app\ins\model\Knowledge;
use app\ins\model\QuestionCategory;
use app\ins\model\Subject;
use app\ins\model\User;
use think\Request;

//题目
class Question extends Admin
{
    //题目列表
    public function index(){
        //过滤前端传递参数
        $data = request()->only(["subject","type","level","grade","knowledge","chapter","keyword"]);
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        //验证搜索条件是否合法
        $curr_subject_ids = explode(",",$this->subject_ids);
        if(empty($curr_subject_ids))
            return my_json([],-1,"未设置老师科目信息");

        if(!in_array($data['subject'],$curr_subject_ids))
            return my_json([],-1,"科目不存在");
        //更新用户当前科目id
        if($this->subject_id != $data['subject'])
        {
            $this->subject_id = $data['subject'];
            User::update(["current_subject_id"  =>  $data['subject']],["id" =>  $this->uid]);
        }

        if($data['type'])
        {
            $question_category_model = QuestionCategory::where([
                ["is_enable","=",1],
                ["is_delete","=",0],
                ["id","=",$data['type']]
            ])->find();
            if(!$question_category_model)
                return my_json([],-1,"题目类型不存在");
        }

        if($data['level'])
        {
            $level_list = config("my.question_level");
            $level_find = false;
            foreach($level_list as $l)
            {
                if($l['id'] == $data['level'])
                {
                    $level_find = true;
                    break;
                }
            }
            if(!$level_find)
                return my_json([],-1,"难度值不存在");
        }

        if($data['keyword'])
        {
            if(strpos($data['keyword']," "))
            {
                $data['keyword'] = array_filter(explode(" ",$data['keyword']));
            }
        }

        $curr_grade_ids = current(Institution::where("id",$this->ins_id)->column("grade_ids"));
        if(empty($curr_grade_ids))
            return my_json([],-1,"未设置机构开通班级");

        $curr_grade_ids = explode(",",$curr_grade_ids);
        if(!in_array($data['grade'],$curr_grade_ids))
            return my_json([],-1,"该年级尚未开通");

        if(empty($data['knowledge']) || !is_array($data['knowledge']))
        {
            $data['knowledge'] = [];
        }

        if(empty($data['chapter']) || !is_array($data['chapter']))
        {
            $data['chapter'] = [];
        }

        //搜索条件验证完毕

        //查询
        $ctb = new Api();
        $result = $ctb->getQuestionList([
            "subject_id"    =>  $data['subject'],
            "grade_id"  =>  $data['grade'],
            "type"  =>  $data['type'],
            "level"  =>  $data['level'],
            "title" =>  $data['keyword'],
            "knowledge_id"  =>  $data['knowledge'],
            "chapter_id"    =>  $data['chapter'],
            "page"  =>  $page
        ]);

        if($result === false)
            return my_json([],-1,$ctb->getError());
        //处理接口数据
        $question_page = [
            "count" =>  $result['total'],
            "total_page"    =>  $result['last_page'],
            "page"  =>  $page,
            "list"  =>  $result['data'],
        ];

        //检测是否已经添加到组卷栏中
        $basket_question_ids = Basket::getQuestionIds($this->uid,$this->subject_id);

        foreach($question_page['list'] as $key => $val)
        {
            if(in_array($val['id'],$basket_question_ids))
                $question_page['list'][$key]['has_add_basket'] = 1;
            else
                $question_page['list'][$key]['has_add_basket'] = 0;
        }
        return my_json($question_page);
    }
    //获得题目列表的搜索条件
    public function getSearchCondition(){
        $source_type = input("get.source_type",0,"int");

        //查询条件
        $condition = [];

        $condition['level'] = config("my.question_level");//难度

        $curr_subject_ids = explode(",",$this->subject_ids);
        if(empty($this->subject_ids) || empty($curr_subject_ids))
            return my_json([],-1,"未设置老师科目信息");
        $condition['subject'] = Subject::whereIn("id",$curr_subject_ids)->field("id,title")->order("sort asc")->select()->toArray();

        $default_subject_id = $this->subject_id;
        if(empty($default_subject_id))
            $default_subject_id = current($curr_subject_ids);
        $condition['default_subject_id'] = $default_subject_id;

        $curr_grade_ids = current(Institution::where("id",$this->ins_id)->column("grade_ids"));
        if(empty($curr_grade_ids))
            return my_json([],-1,"未设置机构开通班级");

        //年级
        $curr_grade_ids = explode(",",$curr_grade_ids);
        $condition['grade'] = Grade::get_all([
            ['is_enable','=',1],
            ['is_delete','=',0],
            ["id","in",$curr_grade_ids],
        ],"id,name","sort ASC");//年级

        //题型
        $ctb = new Api();
        $result = $ctb->getQuestionCategory([
            "subject_id"    =>  $default_subject_id
        ]);
        if($result === false)
            return my_json([],-1,$ctb->getError());
        $condition['type'] = $result;

        if($source_type)
        {
            //知识点
            $result = $ctb->getKnowledge([
                "subject_id"    =>  $default_subject_id,
                "grade_id"  =>  $curr_grade_ids[0]
            ]);
            if($result === false)
                return my_json([],-1,$ctb->getError());
            $condition['knowledge'] = $result;
        }
        else
        {
            //章节
            $ins_data = Institution::where("id",$this->ins_id)->field("province,city")->find();
            $result = $ctb->getChapter([
                "subject_id"    =>  $default_subject_id,
                "grade_id"  =>  $curr_grade_ids[0],
                "province_id"   =>  $ins_data['province'],
                "city_id"   =>  $ins_data['city'],
                "semester"  =>  get_semester()
            ]);
            if($result === false)
                return my_json([],-1,$ctb->getError());

            $condition['chapter'] = $result;
        }

        return my_json($condition);
    }
    //获得题目答案
    public function getAnswer(){
        $id = input("get.id");

        $model = \app\ins\model\Question::find($id);
        if(!$model)
            return my_json([],-1,"题目数据不存在");

        $data = $model->getData();
        $know_point_arr = $data['know_point']? explode(",",trim($data['know_point'],",")) : [];
        if(empty($know_point_arr))
            $data['know_point_names'] = "";
        else
        {
            $know_point_names = Knowledge::where("id","in",$know_point_arr)->column("title");
            $data['know_point_names'] = join(",",$know_point_names);
        }

        return my_json($data);
    }
    //获得题目详情
    public function getInfo(){
        $id = input("get.id");
        $count = input("get.count",1,"int");

        $re = [
            "question" => [],
            "about" =>  [],
        ];
        $model = \app\ins\model\Question::find($id);
        $re['question'] = $model->getData();

        //检测是否已经添加到组卷栏中
        $basket_question_ids = Basket::getQuestionIds($this->uid,$this->subject_id);
        if(in_array($re['question']['id'],$basket_question_ids))
            $re['question']['has_add_basket'] = 1;
        else
            $re['question']['has_add_basket'] = 0;

        $re['question']['type_name'] = QuestionCategory::where("id",$re['question']['type'])->column('title');
        if(!empty($re['question']['type_name']))
            $re['question']['type_name'] = join(",",$re['question']['type_name']);

        if($re['question']['know_point'])
            $question_know_point_list = Knowledge::get_all(["id" => array_filter(array_unique(explode(",",$re['question']['know_point'])))],"id,title");
        else
            $question_know_point_list = [];

        $re['question']['know_point_names'] = "";
        foreach($question_know_point_list as $p)
        {
            if(strstr(','.$re['question']['know_point'].',',(string)$p['id']))
            {
                $re['question']['know_point_names'] .= $p['title'];
            }
        }

        $re['about'] = \app\ins\model\Question::get_rand_list($this->subject_id,$model['type'],$count);
        //题型
        $question_types = array_column($re['about'],"type");
        $question_type_list = QuestionCategory::where("id","in",$question_types)->field("id,title")->select()->toArray();
        if(!empty($question_type_list))
            $question_type_list = array_column($question_type_list,null,"id");
        //知识点
        $question_know_point_ids = array_column($re['about'],"know_point");
        $question_know_point_list = Knowledge::get_all(["id" => array_filter(array_unique(explode(",",join(",",$question_know_point_ids))))],"id,title");
        if($question_know_point_list)
            $question_know_point_list = array_column($question_know_point_list,null,"id");
        foreach($re['about'] as $key => $val)
        {
            if(in_array($val['id'],$basket_question_ids))
                $re['about'][$key]['has_add_basket'] = 1;
            else
                $re['about'][$key]['has_add_basket'] = 0;

            $re['about'][$key]['type_name'] = isset($question_type_list[$val['type']])?$question_type_list[$val['type']]['title']:"";

            $re['about'][$key]['know_point_names'] = "";
            foreach($question_know_point_list as $p)
            {
                if(strstr(','.$val['know_point'].',',(string)$p['id']))
                {
                    $re['about'][$key]['know_point_names'] .= $p['title'];
                }
            }
        }

        return my_json($re);
    }
}
