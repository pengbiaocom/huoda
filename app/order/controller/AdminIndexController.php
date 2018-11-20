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
namespace app\order\controller;

use cmf\controller\AdminBaseController;
use app\order\service\OrderService;
use app\order\model\OrderModel;
use think\Db;

class AdminIndexController extends AdminBaseController
{
    /**
    * 订单列表
    * @date: 2018年11月20日 下午5:39:24
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function index()
    {
        $param = $this->request->param();

        $orderService = new OrderService();
        $data = $orderService->adminOrderList($param);

        $data->appends($param);


        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('keyword', isset($param['keyword']) ? $param['keyword'] : '');
        $this->assign('orderlist', $data->items());
        $this->assign('page', $data->render());

        return $this->fetch();
    }
    
    /**
    * 订单派送列表
    * @date: 2018年11月20日 下午5:38:58
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function push()
    {
        $baseSetting = cmf_get_option('base_setting');
        
        $orderService = new OrderService();
        $param['dispatch_max_num'] = $baseSetting['dispatch_max_num'];
        $param['big_max_num'] = $baseSetting['big_max_num'];
        $pushs = $orderService->adminOrderPush($param);
        
        $this->assign('pushs', $pushs);
        return $this->fetch();
    }
    
    /**
    * 获取当前可发车的配送员
    * @date: 2018年11月20日 下午5:38:43
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function select()
    {   
        $orderService = new OrderService();
        $distributor = $orderService->distributor();
        
        $this->assign('distributor', $distributor);
        return $this->fetch();
    }
    
    /**
    * 打印派送订单数据
    * @date: 2018年11月20日 下午5:38:28
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function print()
    {
        $distribution = [];
        $distribution['distributions'] = $this->request->param('ids/a');
        $distribution['uid'] = $this->request->param('distributor', 0, 'intval');
        $distribution['status'] = 1;
        $distribution['create_time'] = time();
        
        Db::startTrans();
        try{
            /* 修改配送员状态 */
            if(Db::table("__USER__")->where('id', $distribution['uid'])->value('distribution_ing') == 0){
                if(Db::table("__USER__")->where('id', $distribution['uid'])->update(['distribution_ing'=>1])){
                    /* 修改指定订单状态 */
                    if(Db::table("__ORDER__")->where('id', 'in', $distribution['distributions'])->update(['order_status'=>2])){
                        /* 插入配送数据 */
                        $distribution['distributions'] = json_encode($distribution['distributions']);
                        if(Db::table("__DISTRIBUTION__")->insert($distribution)){
                            /* 处理出数据返回给html操作打印 */
                            echo 1;
                        }else{
                            exception('派单失败');
                        }
                    }
                }else{
                    exception('更新配送员状态失败，可能其他管理员为该配送员派发了订单，请在派单列表中刷新后再次尝试');
                }
            }else{
                exception('配送员还在配送中');
            }
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            
            echo $e->getMessage();
        }
    }
    
    /**
    * 管理配送数据
    * @date: 2018年11月20日 下午5:39:33
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function manager()
    {
        
    }
    
    /**
    * 管理配送数据中的订单（需要移除功能）
    * @date: 2018年11月20日 下午6:04:32
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function managerOrder()
    {
        
    }

    /**
    * 取消订单
    * @date: 2018年11月20日 下午5:39:47
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function delete()
    {
        $param = $this->request->param();
        $orderModel = new OrderModel();

        if (isset($param['id'])) {
            $id           = $this->request->param('id', 0, 'intval');
            $result       = $orderModel->where(['id' => $id])->find();
            $data         = [
                'object_id'   => $result['id'],
                'create_time' => time(),
                'table_name'  => 'order',
                'name'        => $result['order_number'],
                'user_id'     => cmf_get_current_admin_id()
            ];
            $resultPortal = $orderModel
                ->where(['id' => $id])
                ->update(['delete_time' => time()]);
            if ($resultPortal) {

                Db::name('recycleBin')->insert($data);
            }
            $this->success("删除成功！", '');

        }

        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $recycle = $orderModel->where(['id' => ['in', $ids]])->select();
            $result  = $orderModel->where(['id' => ['in', $ids]])->update(['delete_time' => time()]);
            if ($result) {
                foreach ($recycle as $value) {
                    $data = [
                        'object_id'   => $value['id'],
                        'create_time' => time(),
                        'table_name'  => 'order',
                        'name'        => $value['order_number'],
                        'user_id'     => cmf_get_current_admin_id()
                    ];
                    Db::name('recycleBin')->insert($data);
                }
                $this->success("删除成功！", '');
            }
        }
    }

}