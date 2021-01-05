<?php
declare (strict_types = 1);

namespace app\ins\controller;

use app\BaseController;
use app\ins\model\Basket;
use app\ins\model\Grade;
use app\ins\model\Knowledge;
use app\ins\model\QuestionCategory;
use think\Request;

//题目
class Question extends Admin
{
    //题目列表
    public function index(){
        //查询条件
        $condition = [];

        $w = [
            ['is_enable','=',1],
            ['is_delete','=',0]
        ];
        $condition['level'] = config("my.question_level");//难度
        $condition['grade'] = Grade::get_all($w,"id,name","sort ASC");//年级
        $w[] = ['subject_ids','find in set',$this->subject_id];
        $condition['type'] = QuestionCategory::get_all($w,"id,title as name","sort ASC");//题型

        //处理前端传递参数
        $data = request()->only(["type","level","grade","knowledge"]);
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        //todo 验证条件是否合法

        $where_know = [
            ["subject_id","=",$this->subject_id]
        ];//知识点
        $where_question = [
            ["subject_id","=",$this->subject_id]
        ];//条目

        if($data['type'])
        {
            $where_question[] = ["type",'=',$data['type']];
        }
        if($data['level'])
        {
            $where_question[] = ['level','=',$data['level']];
        }
        //grade、knowledge 均为数组格式
        if($data['grade'] && is_array($data['grade']) && !in_array(0,$data['grade']))
        {
            $tmp2 = [];
            foreach($data['grade'] as $v)
                $tmp2[] = "FIND_IN_SET({$v},grade_id)";
            $where_question[] = ['grade_id','in',$data['grade']];
        }
        else
        {
            $tmp2 = [];
            foreach($condition['grade'] as $v)
                $tmp2[] = "FIND_IN_SET({$v['id']},grade_id)";
            $where_question[] = ['grade_id','in',array_column($condition['grade'],"id")];
        }
        if($data['knowledge'] && is_array($data['knowledge']))
        {
            $tmp = [];
            foreach($data['knowledge'] as $v)
                $tmp[] = "FIND_IN_SET({$v},know_point)";
        }
        $query = \app\ins\model\Question::where($where_question);
        if(isset($tmp))
            $query->where('('.join(' OR ', $tmp).')');
        $question_page = [];
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
        $question_page['count'] = $query->count();
        $question_page['total_page'] = ceil($question_page['count']/$limit);
        $question_page['page'] = $page;

        $query2 = Knowledge::where($where_know);
        if(isset($tmp2))
            $query2->where('('.join(' OR ', $tmp2).')');
        $knowledge_list = $query2->field('id,name,code,title,pid')->order('sort','asc')->select()->toArray();
        return my_json([
            "condition" =>  $condition,
            "question"  =>  $question_page,
            "knowledge" =>  $knowledge_list,
        ]);
    }
    //获得题目答案
    public function getAnswer(){
        $id = input("get.id");

        $model = \app\ins\model\Question::find($id);
        if(!$model)
            return my_json([],-1,"题目数据不存在");

        $data = $model->getData();
        $know_point_arr = explode(",",trim($data['know_point'],","));
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

        $model = \app\ins\model\Question::find($id);

        return my_json($model->getData());
    }
}
