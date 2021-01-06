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

    public static function get_rand_list($type,$limit = 1){
        $result = Db::connect("aictb")->query("SELECT * FROM `z_exercises` WHERE id >= (SELECT floor(RAND() * (SELECT MAX(id) FROM `z_exercises`))) AND type=? ORDER BY id LIMIT ?;",[$type,$limit]);
        return $result;
    }
}