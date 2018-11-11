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
    public function index()
    {

        $param = $this->request->param();

        $orderService = new OrderService();
        $data        = $orderService->adminOrderList($param);

        $data->appends($param);


        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('keyword', isset($param['keyword']) ? $param['keyword'] : '');
        $this->assign('orderlist', $data->items());
        $this->assign('page', $data->render());

        return $this->fetch();
    }

    public function delete()
    {
        $param           = $this->request->param();
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