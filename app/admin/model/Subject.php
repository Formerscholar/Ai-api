<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 9:09
 */
namespace app\admin\model;

use think\Model;

class Subject extends Base{
    protected $connection = "aictb";

    //根据学科id,返回以该id为索引的数据列表
    public static function getSubjectList($subject_ids = [],$field="*"){
        if(!is_array($subject_ids))
            $subject_ids = [intval($subject_ids)];

        $list = self::where("id","in",$subject_ids)->field($field)->select()->toArray();
        return array_column($list,null,"id");
    }
}