<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.chouvc.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 逸秋 < 324834500@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\validate;

use think\Validate;

class SettingBaseValidate extends Validate
{
    protected $rule = [
        'options.dispatch_max_num' => 'require',
        'options.big_max_num' => 'require',
        'options.notice' => 'require'
    ];

    protected $message = [
        'options.dispatch_max_num.require' => '出车最大送单量不能为空',
        'options.big_max_num.require' => '出车最大运送大件量不能为空',
        'options.notice.require' => '公告不能为空'
    ];
}