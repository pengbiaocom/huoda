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

class OrderController extends RestBaseController
{
	protected function _initialize(){
        parent::_initialize();
		//微信支付参数配置(appid,商户号,支付秘钥)
		$config = [
			'appid'=>'wxa6737565830cae42',
			'pay_mchid'=>'1509902681',
			'pay_apikey'=>'6ba57bc32cfd5044f8710f09ff86c664'
		];

		$this->config = $config;
	}
	/**
	 * 显示资源列表
	 */
	public function index()
	{
		$uid = $this->request->param("uid",0);
		$limit = $this->request->param('limit', 10, 'intval');
		$page = $this->request->param('page', 1, 'intval');
		$status = $this->request->param("status",0);

		if(empty($uid))  return json(['code'=>1,'msg'=>'缺少参数']);

		$where['uid'] = $uid;
		if($status != 'all'){
			$where['order_status'] = $status;
		}

		$list = Db::name("order")->where($where)->order('create_time desc')->limit(($page-1)*$limit, $limit)->select();

        if(!empty($list)){
			$list = json_decode($list,true);
			foreach($list as $key=>$row){
				$list[$key]['province'] = db("admin_region")->where("id",$row['get_region_one'])->value("name");
				$list[$key]['city'] = db("admin_region")->where("id",$row['get_region_tow'])->value("name");
				$list[$key]['county'] = db("admin_region")->where("id",$row['get_region_three'])->value("name");
				$list[$key]['create_time'] = date("Y-m-d H:i:s",$row['create_time']);
			}
			return json(['code'=>0, 'msg'=>'调用成功', 'data'=>$list, 'paginate'=>array( 'page'=>sizeof($list) < $limit ? $page : $page+1, 'limit'=>$limit)]);
		}else{
			return json(['code'=>1, 'msg'=>'调用失败', 'data'=>[]]);
		}
	}
	
	/**
	 * 保存新建的资源
	 */
	public function save()
	{
		$data['uid'] = $this->request->param('uid',0);
		$data['send_address'] = $this->request->param('send_address','');
		$data['send_username'] = $this->request->param('send_username','');
		$data['send_phone'] = $this->request->param('send_phone','');
		$data['get_region_one'] = $this->request->param('get_region_one','');
		$data['get_region_tow'] = $this->request->param('get_region_tow','');
		$data['get_region_three'] = $this->request->param('get_region_three','');
		$data['get_address'] = $this->request->param('get_address','');
		$data['get_username'] = $this->request->param('get_username','');
		$data['get_phone'] = $this->request->param('get_phone','');
		$data['cid'] = $this->request->param('cid','');
		$data['estimate_time'] = $this->request->param('estimate_time','');
		$data['order_total_price'] = $this->request->param('order_total_price','');
		$data['remarks'] = $this->request->param('remarks','');


		if(empty($data))   return json(['code'=>1,'msg'=>'缺少参数']);

		$data['order_number'] = 'HD'.date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
		$data['order_status'] = 0;
		$data['create_time'] = time();

		$user = db("third_party_user")->where("user_id",$data['uid'])->find();

		if($id = db("order")->insertGetId($data)){
			return json(['code'=>0,'msg'=>'下单成功']);
			$config = $this->config;
			//统一下单参数构造
			$unifiedorder = array(
				'appid'			=> $config['appid'],
				'mch_id'		=> $config['pay_mchid'],
				'nonce_str'		=> self::getNonceStr(),
				'body'			=> '货达',
				'out_trade_no'	=> 'YF'.date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),//每一次的发起支付都重新生成一下订单号，并替换数据库
				'total_fee'		=> $data['price'] * 100,
				'spbill_create_ip'	=> get_client_ip(),
				'notify_url'	=> 'https://huoda.chouvc.com/api/home/order/notify',
				'trade_type'	=> 'JSAPI',
				'openid'		=> $user['openid']
			);
			//更新数据库单号
			db("order")->where(['id'=>$id])->update(['order_number'=>$unifiedorder['out_trade_no']]);
			$unifiedorder['sign'] = self::makeSign($unifiedorder);
			//请求数据
			$xmldata = self::array2xml($unifiedorder);
			$url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
			$res = self::curl_post_ssl($url, $xmldata);
			if(!$res){
				self::return_err("Can't connect the server");
			}

			$content = self::xml2array($res);

			if(strval($content['result_code']) == 'FAIL'){
				self::return_err(strval($content['err_code_des']));
			}
			if(strval($content['return_code']) == 'FAIL'){
				self::return_err(strval($content['return_msg']));
			}

			if(!empty($content['prepay_id'])){
				return self::pay($content['prepay_id'],$unifiedorder['out_trade_no']);
			}else{
				return json(['code'=>1,'msg'=>'发起支付失败']);
			}
		}else{
			return json(['code'=>1,'msg'=>'服务器繁忙']);
		}
	}

	/**
	 * 进行支付接口(POST)
	 * @param string $prepay_id 预支付ID(调用prepay()方法之后的返回数据中获取)
	 * @return  json的数据
	 */
	public function pay($prepay_id,$sorder_sn){
		$config = $this->config;

		$data = array(
			'appId'		=> $config['appid'],
			'timeStamp'	=> time(),
			'nonceStr'	=> self::getNonceStr(),
			'package'	=> 'prepay_id='.$prepay_id,
			'signType'	=> 'MD5'
		);

		$data['paySign'] = self::makeSign($data);
		$data['sorder_sn'] = $sorder_sn;

		return json(['code'=>0,'data'=>$data]);
	}

	//微信支付回调验证
	public function notify(){
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];

		// 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了
		//file_put_contents(APP_ROOT.'/Statics/log2.txt',$res,FILE_APPEND);

		//将服务器返回的XML数据转化为数组
		$data = self::xml2array($xml);
		// 保存微信服务器返回的签名sign
		$data_sign = $data['sign'];
		// sign不参与签名算法
		unset($data['sign']);
		$sign = self::makeSign($data);

		// 判断签名是否正确  判断支付状态
		if ( ($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {
			$result = $data;
			//获取服务器返回的数据
			$out_trade_no =  explode('_',$data['out_trade_no']);
			$order_sn = $out_trade_no[0];			//订单单号
			$openid = $data['openid'];					//付款人openID
			$total_fee = $data['total_fee'];			//付款金额
			$transaction_id = $data['transaction_id']; 	//微信支付流水号
			db("order")->where(['order_number'=>$order_sn])->update(['status'=>1]);
		}else{
			$out_trade_no =  explode('_',$data['out_trade_no']);
			$order_sn = $out_trade_no[0];			//订单单号
			db("order")->where(['order_number'=>$order_sn])->delete();
			$result = false;
		}
		// 返回状态给微信服务器
		if ($result !== false) {
			$str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
		}else{
			$str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
		}
		echo $str;
		return $result;
	}
	
	/**
	 * 显示指定的资源
	 *
	 */
	public function detail()
	{
		$order_id = $this->request->param("id",3);

		if(empty($order_id)) return json(['code'=>1,'msg'=>'缺少参数']);
		$info = db("order")->where("id",$order_id)->find();
		if(!empty($info)){
			$info['province'] = db("admin_region")->where("id",$info['get_region_one'])->value("name");
			$info['city'] = db("admin_region")->where("id",$info['get_region_tow'])->value("name");
			$info['county'] = db("admin_region")->where("id",$info['get_region_three'])->value("name");
			$info['cargo_name'] = db("admin_cargo")->where("id",$info['cid'])->value("name");
			$info['create_time'] = date("Y-m-d H:i:s",$info['create_time']);

			return json(['code'=>0,'msg'=>'success','data'=>$info]);
		}else{
			return json(['code'=>1,'msg'=>'没有数据']);
		}
	}
	
	/**
	 * 保存更新的资源
	 *
	 * @param  int $id
	 */
	public function update($id)
	{
		
	}
	
	/**
	 * 删除指定资源
	 *
	 * @param  int $id
	 */
	public function delete($id)
	{
		
	}

	/**
	 * 错误返回提示
	 * @param string $errMsg 错误信息
	 * @param string $status 错误码
	 * @return  json的数据
	 */
	protected function return_err($errMsg='error',$status=0){
		exit(json_encode(array('code'=>$status,'result'=>'fail','msg'=>$errMsg)));
	}

	/**
	 * 正确返回
	 * @param 	array $data 要返回的数组
	 * @return  json的数据
	 */
	protected function return_data($data=array()){
		exit(json_encode(array('status'=>1,'result'=>'success','data'=>$data)));
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