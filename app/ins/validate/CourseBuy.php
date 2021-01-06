<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\ins\validate;

use think\Validate;

class CourseBuy extends Validate{
    protected $rule = [
        'course_id' =>  'require|integer',
        'student_id' =>  'require|integer',
        'buy_hour' =>  'require|number',
        'used_hour' =>  'number',
    ];
    protected $message  =   [

    ];
    protected $scene = [
        'add'  =>  ['course_id','student_id','buy_hour'],
    ];
    public function sceneEdit()
    {
        return $this->only(['buy_hour','used_hour']);
    }
}