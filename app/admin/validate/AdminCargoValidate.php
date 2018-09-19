<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 逸秋 < 324834500@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\validate;

use think\Validate;
use think\Db;

class AdminCargoValidate extends Validate
{
    protected $rule = [
        'parent_id'  => 'checkParentId',
        'name'       => 'require',
        'name'     => 'unique:AdminCargo,parent_id^name',
    ];

    protected $message = [
        'parent_id'          => '超过了2级',
        'name.require'       => '名称不能为空',
        'name.unique'      => '同样的记录已经存在!'
    ];

    protected $scene = [
        'add'  => ['name', 'parent_id'],
        'edit' => ['name', 'id', 'parent_id'],

    ];

    // 自定义验证规则
    protected function checkParentId($value)
    {
        $find = Db::name('AdminCargo')->where(["id" => $value])->value('parent_id');

        if ($find) {
            return false;
        }
        return true;
    }
}