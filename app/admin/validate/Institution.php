<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\admin\validate;

use think\Validate;

class Institution extends Validate{
    protected $rule = [
        'name'  =>  'require|max:40',
        'short_name'  =>  'max:20',
        'address'  =>  'max:20',
        'telephone'  =>  'max:20',
        'mobile'    =>  'mobile|unique:institution',
        'contacter' =>  'max:20',
        'province'  =>  'number',
        'city'  =>  'number',
        'area'  =>  'number',
        'grade_ids'  =>  'require|array',
    ];
    protected $message  =   [
    ];
}