<?php
declare (strict_types = 1);

namespace app\ins\controller;

use think\Request;

class BuyType extends Admin
{
    public function index()
    {
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        $where[] = ["ins_id","=",$this->ins_id];
        $keyword = input("get.keyword","");
        if($keyword)
            $where[] = ['name','like',"%{$keyword}%"];

        $list = \app\ins\model\BuyType::get_page($where,"*","id DESC",$page,$limit);

        return my_json($list);
    }

    public function add(Request $request)
    {
        $insert_data = $request->except(["id"],"post");
        validate(\app\ins\validate\BuyType::class)->check($insert_data);

        $insert_data['ins_id'] = $this->ins_id;
        $insert_data['add_time'] = time();
        $model = \app\ins\model\BuyType::create($insert_data);

        return my_json(["id"    =>  $model->id],0,"添加课时类型成功");
    }

    public function edit($id)
    {
        $model = \app\ins\model\BuyType::find($id);
        if(!$model)
            return my_json([],-1,"未找到课时类型数据");

        return my_json($model->getData());
    }

    public function save(Request $request)
    {
        $update_data = $request->post();
        validate(\app\ins\validate\BuyType::class)->check($update_data);

        $model = \app\ins\model\BuyType::find($update_data['id']);
        if(!$model)
            return my_json([],-1,"未找到课时类型数据");

        $update_data['update_time'] = time();
        $model->save($update_data);

        return my_json([],0,"编辑课时类型成功");
    }

    public function delete($id)
    {
        \app\ins\model\BuyType::where("id",$id)->delete();
        return my_json([]);
    }
}
