<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 9:09
 */
namespace app\ins\model;

use think\Model;

class QuestionCategory extends Base{
    protected $connection = "aictb";

    //根据题型id,返回以该id为索引的数据列表
    public static function getTypeList($ids = [],$field="*"){
        if(!is_array($ids))
            $ids = [intval($ids)];

        $list = self::where("id","in",$ids)->field($field)->select()->toArray();
        return array_column($list,null,"id");
    }
}