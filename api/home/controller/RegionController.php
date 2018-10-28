<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: onep2p <onep2p@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use cmf\controller\RestBaseController;
use think\Db;

class RegionController extends RestBaseController
{
	public function read(){
		$id = $this->request->param('id', 0, 'intval');
        if ($id >= 0) {
        	$map['status'] = 1;
        	$map['parent_id'] = $id;
        	$regionList = Db::name("AdminRegion")->where($map)->select();
        	
        	if (empty($this->apiVersion) || $this->apiVersion == '1.0.0') {
        		$response = [$regionList];
        	} else {
        		$response = $regionList;
        	}
        	
        	$this->success("送达区域获取成功!", $response);
        } else {
        	$this->error('缺少ID参数');
        }
	}
}