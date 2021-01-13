<?php
namespace app\admin\controller;

use app\BaseController;

class Meal extends Admin
{
    public function index()
    {
        return "admin";
    }

    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
    }
}
