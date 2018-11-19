<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\order\service;

use app\order\model\OrderModel;

class OrderService
{

    public function adminOrderList($filter)
    {

        $where = [
            'a.create_time' => ['>=', 0],
            'a.delete_time' => 0
        ];

        $join = [
            ['__USER__ u', 'a.uid = u.id']
        ];

        $field = 'a.*,u.user_login,u.user_nickname,u.user_email';

        $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
        $endTime = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
        if (!empty($startTime) && !empty($endTime)) {
            $where['a.create_time'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)) {
                $where['a.create_time'] = ['>= time', $startTime];
            }
            if (!empty($endTime)) {
                $where['a.create_time'] = ['<= time', $endTime];
            }
        }

        $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
        if (!empty($keyword)) {
            $where['a.order_number'] = ['like', "%$keyword%"];
        }

        $orderModel = new OrderModel();
        $articles = $orderModel->alias('a')->field($field)
            ->join($join)
            ->where($where)
            ->order("a.order_status asc,a.lat asc,a.lng asc,create_time desc")
            ->paginate(10);

        return $articles;

    }
    
    public function adminOrderFind($filter)
    {
        
    }

}