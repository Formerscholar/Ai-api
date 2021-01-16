<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\admin\validate;

use think\Validate;

class Menu extends Validate{
    protected $rule = [
        'name' =>  'require|max:20',
        'route' =>  'max:100',
        'desc' =>  'max:100',
        'is_show' =>  'in:Y,N',
        'icon' =>  'max:40',
        'path' =>  'array',
    ];
    protected $message  =   [

    ];
    protected $scene = [
        'edit'  =>  ['name','route','desc','is_show','icon','path'],
    ];
}