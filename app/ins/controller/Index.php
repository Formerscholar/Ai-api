<?php
declare (strict_types = 1);

namespace app\ins\controller;

use app\BaseController;
use app\ins\model\Institution;
use app\ins\model\Question;
use app\ins\model\Role;
use app\ins\model\School;
use app\ins\model\Student;
use app\ins\model\Teacher;
use app\ins\model\User;
use org\Exercises;
use think\Request;
use WeChat\Oauth;

class Index extends Admin
{
    public function index(){;
        $school_list = School::scope("ins_id")->field("id,name")->select()->toArray();

        foreach($school_list as $key=>$val)
        {
            $school_list[$key]['student_count'] = Student::where("school_id",$val['id'])->count();
            $school_list[$key]['teacher_count'] = User::where("school_id",$val['id'])->count();
        }

        return my_json($school_list,0,"");
    }
}
