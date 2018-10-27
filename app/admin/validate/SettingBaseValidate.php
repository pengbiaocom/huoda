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
        'options.notice' => 'require',
//         'options.about' => 'require',
//         'options.user_agreement' => 'require',
//         'options.price_explain' => 'require'
    ];

    protected $message = [
        'options.dispatch_max_num.require' => '出车最大送单量不能为空',
        'options.notice.require' => '公告不能为空',
//         'options.about.require' => '关于我们不能为空',
//         'options.user_agreement.require' => '用户协议不能为空',
//         'options.price_explain.require' => '价格说明不能为空',
    ];
}