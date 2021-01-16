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
                $item['grade_ids'] = array_map(function($v){ return (int)$v; },explode(",",$item['grade_ids']));

                foreach($item['grade_ids'] as $grade_id)
                {
                    if(isset($grade_list[$grade_id]))
                        $item['grade_data'][] = $grade_list[$grade_id];
                }
            }
        }

        return $list;
    }
}