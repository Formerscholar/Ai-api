<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\ins\validate;

use think\Validate;

class StudentStudy extends Validate{
    protected $rule = [
        'course_id' =>  'require|integer',
        'student_id'    =>  'require|integer',
        'study_time'=>  'require',
        'content' =>  'require|max:20',
        'paper_id'  =>  'require|integer',
    ];
    protected $message  =   [

    ];
    protected $scene = [
        'add'  =>  ['course_id','student_id','study_time','content','paper_id'],
        'edit'  =>  ['study_time','content','paper_id'],
    ];
}