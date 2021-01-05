<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//组卷篮

use app\ins\model\Base;
use app\ins\model\Paper;
use app\ins\model\Question;
use app\ins\model\QuestionCategory;

class Basket extends Admin{
    //组卷栏信息
    public function detail(){
        $basket_model = \app\ins\model\Basket::where(["uid" =>  $this->uid])->fieldRaw("type,count(*) as c")->group('type')->select();

        $type_ids = array_column($basket_model->toArray(),"type");
        $type_list = QuestionCategory::get_all(["id" => $type_ids],"id,title");
        if($type_list)
            $type_list = array_column($type_list,null,"id");

        $re = [
            "totle_count"   =>  0,
            "list"  =>  [],
        ];
        foreach($basket_model as $item){
            $re['totle_count'] += $item['c'];
            $re['list'][] = [
                "id"    =>  $item['type'],
                "name"  =>  isset($type_list[$item['type']])?$type_list[$item['type']]['title']:"",
                "count" =>  $item['c'],
            ];
        }

        return my_json($re);
    }

    //组卷栏的题目列表
    public function index(){
        $order_by = request()->only(["type","sort"]);
        //需要排序的题目类型
        if(!is_array($order_by['type']))
            $order_by['type'] = [];
        //排序方式
        if(in_array($order_by['sort'],[1,2]))
        {
            if($order_by['sort'] == 1)
                $order_by['sort'] = "level ASC";
            else
                $order_by['sort'] = "level DESC";
        }
        else
            $order_by['sort'] = "sort ASC";

        $re = [
            "paper" =>  [
                "score" =>  0,
                "question_count"    =>  0,
                "level" =>  0,
                "detail"    =>  [
                    [
                        "type"  =>  1,
                        "name"  =>  '单选题',
                        "quesiton_ids"  =>  [

                        ]
                    ]
                ],
            ],
            "question"  =>  [
                [
                    "type"  =>  1,
                    "name"  =>  '单选题',
                    "question_list" =>  [

                    ],
                ]
            ],
        ];

        //来源试卷信息
        if(request()->has("id"))
        {
            $paper_id = request()->get("id",0,"int");
            $re['paper_config'] = Paper::find($paper_id);
        }

        $basket_model = \app\ins\model\Basket::where(["uid"   =>  $this->uid])->fieldRaw("type,count(*) as count")->group('type')->select();

        $type_list = QuestionCategory::get_all(["id" => array_column($basket_model->toArray(),"type")],"id,title");
        if($type_list)
            $type_list = array_column($type_list,null,"id");

        $paper = [];
        $paper['detail'] = [];
        $paper['score'] = \app\ins\model\Basket::where(["uid"   =>  $this->uid])->sum("score");//总分值
        $paper['question_count'] = \app\ins\model\Basket::where(["uid"   =>  $this->uid])->count();;//总题数
        $total_level = 0;//总难度

        $question = [];
        foreach($basket_model as $item)
        {
            $type_data = $item->getData();
            $type_data['name'] = isset($type_list[$type_data['type']])?$type_list[$type_data['type']]['title']:"";

            if(in_array($type_data['type'],$order_by['type']))
            {
                $question_reuslt = \app\ins\model\Basket::where(["uid" =>  $this->uid,"type"  =>  $type_data['type']])->field("question_id,score,sort")->order($order_by['sort'])->select();
            }
            else
            {
                $question_reuslt = \app\ins\model\Basket::where(["uid" =>  $this->uid,"type"  =>  $type_data['type']])->field("question_id,score,sort")->order($order_by['sort'])->select();
            }
            $question_id_arr = array_column($question_reuslt->toArray(),"question_id");
            $question_model = Question::where("id","in",$question_id_arr)->orderRaw("field(id,".join(",",$question_id_arr).")")->select();
            foreach($question_model as $item_quesiton)
            {
                $item_quesiton['score'] = array_column($question_reuslt->toArray(),null,"question_id")[$item_quesiton['id']]['score'];
                $item_quesiton['sort'] = array_column($question_reuslt->toArray(),null,"question_id")[$item_quesiton['id']]['sort'];
            }

            $total_level += array_sum(array_column($question_model->toArray(),"level"));

            $paper['detail'][] = array_merge($type_data,["quesiton_ids" => $question_id_arr]);
            $question[] = array_merge($type_data,["score" =>  \app\ins\model\Basket::where(["uid"   =>  $this->uid,"type" =>  $type_data['type']])->sum("score"),"question_list" => $question_model->toArray()]);
        }
        if($paper['question_count'])
            $paper['level'] = ceil($total_level / $paper['question_count']);
        else
            $paper['level'] = 0;
        $re['paper'] = $paper;
        $re['question'] = $question;

        return my_json($re);
    }

    //单题添加题目至组卷栏
    public function add(){
        $id = input("get.id",0,"int");

        if(\app\ins\model\Basket::where([
                "uid"   =>  $this->uid
            ])->count() >= config("my.basket_max_question"))
            return my_json([],-1,"一份试卷最多".config("my.basket_max_question")."道试题，请重新加载组卷篮");

        $question_model = Question::find($id);
        if(!$question_model || $question_model['is_delete'])
            return my_json([],-1,"题目数据不存在");

        if(\app\ins\model\Basket::where([
                "uid"   =>  $this->uid,
                "question_id"   =>  $id
            ])->count() > 0)
            return my_json([],-1,"组卷栏中已经存在该题目");

        $type_model = QuestionCategory::find($question_model['type']);
        if(!$type_model)
            return my_json([],-1,"题目类型为空");

        $model = \app\ins\model\Basket::create([
            "uid"   =>  $this->uid,
            "question_id"   =>  $id,
            "type"  =>  $question_model['type'],
            "level" =>  $question_model['level'],
            "score" =>  $type_model['score'],
            "add_time"  =>  time(),
        ]);
        \app\ins\model\Basket::update(["sort"   =>  $model->id],["id"  =>  $model->id]);

        return my_json(["id"    =>  $model->id],0,"操作成功");
    }
    //多题添加题目至组卷栏
    public function batchAdd(){
        $id_arr = input("get.id",0,"int");

        if(!is_array($id_arr))
            return my_json([],-1,"参数异常");


        if((\app\ins\model\Basket::where([
                "uid"   =>  $this->uid
            ])->count() + count($id_arr)) >= config("my.basket_max_question"))
            return my_json([],-1,"一份试卷最多".config("my.basket_max_question")."道试题，请重新加载组卷篮");

        $add_data = [];
        //过滤掉已经在组卷栏中的题目
        foreach($id_arr as $id)
        {
            $question_model = Question::find($id);
            if(!$question_model || $question_model['is_delete'])
                continue;

            $type_model = QuestionCategory::find($question_model['type']);
            if(!$type_model)
                continue;

            if(\app\ins\model\Basket::where([
                    "uid"   =>  $this->uid,
                    "question_id"   =>  $id
                ])->count() > 0)
                continue;

            $add_data[] = [
                "uid"   =>  $this->uid,
                "question_id"   =>  $id,
                "type"  =>  $question_model['type'],
                "level" =>  $question_model['level'],
                "score" =>  $type_model['score'],
                "add_time"  =>  time(),
            ];
        }

        $model = new \app\ins\model\Basket();
        $res = $model->saveAll($add_data);

        $update_data = [];
        foreach($res as $item)
        {
            $update_data[] = [
                "id"    =>  $item['id'],
                "sort"  =>  $item['id'],
            ];
        }
        $model->saveAll($update_data);

        return my_json(["id"    =>  array_column($res->toArray(),"id")],0,"操作成功");
    }

    //按题目id删除
    public function deleteById(){
        $id = input("get.id",0,"int");

        $basket_model = \app\ins\model\Basket::where([
            "uid"   =>  $this->uid,
            "question_id"   =>  $id
        ])->find();
        if(!$basket_model)
            return my_json([],-1,"未找到数据");

        $basket_model->delete();

        return my_json([],0,"操作成功");
    }
    //按题目类型删除
    public function deleteByType(){
        $type = input("get.type",0,"int");

        $basket_model = \app\ins\model\Basket::where([
            "uid"   =>  $this->uid,
            "type"   =>  $type
        ])->select();
        if(!$basket_model)
            return my_json([],-1,"未找到数据");

        $basket_model->delete();

        return my_json([],0,"操作成功");
    }
    //清空组卷栏
    public function deleteAll(){
        $basket_model = \app\ins\model\Basket::where([
            "uid"   =>  $this->uid,
        ])->select();
        if(!$basket_model)
            return my_json([],-1,"未找到数据");

        $basket_model->delete();

        return my_json([],0,"操作成功");
    }

    //排序
    public function sort(){
        $type = input("get.type",0,"int");//题目类型
        $id = input("get.id",0,"int");//题目id
        $sort = input("get.sort");//排序方向：up:上移，down:下移

        if(in_array($sort,["up","down"]) === false)
            return my_json([],-1,"排序值异常");

        $current_where = [
            "uid"    =>  $this->uid,
            "type"   =>  $type,
            "question_id" => $id
        ];

        $question_collection = \app\ins\model\Basket::where($current_where)->find();
        if(!$question_collection)
            return my_json([],-1,"未找到题目");
        $question_row = $question_collection->getData();

        $current_sort = $question_row['sort'];
        switch($sort){
            case "up":
                $min_sort = \app\ins\model\Basket::where([
                    "uid"   =>  $this->uid,
                    "type"  =>  $type
                ])->min("sort");
                if($current_sort == $min_sort)
                    return my_json([],-1,"已是第一题");
                else
                {
                    $preData = \app\ins\model\Basket::preData($current_where['uid'],$current_where['type'],$current_sort);
                    $else_where = [
                        "uid"    =>  $current_where['uid'],
                        "type"  =>  $current_where['type'],
                        "question_id"  =>  $preData['question_id'],
                    ];
                    $else_sort = $preData['sort'];
                    $bools = \app\ins\model\Basket::transPosition($current_where,$current_sort,$else_where,$else_sort);
                    if($bools){
                        return my_json([],0,"排序成功");
                    }else{
                        return my_json([],-1,"排序失败");
                    }
                }

                break;
            case "down":
                $max_sort = \app\ins\model\Basket::where([
                    "uid"   =>  $this->uid,
                    "type"  =>  $type
                ])->max("sort");

                if($current_sort == $max_sort)
                    return my_json([],-1,"已是最后一题");
                else
                {
                    $nextData = \app\ins\model\Basket::nextData($current_where['uid'],$current_where['type'],$current_sort);
                    $else_where = [
                        "uid"    =>  $current_where['uid'],
                        "type"  =>  $current_where['type'],
                        "question_id"  =>  $nextData['question_id'],
                    ];
                    $else_sort = $nextData['sort'];
                    $bools =\app\ins\model\Basket::transPosition($current_where,$current_sort,$else_where,$else_sort);
                    if($bools){
                        return my_json([],0,"排序成功");
                    }else{
                        return my_json([],-1,"排序失败");
                    }
                }

                break;
        }
    }

    //设置题目得分
    public function setScore(){
        $id = input("get.id",0,"int");
        $score = input("get.score",0,"int");

        $basket_model = \app\ins\model\Basket::where([
            "uid"   =>  $this->uid,
            "question_id"   =>  $id
        ])->find();
        if(!$basket_model)
            return my_json([],-1,"未找到数据");

        $basket_model['score'] = $score;
        $basket_model['update_time'] = time();
        $basket_model->save();

        return my_json([],0,"操作成功");
    }
}