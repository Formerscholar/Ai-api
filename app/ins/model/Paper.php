<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 13:19
 */
namespace app\ins\model;

use think\model\concern\SoftDelete;

class Paper extends Base{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    public function scopeIns_Id($query){
        $user = session("user");
        if($user)
        {
            $query->where("ins_id",$user['ins_id']);
        }
    }

    public static function format_list(Array $list = []){
        $subject_ids = fetch_array_value($list,'subject_id');
        $uids = fetch_array_value($list,'uid');

        $subject_list = Subject::get_all(["id" => $subject_ids],"id,name,title,icon1,icon2");
        if($subject_list)
            $subject_list = array_column($subject_list,null,"id");

        $user_list = User::get_all(["id" => $uids],"id,name,student_count,point");
        if($user_list)
            $user_list = array_column($user_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['subject_id']))
                $item['subject_data'] = isset($subject_list[$item['subject_id']])?$subject_list[$item['subject_id']]:[];
            if(isset($item['uid']))
            $item['user_data'] = isset($user_list[$item['uid']])?$user_list[$item['uid']]:[];
        }

        return $list;
    }
}