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

class AddressController extends RestBaseController
{
    public function read()
    {
        $type = $this->request->param('type', 0, 'intval');// 0  最近的单个地址，1  最近的所有地址
		$uid = $this->request->param("uid", 0, 'intval');
		$order_number = $this->request->param("order_number",'');
		if($order_number !=''){
			$data = Db::name("order")->where("order_number",$order_number)->order('create_time desc')->find();
		}else{
			switch ($type) {
				case 0:
					$where['order_status'] = array('GT', 0);
					$where['uid'] = $uid;
					$data = Db::name("order")->where($where)->order('create_time desc')->find();
// 					if($data){
// 						$data['order_total_price'] = $data['order_total_price']+5;
// 					}
					break;
				case 1:
					$where['order_status'] = array('GT', 0);
					if($uid > 0) $where['uid'] = $uid;
					$data = Db::name("order")->where($where)->order('create_time desc')->select();
					if(!empty($data)){
// 						foreach($data as $key=>$row){
// 							$data[$key]['order_total_price'] = $row['order_total_price']+5;
// 						}
						$data = json_decode($data,true);
					}
					break;
				default:
					$where['order_status'] = array('GT', 0);
					$where['uid'] = $uid;
					$data = Db::name("order")->where($where)->order('create_time desc')->find();
// 					if($data){
// 						$data['order_total_price'] = $data['order_total_price']+5;
// 					}
					break;
			}
		}

		if(!empty($data)){
			if (empty($this->apiVersion) || $this->apiVersion == '1.0.0') {
				$response = [$data];
			} else {
				$response = $data;
			}
			$this->success("地址获取成功!", $response);
		}else{
			$this->error("地址获取失败!");
		}

    }
}