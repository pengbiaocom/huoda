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
use function Qiniu\json_decode;

class GeoController extends RestBaseController
{
    public function read()
    {
        $address = $this->request->param('address');
        $url = "http://restapi.amap.com/v3/geocode/geo?key=389880a06e3f893ea46036f030c94700&s=rsv3&city=510100&address=".urldecode($address);
        
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
        
        $location = json_decode($rs, true);
        
        if($location['status'] == 1){
            if (empty($this->apiVersion) || $this->apiVersion == '1.0.0') {
                $response = [$location['geocodes'][0]['location']];
            } else {
                $response = $location['geocodes'][0]['location'];
            }
            
            $this->success("坐标获取成功!", $response);
        } else {
            $response = $location['geocodes'][0]['location'];
            $this->error('坐标获取失败！', []);
        }
    }
}