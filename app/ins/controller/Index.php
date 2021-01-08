<?php
declare (strict_types = 1);

namespace app\ins\controller;

use app\BaseController;
use app\ins\model\Institution;
use app\ins\model\Question;
use app\ins\model\Role;
use app\ins\model\Teacher;
use app\ins\model\User;
use org\Exercises;
use think\Request;
use WeChat\Oauth;

class Index extends Admin
{
    public function index(){
        return my_json([],0,"");
    }
}
