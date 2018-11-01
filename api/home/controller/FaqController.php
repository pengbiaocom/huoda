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
namespace api\home\controller;

use cmf\controller\RestBaseController;
use think\Db;

class FaqController extends RestBaseController
{
    public function save()
    {
        $uid = $this->request->param('uid', 0, 'intval');
        $content = $this->request->param('content');
        
        $faq['uid'] = $uid;
        $faq['content'] = $content;
        $faq['status'] = 0;
        $faq['create_time'] = time();
        
        $result = Db::name("UserFaq")->insert($faq);
        
        if (empty($result)) {
            $this->error("反馈失败,请重试!");
        }
        
        $this->success("意见意见反馈成功,请等待管理员与您联系!");        
    }
}