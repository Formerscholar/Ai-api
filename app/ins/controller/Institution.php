<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 10:32
 */

namespace app\ins\controller;

//机构管理
use app\ins\model\Meal;
use app\Request;

class Institution extends Admin{
    //获得机构信息
    public function info(){
        $ins_model = \app\ins\model\Institution::find($this->ins_id);
        if(!$ins_model)
            return my_json([],-1,"未找到机构信息");
        $ins_data = $ins_model->getData();

        $meal_model = Meal::find($ins_data['meal_id']);
        if($meal_model)
            $ins_data['meal_data'] = $meal_model->getData();

        return my_json($ins_data);
    }
    //保存机构信息
    public function save(){
        $data = $this->request->except(["expire_time,is_enable"],"post");
        validate(\app\ins\validate\Institution::class)->check($data);

        $ins_model = \app\ins\model\Institution::find($this->ins_id);
        $ins_model->save($data);

        return my_json([],0,"机构信息修改成功");
    }
    //套餐列表
    public function mealList(){
        $list = Meal::get_all(["is_enable" => 1]);
        return my_json($list);
    }

    //

}