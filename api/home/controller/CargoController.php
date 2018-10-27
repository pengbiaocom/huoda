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

class CargoController extends RestBaseController
{
	public function read(){
		$cargoList = Db::name("AdminCargo")->where('status', 1)->select();
		
		if (empty($this->apiVersion) || $this->apiVersion == '1.0.0') {
            $response = [$cargoList];
        } else {
            $response = $cargoList;
        }

        $this->success("货物分类获取成功!", $response);
	}
}