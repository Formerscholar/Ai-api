<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/24 0024
 * Time: 13:19
 */
namespace app\ins\model;


class Basket extends Base{
    //返回组卷栏中所有的题目ids
    public static function getQuestionIds($uid,$subject_id){
        return self::where("uid",$uid)->where("subject_id",$subject_id)->column("question_id");
    }
    //排序算法,上一个
    public static function preData($uid,$subject_id,$type,$pre_sort){
        $pre_sort--;

        $res = self::where([
            "uid"   =>  $uid,
            "subject_id"    =>  $subject_id,
            "type"  =>  $type,
            "sort"  =>  $pre_sort
        ])->find();

        if(!$res)
        {
            return self::preData($uid,$type,$pre_sort);
        }
        else
        {
            return $res->getData();
        }
    }
    //排序算法,下一个
    public static function nextData($uid,$subject_id,$type,$next_sort){
        $next_sort++;

        $res = self::where([
            "uid"   =>  $uid,
            "subject_id"    =>  $subject_id,
            "type"  =>  $type,
            "sort"  =>  $next_sort
        ])->find();

        if(!$res)
        {
            return self::nextData($uid,$type,$next_sort);
        }
        else
        {
            return $res;
        }
    }
    //位置交换
    public static function transPosition($curr_where = [],$curr_sort,$else_where=[],$else_sort){
        $data['sort'] = $else_sort;
        $res = self::update($data,$curr_where);

        if($res)
        {
            $data['sort'] = $curr_sort;
            $res = self::update($data,$else_where);
            if($res)
            {
                return true;
            }
            else
                return false;
        }
        else
            return false;
    }
}