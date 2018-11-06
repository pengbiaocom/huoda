<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;


use cmf\controller\RestBaseController;
use Phpml\Classification\NaiveBayes;

class IndexController extends RestBaseController
{
    // api 首页
    public function index()
    {
        $this->success("恭喜您,API访问成功!", [
            'version' => '1.1.0',
            'doc'     => 'http://www.thinkcmf.com/cmf5api.html'
        ]);
    }

    public function  demo(){
        $samples = [
            [15, 56], 
            [12.1, 46], 
            [11.4, 43], 
            [7.4, 28], 
            [7.6, 29], 
            [17.2, 65], 
            [15.3, 57], 
            [12.4, 46], 
            [14.5, 55], 
            [17.4, 65], 
            [16.3, 61], 
            [6.8, 26], 
            [9.3, 37],
            [26.8, 101],
            [29.3, 110],
            [5.2, 19],
            [12.1, 45],
            [2.2, 8],
            [31.1, 117],
            [31.6, 119],
            [12.8, 48],
            [15, 56],
            [18.1, 68],
            [9.2, 35],
            [15.4, 58],
            [16.9, 63],
            [25.1, 94],
            [19.5, 73],
            [25.4, 95],
            [29.1, 109],
            [2.6, 10],
            [6.1, 23],
            [9.9, 37],
            [14, 52],
            [18.9, 71],
            [18.8, 70],
            [7.2, 27],
            [9.2, 34],
            [13.7, 51],
            [15, 56],
            [15.6, 59],
            [15.1, 57],
            [14, 53],
            [14.3, 54],
            [7.9, 30],
            [12.2, 46],
            [18.2, 68],
            [7.1, 27],
            [8.6, 32],
            [9.7, 36],
            [13, 49],
            [14.4, 54],
            [26.2, 98],
            [13.5, 51],
            [10.5, 39],
            [11.4, 43],
            [20.6, 77],
            [21.9, 82],
            [19.2, 72],
        ];
        $labels = [
            50, 
            30, 
            30, 
            25, 
            20, 
            40, 
            50, 
            30, 
            30, 
            50, 
            50, 
            25, 
            30, 
            90, 
            100, 
            20, 
            30, 
            15, 
            90, 
            100, 
            40, 
            50, 
            50, 
            30, 
            40, 
            50, 
            100, 
            50, 
            80, 
            100, 
            20, 
            25, 
            40, 
            30, 
            40, 
            50, 
            25, 
            30, 
            40,
            50,
            50,
            50,
            40,
            45,
            30,
            40,
            50,
            25,
            30,
            30,
            40,
            50,
            90,
            40,
            40,
            30,
            50,
            50,
            50,
        ];

        $classifier = new NaiveBayes();
        $classifier->train($samples, $labels);
        
        var_dump($classifier->predict([16.9, 64]));
        var_dump($classifier->predict([16.3, 61]));
        var_dump($classifier->predict([13.9, 52]));
        var_dump($classifier->predict([13.2, 50]));
        var_dump($classifier->predict([14.2, 53]));
        var_dump($classifier->predict([12.7, 48]));
        var_dump($classifier->predict([12.9, 49]));
        var_dump($classifier->predict([12.4, 46]));
        var_dump($classifier->predict([23.2, 88]));
        var_dump($classifier->predict([24.5, 93]));
    }

}
