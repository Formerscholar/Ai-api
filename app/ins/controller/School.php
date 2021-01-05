<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//校区管理
use app\ins\model\StudentResult;
use app\ins\model\StudentStudy;
use app\Request;

class School extends Admin{

    //列表
    public function index(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");

        $where[] = ["ins_id","=",$this->ins_id];
        $keyword = input("get.keyword","");
        $province = input("get.province","");
        $city = input("get.city","");
        $area = input("get.area","");
        if($keyword)
            $where[] = ['name','like',"%{$keyword}%"];
        if($province)
            $where[] = ['province','=',$province];
        if($city)
            $where[] = ['city','=',$city];
        if($area)
            $where[] = ['area','=',$area];

        $list = \app\ins\model\School::get_page($where,"*","id DESC",$page,$limit);

        return my_json($list);
    }
    //添加
    public function add(){
        $post_data = request()->except(["id"]);
        validate(\app\ins\validate\School::class)->check($post_data);

        $post_data['ins_id'] = $this->ins_id;
        $post_data['add_time'] = time();
        $student_model = \app\ins\model\School::create($post_data);

        return my_json(["id"    =>  $student_model->id],0,"添加校区成功");
    }
    //编辑
    public function edit(){
        $id = input("get.id",0,"int");
        $model = \app\ins\model\School::scope("ins_id")->find($id);
        if(!$model)
            return my_json([],-1,"校区数据不存在");

        return my_json($model->getData());
    }
    //编辑保存
    public function save(){
        $data = request()->post();

        validate(\app\ins\validate\School::class)->check($data);

        $model = \app\ins\model\School::scope("ins_id")->find($data['id']);
        if(!$model)
            return my_json([],-1,"校区数据不存在");

        $data['update_time'] = time();
        $model->save($data);

        return my_json([],0,"编辑校区成功");
    }
    //删除
    public function delete(){
        $id = input("get.id",0,"int");
        if($id && !is_array($id))
            $id = [$id];
        if(empty($id))
            return my_json([],-1,"未选择要删除的数据");

        $model = new \app\ins\model\School();
        $batch_data = [];
        foreach($id as $i)
        {
            if($i)
                $batch_data[] = [
                    "id"    =>  $i,
                    "is_delete" => 1,
                    "delete_time" => time()
                ];
        }
        $model->saveAll($batch_data);

        return my_json([],0,"删除校区成功");
    }
}