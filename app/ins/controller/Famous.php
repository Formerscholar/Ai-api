<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//名校资源
use aictb\Api;
use app\ins\model\Institution;
use app\ins\model\LocalGrade;
use app\ins\model\LocalSubject;
use app\ins\model\Paper;
use app\ins\model\PaperQuestion;
use app\ins\model\Student;
use app\ins\model\StudentResult;
use app\ins\model\StudentStudy;
use app\ins\model\User;
use app\Request;

class Famous extends Admin{
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

        //分类
        $api = new Api();
        $re = $api->getFamousCategory();
        $condition['category'] = [];
        if($re !== false)
            $condition['category'] = $re;

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
        $re = $api->getFamousList($params);
        if($re === false)
            return my_json([],-1,$api->getError());
        //验证是否已经同步至我的试卷
        if(!empty($re['data']))
        {
            $user_paper_list = Paper::scope("ins_id")->where("uid",$this->uid)->where("is_famous",1)->field("id,famous_id,sync_time")->select()->toArray();
            $user_paper_list = array_column($user_paper_list,null,"famous_id");

            foreach($re['data'] as $key => $value)
            {
                if(isset($user_paper_list[$value['id']]))
                {
                    $re['data'][$key]['is_famous'] = 1;
                    $re['data'][$key]['local_paper_id'] = $user_paper_list[$value['id']]['id'];
                    $re['data'][$key]['sync_time'] = $user_paper_list[$value['id']]['sync_time'];
                }
                else
                    $re['data'][$key]['is_famous'] = 0;
            }
        }
        return my_json($re);
    }
    //同步至本地试卷库
    public function syncToLocal(){
        $sync_id = input("post.sync_id",0,"int");

        $ctb = new Api();
        $result = $ctb->getSchoolResourcesDetail([
            "exams_id"  =>  $sync_id
        ]);
        if($result === false)
            return my_json([],-1,$ctb->getError());

        $paper_exist = Paper::scope("ins_id")->where("uid",$this->uid)->where("is_famous",1)->where("famous_id",$sync_id)->count();
        if($paper_exist)
            return my_json([],-1,"该试卷已经同步过了");

        $insert_paper_data = [
            "ins_id"    =>  $this->ins_id,
            "subject_id"    =>  $result['exams']['subject_id'],
            "title" =>  $result['exams']['title'],
            "uid"   =>  $this->uid,
            "is_famous" =>  1,
            "famous_id" =>  $sync_id,
            "add_time"  =>  time(),
            "sync_time" =>  time()
        ];
        $insert_paper_question_data = [];
        foreach($result['examsExercisesList'] as $item){
            $insert_paper_question_data[] = [
                "parent_id"  =>  $item['parent_id'],
                "question_id"   =>  $item['exercises_id'],
                "add_time"  =>  time(),
                "score" =>  $item['score'],
                "title" =>  $item['title'],
//                "paper_id"
//                "sort"
            ];
        }
        \think\facade\Db::startTrans();
        try {
            //插入试卷数据
            $paper_model = \app\ins\model\Paper::create($insert_paper_data);
            foreach($insert_paper_question_data as $key => $val)
            {
                $insert_paper_question_data[$key]['paper_id'] = $paper_model->id;
            }
            //插入试卷题目关系数据
            $paper_question_model = new PaperQuestion();
            $insert_list = $paper_question_model->saveAll($insert_paper_question_data)->toArray();
            if(!empty($insert))
            {
                $insert_ids = array_column($insert_list,"id");
                $update_data = [];
                foreach($insert_ids as $id)
                {
                    $update_data[] = [
                        "id"    =>  $id,
                        "sort"  =>  $id,
                    ];
                }
                $paper_question_model->saveAll($update_data);
            }

            // 提交事务
            \think\facade\Db::commit();

            return my_json([],0,"操作成功");
        } catch (\Exception $e) {
            // 回滚事务
            \think\facade\Db::rollback();

            return my_json([],-1,$e->getMessage());
        }

    }
    //试卷详情
    public function detail(){

    }
    //下载
    public function download(){

    }
}