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

            if($result['order_status']==0){
                $data         = [
                    'object_id'   => $result['id'],
                    'create_time' => time(),
                    'table_name'  => 'order',
                    'name'        => $result['order_number'],
                    'user_id'     => cmf_get_current_admin_id()
                ];
                $resultPortal = $orderModel
                    ->where(['id' => $id])
                    ->update(['delete_time' => time(),'order_status'=>-1]);
                if ($resultPortal) {
                    Db::name('recycleBin')->insert($data);
                }
                $this->success("删除成功！", '');
            }else{
                if($this->refund($result)){
                    $data         = [
                        'object_id'   => $result['id'],
                        'create_time' => time(),
                        'table_name'  => 'order',
                        'name'        => $result['order_number'],
                        'user_id'     => cmf_get_current_admin_id()
                    ];
                    $resultPortal = $orderModel
                        ->where(['id' => $id])
                        ->update(['delete_time' => time(),'order_status'=>-1]);
                    if ($resultPortal) {
                        Db::name('recycleBin')->insert($data);
                    }
                    $this->success("删除成功！", '');
                }
            }


        }

        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $recycle = $orderModel->where(['id' => ['in', $ids]])->select();
            $result  = $orderModel->where(['id' => ['in', $ids]])->update(['delete_time' => time(),'order_status'=>-1]);
            if ($result) {
                foreach ($recycle as $value) {
                    if($value['order_status']==0){
                        $data = [
                            'object_id'   => $value['id'],
                            'create_time' => time(),
                            'table_name'  => 'order',
                            'name'        => $value['order_number'],
                            'user_id'     => cmf_get_current_admin_id()
                        ];
                        Db::name('recycleBin')->insert($data);
                    }else{
                        if($this->refund($value)){
                            $data = [
                                'object_id'   => $value['id'],
                                'create_time' => time(),
                                'table_name'  => 'order',
                                'name'        => $value['order_number'],
                                'user_id'     => cmf_get_current_admin_id()
                            ];
                            Db::name('recycleBin')->insert($data);
                        }
                    }

                }
                $this->success("删除成功！", '');
            }
        }
    }

    /**
     * 退款申请
     * @date: 2018年7月27日 上午9:00:18
     * @param: variable
     * @return:
     */
    private function refund($order){
        $config = $this->config;

        //退款申请参数构造
        if($order){
            $refunddorder = array(
                'appid'			=> $config['appid'],
                'mch_id'		=> $config['pay_mchid'],
                'nonce_str'		=> self::getNonceStr(),
                'out_trade_no'	=> $order['order_number'],
                'out_refund_no' => 'tk_' . md5($order['order_number']),//退款唯一单号，系统生成
                'total_fee'		=> $order['order_total_price'] * 100,
                'refund_fee'    => $order['order_total_price'] * 100,//退款金额,通过计算得到要退还的金额
            );

            $refunddorder['sign'] = self::makeSign($refunddorder);

            //请求数据
            $xmldata = self::array2xml($refunddorder);
            $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
            $res = self::postXmlCurl($xmldata, $url, true);
            $resData = $this->xml2array($res);

            if($resData['return_code'] === 'SUCCESS' && $resData['return_msg'] === 'OK' && $resData['result_code'] === 'SUCCESS'){
                return true;
            }

            return false;
        }
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     */
    private function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验

        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, CMF_ROOT.'/public_html/cert/apiclient_cert.pem');
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, CMF_ROOT.'/public_html/cert/apiclient_key.pem');
        }

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        //运行curl
        $data = curl_exec($ch);

        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误代码：$error"."<br/>";
            curl_close($ch);
            echo false;
        }
    }

    /**
     * 将一个数组转换为 XML 结构的字符串
     * @param array $arr 要转换的数组
     * @param int $level 节点层级, 1 为 Root.
     * @return string XML 结构的字符串
     */
    protected function array2xml($arr, $level = 1) {
        $s = $level == 1 ? "<xml>" : '';
        foreach($arr as $tagname => $value) {
            if (is_numeric($tagname)) {
                $tagname = $value['TagName'];
                unset($value['TagName']);
            }
            if(!is_array($value)) {
                $s .= "<{$tagname}>".(!is_numeric($value) ? '<![CDATA[' : '').$value.(!is_numeric($value) ? ']]>' : '')."</{$tagname}>";
            } else {
                $s .= "<{$tagname}>" . $this->array2xml($value, $level + 1)."</{$tagname}>";
            }
        }
        $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
        return $level == 1 ? $s."</xml>" : $s;
    }

    /**
     * 将xml转为array
     * @param  string 	$xml xml字符串
     * @return array    转换得到的数组
     */
    protected function xml2array($xml){
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }

    /**
     * 生成签名
     * @return 签名
     */
    protected function makeSign($data){
        //获取微信支付秘钥
        $key = $this->config['pay_apikey'];
        // 去空
        $data=array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a=http_build_query($data);
        $string_a=urldecode($string_a);
        //签名步骤二：在string后加入KEY
        //$config=$this->config;
        $string_sign_temp=$string_a."&key=".$key;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result=strtoupper($sign);
        return $result;
    }

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    protected function getNonceStr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

}