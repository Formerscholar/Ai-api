<?php
declare (strict_types = 1);

namespace app\ins\controller;

use app\BaseController;
use app\ins\model\Basket;
use app\ins\model\Grade;
use app\ins\model\Institution;
use app\ins\model\Knowledge;
use app\ins\model\QuestionCategory;
use app\ins\model\Subject;
use think\Request;

//题目
class Question extends Admin
{
    //题目列表
    public function index(){
        //过滤前端传递参数
        $data = request()->only(["subject","type","level","grade","knowledge"]);
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        //验证搜索条件是否合法
        $curr_subject_ids = explode(",",$this->subject_ids);
        if(empty($curr_subject_ids))
            return my_json([],-1,"未设置老师科目信息");

        if(!in_array($data['subject'],$curr_subject_ids))
            return my_json([],-1,"科目不存在");

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


        $curr_grade_ids = current(Institution::where("id",$this->ins_id)->column("grade_ids"));
        if(empty($curr_grade_ids))
            return my_json([],-1,"未设置机构开通班级");

        $curr_grade_ids = explode(",",$curr_grade_ids);
        if($data['grade'])
        {
            if(!is_array($data['grade']))
                return my_json([],-1,"年级必须是数组");

            $data['grade'] = array_values(array_intersect($data['grade'],$curr_grade_ids));
        }

        if(empty($data['knowledge']) || !is_array($data['knowledge']))
        {
            $data['knowledge'] = [];
        }

        //搜索条件验证完毕

        //查询
        $where_question = [
            ["subject_id","=",$data['subject']]
        ];//题目

        if($data['type'])
        {
            $where_question[] = ["type",'=',$data['type']];
        }
        if($data['level'])
        {
            $where_question[] = ['level','=',$data['level']];
        }
        //grade、knowledge 均为数组格式
        if($data['grade'])
            $where_question[] = ['grade_id','in',$data['grade']];
        else
            $where_question[] = ['grade_id','in',$curr_grade_ids];

        $where_knowledge = [];
        if($data['knowledge'])
        {
            foreach($data['knowledge'] as $v)
                $where_knowledge[] = "FIND_IN_SET({$v},know_point)";
        }


        $query = \app\ins\model\Question::where($where_question)->where(join(' OR ', $where_knowledge));
        $question_page = [];
        $question_page['count'] = $query->count();
        $question_page['total_page'] = ceil($question_page['count']/$limit);
        $question_page['page'] = $page;
        $question_page['list'] = $query->page($page)->limit($limit)->select()->toArray();

        //检测是否已经添加到组卷栏中
        $basket_question_ids = Basket::getQuestionIds($this->uid);
        //题型
        $question_types = array_column($question_page['list'],"type");
        $question_type_list = QuestionCategory::where("id","in",$question_types)->field("id,title")->select()->toArray();
        if(!empty($question_type_list))
            $question_type_list = array_column($question_type_list,null,"id");
        //知识点
        $question_know_point_ids = array_column($question_page['list'],"know_point");
        $question_know_point_list = Knowledge::get_all(["id" => array_filter(array_unique(explode(",",join(",",$question_know_point_ids))))],"id,title");
        if($question_know_point_list)
            $question_know_point_list = array_column($question_know_point_list,null,"id");
        foreach($question_page['list'] as $key => $val)
        {
            if(in_array($val['id'],$basket_question_ids))
                $question_page['list'][$key]['has_add_basket'] = 1;
            else
                $question_page['list'][$key]['has_add_basket'] = 0;

            $question_page['list'][$key]['type_name'] = isset($question_type_list[$val['type']])?$question_type_list[$val['type']]['title']:"";

            $question_page['list'][$key]['know_point_names'] = "";
            foreach($question_know_point_list as $p)
            {
                if(strstr(','.$val['know_point'].',',(string)$p['id']))
                {
                    $question_page['list'][$key]['know_point_names'] .= $p['title'];
                }
            }
        }
        return my_json($question_page);
    }
    //获得题目列表的搜索条件
    public function getSearchCondition(){
        //查询条件
        $condition = [];

        $condition['level'] = config("my.question_level");//难度

        $curr_subject_ids = explode(",",$this->subject_ids);
        $condition['subject'] = Subject::whereIn("id",$curr_subject_ids)->field("id,title")->orderRaw("field(id,".join(",",$curr_subject_ids).")")->select()->toArray();
        if(empty($curr_subject_ids))
            return my_json([],-1,"未设置老师科目信息");
        $default_subject_id = current($curr_subject_ids);

        $curr_grade_ids = current(Institution::where("id",$this->ins_id)->column("grade_ids"));
        if(empty($curr_grade_ids))
            return my_json([],-1,"未设置机构开通班级");

        $curr_grade_ids = explode(",",$curr_grade_ids);
        $condition['grade'] = Grade::get_all([
            ['is_enable','=',1],
            ['is_delete','=',0],
            ["id","in",$curr_grade_ids],
        ],"id,name","sort ASC");//年级

        $question_category_list = QuestionCategory::where([
            ["is_enable","=",1],
            ["is_delete","=",0],
            ["subject_ids","find in set",$default_subject_id]
        ])->field("id,title")->select();
        //题型
        if($question_category_list)
            $condition['type'] = $question_category_list->toArray();
        else
            $condition['type'] = [];

        //知识点
        $where_grade = [];
        foreach($curr_grade_ids as $v)
            array_push($where_grade,"FIND_IN_SET({$v},grade_id)");

        $knowledge_model = Knowledge::where("subject_id",$default_subject_id)->where(join(' OR ', $where_grade))->field('id,name,code,title,pid')->order('sort','asc')->select();
        if($knowledge_model)
            $condition['knowledge'] = $knowledge_model->toArray();
        else
            $condition['knowledge'] = [];

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

        $re = [
            "question" => [],
            "about" =>  [],
        ];
        $model = \app\ins\model\Question::find($id);
        $re['question'] = $model->getData();

        //检测是否已经添加到组卷栏中
        $basket_question_ids = Basket::getQuestionIds($this->uid);
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

        $re['about'] = \app\ins\model\Question::get_rand_list($model['type'],1);
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
