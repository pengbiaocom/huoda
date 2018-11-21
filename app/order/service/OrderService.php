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
use app\order\model\UserModel;
use app\order\model\DistributionModel;

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
            ->order("a.order_status asc,create_time desc")
            ->paginate(10);

        return $articles;

    }
    
    public function adminOrderPush($filter)
    {
        $orderModel = new OrderModel();
        $orders = $orderModel::all(function($query){
            $query->alias('order');
            $query->where('order.order_status', 1);
            $query->order('order.create_time DESC');
        });
        
        $pushs = [];
        $big = [];
        $ids = [];
        

        $startLng = '104.025652';
        $startLat = '30.630897';
        while(count($pushs) < $filter['dispatch_max_num']){
            $distance = [];
            foreach ($orders as $order){
                if(!in_array($order['id'], $ids)){
                    if($order['radio_value'] == 'large' && count($big) >= $filter['big_max_num']) continue;
                    
                    $endLng = $order['lng'];
                    $endLat = $order['lat'];
                    
                    $radLat1=deg2rad($startLat);//deg2rad()函数将角度转换为弧度
                    $radLat2=deg2rad($endLat);
                    $radLng1=deg2rad($startLng);
                    $radLng2=deg2rad($endLng);
                    $a=$radLat1-$radLat2;
                    $b=$radLng1-$radLng2;
                    
                    $data = $order;
                    $data['distance'] = round(2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378137);
                    
                    $distance[] = $data;
                }
            }
            
            $last_names = array_column($distance,'distance');
            array_multisort($last_names,SORT_ASC,$distance);
            
            if(count($distance) > 0){
                $pushs[] = $distance[0];
                $ids[] = $distance[0]['id'];
                $startLng = $distance[0]['lng'];
                $startLat = $distance[0]['lat'];                
            }else{
                break;
            }
        }
        
        return $pushs;
    }
    
    public function distributor()
    {
        $userModel = new UserModel();
        
        $distributor = $userModel::all(function($query){
            $query->field('id,user_nickname,distributionCount');
            $query->where('user_status', 1);
            $query->where('user_distribution', 1);
            $query->where('distribution_ing', 0);
            $query->order('distributionCount ASC,create_time ASC');
        });
        
        return $distributor;
    }
    
    public function manager($filter)
    {
        $distributionModel = new DistributionModel();
        
        $manager = $distributionModel
            ->alias('manager')
            ->field('manager.*,user.user_nickname')
            ->join('__USER__ user', 'manager.uid = user.id', 'left')
            ->where('manager.status', '>', 0)
            ->order('manager.status asc')
            ->paginate(10);
        
        $orderModel = new OrderModel();
        foreach ($manager as &$item){
            $orders = $orderModel::all(function($query) use($item){
                $query->alias('order');
                $query->where('id', 'in', json_decode($item['distributions'], true));
                $query->order('order.order_total_price DESC');
            });
            
            $item['settlementPrice'] = 0;
            foreach ($orders as $key=>$order){
                if($key == 0){
                    $item['settlementPrice'] += $order['order_total_price'];
                }else{
                    $item['settlementPrice'] += round($order['order_total_price']*0.5, 2);
                }
            }
        }
        
        return $manager;
    }
}