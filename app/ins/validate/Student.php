<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\ins\validate;

use think\Validate;

class Student extends Validate{
    protected $rule = [
        'name' =>  'require|max:20',
        'sex' =>  'require|in:1,2',
        'mobile'  =>  'mobile',
        'school_id'  =>  'require|number',
        'team_ids' =>  'require|array',
        'uids'  =>  'require|array',
    ];
    protected $message  =   [
        'name.require' => '姓名必填',
        'name.max' => '姓名长度最大20个字符',
        'mobile.require' => '家长联系方式必填',
        'mobile.length' => '家长联系方式格式为11位的手机号码',
        'team_ids.require' => '班级必填',
        'uids.require' => '负责老师必填',
    ];
}