<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/25 0025
 * Time: 13:40
 */

namespace app\ins\validate;

use think\Validate;

class Material extends Validate{
    protected $rule = [
        "id"    =>  "require",
        "subject_id"    =>  "require",
        "grade_id"  =>  "require",
        "name"  =>  "require|max:40",
        "desc"  =>  "max:200",
        "file_url"  =>  "require",
        "suffix"    =>  "in:1,2",
    ];
    protected $message  =   [

    ];
    protected $scene = [

    ];
    // 同步
    public function sceneSync()
    {
        return $this->only(['id','subject_id','grade_id','name','desc','file_url','suffix']);
    }
}