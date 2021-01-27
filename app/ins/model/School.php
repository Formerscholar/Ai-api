<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/23 0023
 * Time: 9:09
 */
namespace app\ins\model;

use think\Model;
use think\model\concern\SoftDelete;

class School extends Base{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
}