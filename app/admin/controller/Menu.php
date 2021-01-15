<?php
namespace app\admin\controller;

use app\admin\model\Role;
use app\admin\model\Team;
use app\BaseController;

class Menu extends Admin
{
    //菜单列表
    public function index(){
        $menu_list = \app\admin\model\Menu::get_all([],"id,name,route,desc,pid,icon","sort asc");

        return my_json($menu_list);
    }
    //编辑
    public function edit(){
        $id = input("get.id");
        $menu_row = \app\admin\model\Menu::find($id);
        if(!$menu_row)
            return my_json([],-1,"菜单数据不存在");

        return my_json($menu_row->getData());
    }
    //编辑保存
    public function save(){
        $data = request()->post();
        validate(\app\admin\validate\Teacher::class)->scene("edit")->check($data);

        $user_model = \app\admin\model\Teacher::find($data['id']);
        if(!$user_model)
            return my_json([],-1,"老师数据不存在");

        $data['update_time'] = time();
        $user_model->save($data);

        return my_json([],0,"老师编辑保存成功");
    }
    //添加
    public function add(){
        return my_json([],-1,"暂未实现");
    }
    //删除
    public function delete(){
        return my_json([],-1,"暂未实现");
    }
}
