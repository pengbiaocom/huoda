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
use Phpml\ModelManager;

class GeoController extends RestBaseController
{
    private $amapKey = '51f64f3a0a6905e0503ceefab4ce0ceb';
    public function read()
    {
        $address = $this->request->param('address');
//        $id =  $this->request->param('region',0);
//
//        $info = db("admin_region")->where(['id'=>$id])->find();
//        $response['info'] = $info;

        $rs = $this->http_curl("https://restapi.amap.com/v3/geocode/geo?key=".$this->amapKey."&address=".$address."&city=510100");
        
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
                $response['distance'] = $distance['distance'];
                $response['duration'] = $distance['duration'];
                $response['price'] = $distance['price'];
                $this->success("获取成功!", $response);
            }else{
                $this->error("获取失败");
            }

        }else{
            $this->error("获取失败");
        }

    }
    
    private function distance($location)
    {
        $origin = '104.025652,30.630897';
        $destination = $location;
        
        $url = "https://restapi.amap.com/v4/direction/bicycling?key=".$this->amapKey."&origin=".$origin."&destination=".$destination;
        $rs = $this->http_curl($url);
        $rs = json_decode($rs, true);
        
        if($rs['errcode'] == 0){
            $calculated['distance'] = $rs['data']['paths'][0]['distance'];
            $calculated['duration'] = round($rs['data']['paths'][0]['duration']/60) + 30;
            $calculated['price'] = $this->priceCalculation($calculated['distance'], $calculated['duration']-30);
        }else{
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
            $calculated['distance'] = round($earthRadius * $stepTwo);
            $calculated['duration'] = round($earthRadius * $stepTwo / 350) + 30;
            $calculated['price'] = $this->priceCalculation($calculated['distance'], $calculated['duration']-30);
        }
        
        return $calculated;
    }
    
    private function priceCalculation($distance, $duration){
        $filepath = "/home/wwwroot/huoda/model";
        $modelManager = new ModelManager();
        $classifier = $modelManager->restoreFromFile($filepath);
        
        $distance = round($distance/1000,1);
        return $classifier->predict([$distance, $duration]);
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
}