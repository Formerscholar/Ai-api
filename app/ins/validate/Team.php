<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\ins\validate;

use think\Validate;

class Team extends Validate{
    protected $rule = [
        'name' =>  'require|max:20',
        'school_id' =>  'require',
        'course_id' =>  'require',
        'uids' =>  'require|array',
    ];
    protected $message  =   [
        'name.require' => '名称必填',
        'name.max' => '名称长度最大20个字符',
    ];
}