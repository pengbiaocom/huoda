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

class AdminRegionValidate extends Validate
{
    protected $rule = [
        'parent_id'  => 'checkParentId',
        'name'       => 'require',
        'name'     => 'unique:AdminRegion,name',
    ];

    protected $message = [
        'parent_id'          => '超过了4级',
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
        $find = Db::name('AdminRegion')->where(["id" => $value])->value('parent_id');

        if ($find) {
            $find2 = Db::name('AdminRegion')->where(["id" => $find])->value('parent_id');
            if ($find2) {
                $find3 = Db::name('AdminRegion')->where(["id" => $find2])->value('parent_id');
                if ($find3) {
                    return false;
                }
            }
        }
        return true;
    }
}