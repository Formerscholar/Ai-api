<?php
namespace app\admin\controller\Meal;

use app\admin\controller\Admin;
use app\BaseController;

class Order extends Admin
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
