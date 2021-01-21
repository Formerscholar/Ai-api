<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\ins\validate;

use think\Validate;

class PaperUpload extends Validate{
    protected $rule = [
        'team_id' =>  'require|number',
        'subject_id' =>  'require|number',
        'name' =>  'require|max:40',
        'mark' =>  'max:200',
        'files'  =>  'require|array',
    ];
    protected $message  =   [

    ];
    protected $scene = [
        'add'  =>  ['team_id','subject_id','name','mark','files'],
    ];
}