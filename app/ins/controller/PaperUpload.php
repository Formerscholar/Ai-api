<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 14:09
 */
namespace app\ins\controller;

//上传试卷管理

class PaperUpload extends Admin{
    //列表
    public function index(){
        $page = input("get.page",1,"int");
        $limit = input("get.limit",10,"int");
        $team_id = input("get.team_id",0,"int");
        $subject_id = input("get.subject_id",0,"int");
        $keyword = input("get.keyword","");

        $where[] = ["ins_id","=",$this->ins_id];
        $where[] = ["school_id","=",$this->school_id];
        if($keyword)
            $where[] = ['name','like',"%{$keyword}%"];
        if($team_id)
            $where = ['team_id','=',$team_id];
        if($subject_id)
            $where = ['subject_id','=',$subject_id];

        $list = \app\ins\model\PaperUpload::get_page($where,"*","id DESC",$page,$limit);

        return my_json($list);
    }
    //添加
    public function add(){
        $post_data = request()->post();
        validate(\app\ins\validate\PaperUpload::class)->check($post_data);

        $post_data['ins_id'] = $this->ins_id;
        $post_data['school_id'] = $this->school_id;
        $post_data['add_time'] = time();
        $model = \app\ins\model\PaperUpload::create($post_data);

        return my_json(["id"    =>  $model->id],0,"添加上传试卷成功");
    }
}