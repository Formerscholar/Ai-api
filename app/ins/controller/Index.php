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
use think\facade\View;
use think\Request;
use WeChat\Oauth;

class Index extends BaseController
{
    public function index(){
        return View::fetch();
    }
}
