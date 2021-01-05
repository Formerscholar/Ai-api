<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 13:19
 */
namespace app\ins\model;

use think\model\concern\SoftDelete;

class Student extends Base{
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
        $team_ids = fetch_array_value($list,'team_ids');
        $uids = fetch_array_value($list,'uids');
        $school_ids = fetch_array_value($list,'school_id');

        $team_list = Team::get_all(["id" => array_filter(array_unique(explode(",",join(",",$team_ids))))],"id,name");
        if($team_list)
            $team_list = array_column($team_list,null,"id");

        $user_list = User::get_all(["id" => array_filter(array_unique(explode(",",join(",",$uids))))],"id,name,student_count,point");
        if($user_list)
            $user_list = array_column($user_list,null,"id");

        $school_list = School::get_all(["id" => $school_ids],"id,name");
        if($school_list)
            $school_list = array_column($school_list,null,"id");

        foreach($list as &$item)
        {
            if(isset($item['team_ids']))
            {
                $item['team_data'] = [];

                foreach($team_list as $team)
                {
                    if(strstr(','.$item['team_ids'].',',(string)$team['id']))
                    {
                        $item['team_data'][] = $team;
                    }
                }
            }

            if(isset($item['uids']))
            {
                $item['user_data'] = [];

                foreach($user_list as $user)
                {
                    if(strstr(','.$item['uids'].',',(string)$user['id']))
                    {
                        $item['user_data'][] = $user;
                    }
                }
            }

            if(isset($item['school_id']))
                $item['school_data'] = isset($school_list[$item['school_id']]) ? $school_list[$item['school_id']] : [];
        }

        return $list;
    }
}