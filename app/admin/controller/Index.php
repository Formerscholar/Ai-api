<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/12/22
 * Time: 0:32
 */
namespace app\admin\controller;

use app\BaseController;

class Index extends BaseController
{
    public function index(){
        return app('http')->getName();
    }
}