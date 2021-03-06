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
use cmf\phpqrcode\QRcode;

class AdminIndexController extends AdminBaseController
{
    private $config = [
        'appid'=>'wx5f90b077ca92b8e7',
        'pay_mchid'=>'1517605631',
        'pay_apikey'=>'6ba57bc32cfd5044f8710f09ff86c664'
    ];
    private $amapKey = '51f64f3a0a6905e0503ceefab4ce0ceb';
    
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
    * 固定价格
    * @date: 2019年2月28日 上午10:23:09
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function fixed()
    {
        
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
    public function printorder()
    {
        $distribution = [];
        $distribution['distributions'] = $this->request->param('ids/a');
        $distribution['uid'] = $this->request->param('distributor', 0, 'intval');
        $distribution['status'] = 1;
        $distribution['create_time'] = time();
        
        $msg = "";
        Db::startTrans();
        try{
            /* 修改配送员状态 */
            if(Db::table("__USER__")->where('id', $distribution['uid'])->value('distribution_ing') == 0){
                if(Db::table("__USER__")->where('id', $distribution['uid'])->update(['distribution_ing'=>1])){
                    /* 修改指定订单状态 */
                    $printData = [];
                    foreach ($distribution['distributions'] as $item){
                        $map['order_status'] = 0;
                        $map['id'] = $item;
                        
                        if(Db::table("__ORDER__")->where($map)->update(['order_status'=>2, 'distribution'=>$distribution['uid']])){
                            /* 处理需要打印的数据 */
                            $printInfo = Db::table("__ORDER__")->where("id", $item)->find();
                            $location = $printInfo['lng'].','.$printInfo['lat'];
                            $url = "https://restapi.amap.com/v3/geocode/regeo?key=".$this->amapKey."&location=".$location."&extensions=base&batch=false&roadlevel=0";
                            $rs = $this->http_curl($url);
                            $rs = json_decode($rs, true);
                            $printInfo['address'] = $rs['regeocode']['formatted_address'];
                            
                            /* 加入付款二维码 */
                            $config = $this->config;
                			//统一下单参数构造
                			$unifiedorder = array(
                				'appid'			=> $config['appid'],
                				'mch_id'		=> $config['pay_mchid'],
                				'nonce_str'		=> self::getNonceStr(),
                				'body'			=> '货达',
                				'out_trade_no'	=> 'HD'.date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),//每一次的发起支付都重新生成一下订单号，并替换数据库
                				'total_fee'		=> $printInfo['order_total_price'] * 100,
                				'spbill_create_ip'	=> get_client_ip(),
                				'notify_url'	=> 'https://www.qianlishitou.com/api/home/order/notify',
                				'trade_type'	=> 'NATIVE'
                			);
                			$unifiedorder['sign'] = self::makeSign($unifiedorder);
    						//请求数据
    						$xmldata = self::array2xml($unifiedorder);
    						$url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    						$res = self::curl_post_ssl($url, $xmldata);
    						if(!$res){
    						    exception('生成支付信息失败，请确认后重新尝试');
    						}else{
    						    $content = self::xml2array($res);
    						    
    						    if(!empty($content['prepay_id']) && !empty($content['code_url'])){
    						        db("order")->where(['id'=>$printInfo['id']])->update(['prepay_id'=>$content['prepay_id'], 'order_number'=>$unifiedorder['out_trade_no']]);
    						        
    						        $printInfo['order_number'] = $unifiedorder['out_trade_no'];
    						        $value = $content['code_url'];//二维码内容
    						        $errorCorrectionLevel = 'L';
    						        $matrixPointSize = 5;
    						        $filename = 'qrcode/'.$unifiedorder['out_trade_no'].'.png';
    						        QRcode::png($value, $filename, $errorCorrectionLevel, $matrixPointSize, 2);

    						        $printInfo['code_url'] = $filename;
    						        $printData[] = $printInfo;
    						    }else{
    						        exception('该订单发起支付信息失败，请确认后重新尝试');
    						    }    						    
    						}
                        }else{
                            exception('该订单存在问题，请确认后重新尝试');
                        }
                    }
                    
                    $distribution['distributions'] = json_encode($distribution['distributions']);
                    if(!Db::table("__DISTRIBUTION__")->insert($distribution)){
                        exception('派单失败');
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
            $msg = $e->getMessage();
        }
        
        if(empty($msg)){
        	$this->assign('prints', $printData);
        	return $this->fetch();
        }else{
        	echo $msg;exit;
        }
    }
    
    private function http_curl($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//https
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);//https
        curl_setopt($ch, CURLOPT_URL, $url);
        
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);//10秒未响应就断开连接
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.130 Safari/537.36');
        
        $rs = curl_exec($ch); //执行cURL抓取页面内容
        curl_close($ch);
        
        return $rs;
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
        $param = $this->request->param();
        
        $orderService = new OrderService();
        $managers = $orderService->manager($param);
        $managers->appends($param);
        
        $this->assign('managers', $managers->items());
        $this->assign('page', $managers->render());
        return $this->fetch();
    }
    
    /**
    * 配送结算
    * @date: 2018年11月21日 下午3:24:12
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function distributionSettlement()
    {
        /* 配送中最高金额的总额+剩余订单的一半  */
        $param = $this->request->param();

        $msg = "";
        Db::startTrans();
        try{
            if(Db::table("__DISTRIBUTION__")->where('id', $param['id'])->update(['status'=>2])){
                $distributions = Db::table("__DISTRIBUTION__")->where('id', $param['id'])->find();
                $distributionsArr = json_decode($distributions['distributions'], true);
                
                if(count($distributionsArr) > 0 && !Db::table("__ORDER__")->where('id', 'in', $distributionsArr)->update(['order_status'=>3])){
                    exception('改变订单状态出现异常');
                }else{
                    if(!Db::table("__USER__")->where('id', $distributions['uid'])->update(['distribution_ing'=>0])){
                        exception('重置配送员状态异常');
                    }
                    Db::table("__USER__")->where('id', $distributions['uid'])->setInc('distributionCount', 1);
                }
            }else{
                exception('本次结算失败');
            } 
            Db::commit();
            $this->success('本次结算成功');
        }catch (\Exception $e) {
            Db::rollback();
            $msg = $e->getMessage();
        }
        
        if(empty($msg)){
            $this->success('结算成功');
        }else{
            $this->error($msg);
        }
    }
    
    /**
    * 管理配送数据中的订单（需要移除功能）
    * @date: 2018年11月20日 下午6:04:32
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function managerorder()
    {
        $param = $this->request->param();
        
        $distributions = db('distribution')->where('id', $param['id'])->value('distributions');
        $distributions = json_decode($distributions, true);
        
        $orders = db('order')->where('id', 'in', $distributions)->select();
        $this->assign('orders', $orders);
        $this->assign('did', $param['id']);
        return $this->fetch();
    }
    
    /**
    * 删除配送中的订单（不可逆转）
    * @date: 2018年11月21日 下午3:46:37
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function deleteDistribution()
    {
        $param = $this->request->param();
        $distributions = db('distribution')->where('id', $param['did'])->value('distributions');
        $distributions = json_decode($distributions, true);
        
        $key = array_search($param['id'], $distributions);
        
        if ($key !== false) array_splice($distributions, $key, 1);
        
        if(db('distribution')->where('id', $param['did'])->update(['distributions'=>json_encode($distributions)])){
            db('order')->where('id',$param['id'])->update(['order_status'=>0, 'distribution'=>0]);
            $this->success('成功取消配送');
        }else{
            $this->error('取消配送失败');
        }
    }

    /**
    * 取消订单
    * @date: 2018年11月20日 下午5:39:47
    * @author: onep2p <324834500@qq.com>
    * @param: variable1
    * @return:
    */
    public function delete()
    {
        $param = $this->request->param();
        $orderModel = new OrderModel();

        if (isset($param['id'])) {
            $id = $this->request->param('id', 0, 'intval');
            $result = $orderModel->where(['id' => $id])->find();

            if($result['order_status'] == 0){
                $data  = [
                    'object_id'   => $result['id'],
                    'create_time' => time(),
                    'table_name'  => 'order',
                    'name'        => $result['order_number'],
                    'user_id'     => cmf_get_current_admin_id()
                ];
                
                if ($orderModel->where(['id' => $id])->update(['delete_time' => time(),'order_status'=>-1])) {
                    Db::name('recycleBin')->insert($data);
                    $this->success("删除成功！", '');
                }else{
                    $this->error('删除失败！');
                }
            }else{
                if($this->refund($result)){
                    $data         = [
                        'object_id'   => $result['id'],
                        'create_time' => time(),
                        'table_name'  => 'order',
                        'name'        => $result['order_number'],
                        'user_id'     => cmf_get_current_admin_id()
                    ];
                    
                    if ($orderModel->where(['id' => $id])->update(['delete_time' => time(),'order_status'=>-1])) {
                        Db::name('recycleBin')->insert($data);
                        $this->success("删除成功！", '');
                    }else{
                        $this->error('删除失败！');
                    }
                }
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
	 * 微信支付发起请求
	 */
	protected function curl_post_ssl($url, $xmldata, $second=30,$aHeader=array()){
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);


		if( count($aHeader) >= 1 ){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
		}

		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xmldata);
		$data = curl_exec($ch);
		if($data){
			curl_close($ch);
			return $data;
		}
		else {
			$error = curl_errno($ch);
			echo "call faild, errorCode:$error\n";
			curl_close($ch);
			return false;
		}
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