<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 11:25
 */
namespace app\ins\model;

use think\Model;

class Right extends Model
{
    public static function checkRight($app,$controll,$action,$rights=[]){
        $row = self::where("app",$app)->where("controller",$controll)->where("action",$action)->find();
        if(!$row)
            return false;

        return in_array($row['id'],$rights);
    }
}