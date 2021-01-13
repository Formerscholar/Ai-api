<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 14:27
 */
namespace app\admin\model;
use think\Model;
use think\model\concern\SoftDelete;

class Institution extends Base{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    public static function format_list(Array $list = []){
        $grade_ids = fetch_array_value($list,'grade_ids');
        $grade_list = Grade::get_all(["id" => array_filter(array_unique(explode(",",join(",",$grade_ids))))],"id,name");
        if($grade_list)
            $grade_list = array_column($grade_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['grade_ids']))
            {
                $item['grade_data'] = [];

                foreach($grade_list as $value)
                {
                    if(strstr(','.$item['grade_ids'].',',(string)$value['id']))
                    {
                        $item['grade_data'][] = $value;
                    }
                }
            }
        }

        return $list;
    }
}