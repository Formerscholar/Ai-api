<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 9:09
 */
namespace app\ins\model;

use think\facade\Db;
use think\Model;

class Question extends Base{
    protected $connection = "aictb";
    protected $name = 'exercises';

    public static function get_rand_list($subject_id,$type,$limit = 1){
        $result = Db::connect("aictb")->query("SELECT * FROM `z_exercises` WHERE id >= (SELECT floor(RAND() * (SELECT MAX(id) FROM `z_exercises`))) AND subject_id = ? AND type=? ORDER BY id LIMIT ?;",[$subject_id,$type,$limit]);
        return $result;
    }
}