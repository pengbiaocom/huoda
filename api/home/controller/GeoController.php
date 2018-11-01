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
        $id =  $this->request->param('region');

        $info = db("admin_region")->where(['id'=>$id])->find();
        $response['info'] = $info;
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
            /* 计算距离和时间 */
            if(!empty($location['geocodes'])){
                $geo = $location['geocodes'][0]['location'];
                $geo = explode(',',$geo);
                $location['lat'] = $geo[1];
                $location['lng'] = $geo[0];
                $distance = $this->distance($location['geocodes'][0]['location']);
                $response['geo'] = $geo;
                $response['distance'] = $distance;
            }else{
                $response['geo'] = [];
                $response['distance'] = [];
            }

        }else{
            $response['geo'] = [];
            $response['distance'] = [];
        }
        $this->success("获取成功!", $response);

    }
    
    private function distance($location)
    {
        $startLng = '104.025652';
        $startLat = '30.630897';
        list($endLng,$endLat) = explode(',', $location);
        

        $earthRadius = 6367000; //approximate radius of earth in meters
        $startLat = ($startLat * pi() ) / 180;
        $startLng = ($startLng * pi() ) / 180;
        $endLat = ($endLat * pi() ) / 180;
        $endLng = ($endLng * pi() ) / 180;
        $calcLongitude = $endLng - $startLng;
        $calcLatitude = $endLat - $startLat;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($startLat) * cos($endLat) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = round($earthRadius * $stepTwo / 350) + 30;
        
        return $calculatedDistance;
    }
}