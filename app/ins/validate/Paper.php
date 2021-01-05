<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\ins\validate;

use think\Validate;

class Paper extends Validate{
    protected $rule = [
        'title'  =>  'require|max:60',//试卷名称
        'name'  =>  'max:50',//试卷别名
        'is_subtitle'   =>  'in:0,1',//是否开启副标题
        'subtitle'  =>  'max:50',//副标题
        'is_lock'   =>  'in:0,1',//是否开启密封线
        'is_total_score'    =>  'in:0,1',//是否开启总评栏，0否1是
        'is_paper_info'    =>  'in:0,1',//是否开启试卷信息
        'paper_info'    =>  'max:255',//试卷信息
        'is_student_info'   =>  'in:0,1',//是否开启考生信息，0否1是
        'is_becareful'  =>  'in:0,1',//是否开启注意事项
        'becareful' =>  'max:255',//注意事项内容
        'is_sub_section'    =>  'in:0,1',//是否开启分卷及注释
        'sub_section'   =>  'max:255',//分卷及注释的内容
        'is_question_score' =>  'in:0,1',//是否开启大题评分区
    ];
    protected $message  =   [
        'title.require' => '试卷名称必填',
    ];
}