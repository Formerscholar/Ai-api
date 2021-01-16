<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 9:09
 */
namespace app\ins\model;

use think\Model;
use think\model\concern\SoftDelete;

class Course extends Base{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $readonly = ['ins_id'];

    public function scopeIns_Id($query){
        $user = session("user");
        if($user)
        {
            $query->where("ins_id",$user['ins_id']);
        }
    }

    public static function format_list(Array $list = []){
        $school_ids = fetch_array_value($list,'school_ids');
        $school_list = School::get_all(["id" => array_filter(array_unique(explode(",",join(",",$school_ids))))],"id,name");
        if($school_list)
            $school_list = array_column($school_list,null,"id");

        $subject_ids = fetch_array_value($list,'subject_id');
        $subject_list = Subject::get_all(["id" => $subject_ids],"id,name,title,icon1,icon2");
        if($subject_list)
            $subject_list = array_column($subject_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['school_ids']))
            {
                $item['school_data'] = [];
                $item['school_ids'] = array_map(function($v){ return (int)$v; },explode(",",$item['school_ids']));

                foreach($item['school_ids'] as $school_id)
                {
                    if(isset($school_list[$school_id]))
                        $item['school_data'][] = $school_list[$school_id];
                }
            }
            if(isset($item['subject_id']))
                $item['subject_data'] = isset($subject_list[$item['subject_id']]) ? $subject_list[$item['subject_id']] : [];
        }

        return $list;
    }
}