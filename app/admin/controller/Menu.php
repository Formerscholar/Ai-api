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
        $post_data = request()->post();
        validate(\app\admin\validate\Menu::class)->scene("edit")->check($post_data);

        $menu_model = \app\admin\model\Menu::find($post_data['id']);
        if(!$menu_model)
            return my_json([],-1,"菜单数据不存在");

        $post_data['update_time'] = time();
        $menu_model->save($post_data);

        return my_json([],0,"菜单编辑保存成功");
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
