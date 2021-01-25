<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 8:41
 */

namespace app\ins\controller;

//公共接口
use app\ins\model\Area;
use app\ins\model\Course;
use app\ins\model\Grade;
use app\ins\model\Institution;
use app\ins\model\Knowledge;
use app\ins\model\Paper;
use app\ins\model\PaperQuestion;
use app\ins\model\Question;
use app\ins\model\QuestionCategory;
use app\ins\model\Role;
use app\ins\model\School;
use app\ins\model\Student;
use app\ins\model\StudentResult;
use app\ins\model\Subject;
use app\ins\model\Team;
use app\ins\model\User;
use think\facade\Filesystem;

class Block extends Admin{
    //获得全部角色
    public function getAllRole(){
        return my_json(Role::get_all([],"id,name"));
    }
    //通过年级获得知识点列表
    public function getKnowledgeByGradIds(){
        $subject_id = input("get.subject_id",0,"int");
        $grade_ids = input("get.grade_ids");

        $where_know = [
            ["subject_id","=",$subject_id]
        ];//知识点

        $curr_grade_ids = current(Institution::where("id",$this->ins_id)->column("grade_ids"));
        if(empty($curr_grade_ids))
            return my_json([],-1,"未设置机构开通班级");

        $curr_grade_ids = explode(",",$curr_grade_ids);

        if(empty($grade_ids) || !is_array($grade_ids))
            $grade_ids = $curr_grade_ids;

        $grade_ids = array_values(array_intersect($grade_ids,$curr_grade_ids));
        $where_grade = [];
        if($grade_ids && is_array($grade_ids))
        {
            foreach($grade_ids as $v)
                $where_grade[] = "FIND_IN_SET({$v},grade_id)";
        }

        $knowledge_model = Knowledge::where($where_know)->where(join(' OR ', $where_grade))->field('id,name,code,title,pid')->order('sort','asc')->select();
        if(!$knowledge_model)
            return my_json([]);
//        echo Knowledge::getLastsql();exit;
        return my_json($knowledge_model->toArray());
    }
    //通过科目获得题型列表
    public function getTypeBySubjectId(){
        $subject_id = input("get.subject_id");

        $question_category_list = QuestionCategory::where([
            ["is_enable","=",1],
            ["is_delete","=",0],
            ["subject_ids","find in set",$subject_id]
        ])->field("id,title")->select();

        return my_json($question_category_list->toArray());
    }
    //获得学生的错题列表
    public function getStudentQuestionList(){
        $id = request()->get("id",0,"int");
        $subject_id = request()->get("subject_id",0,"int");
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        $wh = [];
        if($id)
            $wh[] = ["student_id","=",$id];
        if($subject_id)
            $wh[] = ["subject_id","=",$subject_id];

        $res = StudentResult::get_page($wh,"*","id DESC",$page,$limit);
        $question_ids = array_column($res['list'],"question_id");
        if(!empty($question_ids))
            $question_list = Question::where("id","in",$question_ids)->field("id,content_all")->orderRaw("field(id,".join(",",$question_ids).")")->select()->toArray();
        else
            $question_list = [];
        $question_list = array_column($question_list,null,"id");

        foreach($res['list'] as $key=>$val)
        {
            if(isset($val['question_id']))
            {
                $res['list'][$key]['question_data'] = isset($question_list[$val['question_id']])?$question_list[$val['question_id']]:[];
            }
        }

        return my_json($res);
    }
    //图片上传
    public function uploadImg(){
        $file = request()->file('image');
        if(empty($file))
            return my_json([],-1,"未检测到上传图片");

        $result = validate([
            'file'  =>  ['fileSize:102400,fileExt:gif,jpg,png']
        ])->check(["file"   =>  $file]);
        if($result)
        {
            //上传到服务器,
            $path = Filesystem::disk('public_html')->putFile('upload',$file);
            //结果是 $path = upload/20200825\***.jpg

            //图片路径，Filesystem::getDiskConfig('public','url')功能是获取public目录下的storage，
            $picCover = Filesystem::getDiskConfig('public_html','url').'/'.str_replace('\\','/',$path);
            //结果是 $picCover = storage/upload/20200825/***.jpg

            //获取图片名称
            $fileName = $file->getOriginalName();

            return my_json(["pic_src" => $picCover]);
        }
    }
    //获得课程列表
    public function getCourseList(){
        $school_id = request()->get("school_id",0,"int");

        $wh = [];
        if($school_id)
            $wh[] = ["school_ids","find in set",$school_id];
        $result = Course::scope("ins_id")->where($wh)->select();
        return my_json($result);
    }
    //获得试卷下题目列表
    public function getPaperQuestionList(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");
        $paper_id = input("get.paper_id",0,"int");

        $paper_row = Paper::find($paper_id);
        if(!$paper_row)
            return my_json([]);

        $local_question_list = PaperQuestion::where("paper_id",$paper_id)->where("parent_id",2)->field("question_id")->page($page)->limit($limit)->order('sort','asc')->select()->toArray();
        $question_ids = array_column($local_question_list,"question_id");

        $server_question_list = Question::where("id","in",$question_ids)->orderRaw("field(id,".join(",",$question_ids).")")->select()->toArray();
        $server_question_list = array_column($server_question_list,null,"id");
        foreach($local_question_list as $key => $val)
        {
            if(isset($server_question_list[$val['question_id']]))
                $local_question_list[$key]['question_data'] = $server_question_list[$val['question_id']];
        }

        return my_json($local_question_list);
    }
    //获得试卷列表
    public function getPaperList(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        $result = Paper::scope("ins_id")->page($page)->limit($limit)->select();
        $list = Paper::format_list($result->toArray());
        return my_json($list);
    }
    /*省市区缓存查询*/
    public function getArea(){
        $parent_id = request()->get("id",0,"int");
        $type = request()->get("type",0,"int");
        if($parent_id == -1)
            $result = Area::cache(true,3600)->select();
        else
            $result = Area::where("type",$type)->where("parent_id",$parent_id)->cache(true,3600)->select();
        return  my_json($result->toArray());
    }

    //获得老师列表
    public function getTeacherList(){
        $school_id = request()->get("school_id",0,"int");

        $wh = [];
        if($school_id)
            $wh[] = ["school_id","=",$school_id];
        return my_json(User::scope("ins_id")->where($wh)->field("id,name")->select());
    }
    //获得学生列表
    public function getStudentList(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");
        $keyword = input("get.keyword","");
        $school_id = request()->get("school_id",0,"int");

        $where = [];
        if($keyword)
            $where[] = ['name|mobile','like',"%{$keyword}%"];
        if($school_id)
            $where[] = ["school_id","=",$school_id];

        return my_json(Student::scope("ins_id")->where($where)->field("id,name")->page($page)->limit($limit)->select());
    }
    //获得班级列表
    public function getTeamList(){
        $school_id = request()->get("school_id",0,"int");

        $wh = [];
        if($school_id)
            $wh[] = ["school_id","=",$school_id];

        return my_json(Team::scope("ins_id")->where($wh)->field("id,name")->select());
    }
    //获得校区列表
    public function getSchoolList(){
        return my_json(School::scope("ins_id")->where("is_enable",'=',1)->field("id,name")->select());
    }
    //获得题目类型列表
    public function getQuestionType(){
        return my_json(QuestionCategory::get_all(["is_enable" => 1,"is_delete" => 0],"id,title,score","sort ASC"));
    }
    //获得年级列表
    public function getGradeList(){
        return my_json(Grade::get_all(["is_enable" => 1,"is_delete" => 0],"id,name","sort ASC"));
    }
    //获得学科列表
    public function getSubjectList(){
        return my_json(Subject::get_all(["is_enable" => 1,"is_delete" => 0,"is_show"    =>  1],"id,name,title,icon1,icon2,content","sort ASC"));
    }
}