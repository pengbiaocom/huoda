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
use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\ModelManager;

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
            [2.2,8],
            [7.6,29],
            [5.2,19],
            [2.6,10],
            [7.4,28],
            [6.8,26],
            [6.1,23],
            [7.2,27],
            [7.1,27],
            [5.7,21],
            [6.7,25],
            [12.1,46],
            [11.4,43],
            [12.4,46],
            [14.5,55],
            [9.3,37],
            [12.1,45],
            [9.2,35],
            [14,52],
            [9.2,34],
            [7.9,30],
            [8.6,32],
            [9.7,36],
            [11.4,43],
            [13.9,52],
            [13.2,50],
            [12.4,46],
            [6.2,23],
            [8.2,31],
            [7.8,29],
            [9.2,34],
            [9.6,36],
            [6.4,24],
            [7.2,27],
            [7.5,28],
            [16.3,61],
            [12.7,48],
            [12.9,49],
            [11.5,43],
            [11.6,44],
            [17.2,65],
            [12.8,48],
            [15.4,58],
            [9.9,37],
            [18.9,71],
            [13.7,51],
            [14,53],
            [12.2,46],
            [13,49],
            [13.5,51],
            [10.5,39],
            [16.9,64],
            [14.2,53],
            [13.5,51],
            [15.2,57],
            [12.2,46],
            [14,53],
            [11.3,42],
            [14.3,54],
            [18.5,69],
            [17,64],
            [18.1,68],
            [15,56],
            [15.3,57],
            [17.4,65],
            [16.3,61],
            [15,56],
            [18.1,68],
            [16.9,63],
            [19.5,73],
            [18.8,70],
            [15,56],
            [15.6,59],
            [15.1,57],
            [18.2,68],
            [14.4,54],
            [20.6,77],
            [21.9,82],
            [19.2,72],
            [12.7,48],
            [20,75],
            [19.7,74],
            [25.4,95],
            [23.2,88],
            [24.5,93],
            [23.2,87],
            [17.6,66],
            [23.1,87],
            [26.3,99],
            [29.1,109],
            [26.8,101],
            [31.1,117],
            [26.2,98],
            [32.2,121],
            [33.5,125],
            [29.3,110],
            [31.6,119],
            [25.1,94],
            [29.1,109],
            [35.6,134],
            [29.8,112],
            [27.3,102],
            [34,128],
            [44.7,168],
            [49.3,186]            
        ];
        $labels = [
            15,
            20,
            20,
            20,
            25,
            25,
            25,
            25,
            25,
            25,
            25,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            30,
            35,
            35,
            35,
            35,
            35,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            40,
            45,
            45,
            45,
            45,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            50,
            55,
            60,
            80,
            80,
            80,
            80,
            80,
            80,
            80,
            85,
            90,
            90,
            90,
            90,
            90,
            100,
            100,
            100,
            100,
            100,
            100,
            100,
            120,
            135,
            150
        ];

        //$classifier = new NaiveBayes();
        $classifier = new SVC(Kernel::LINEAR, $cost=10000);
        $classifier->train($samples, $labels);
        $filepath = "/model";
        $modelManager = new ModelManager();
        $modelManager->saveToFile($classifier, $filepath);
    }
    
    public function predict(){
        $filepath = "/model";
        $modelManager = new ModelManager();
        $classifier = $modelManager->restoreFromFile($filepath);
        var_dump($classifier->predict([5.4, 20]));
        var_dump($classifier->predict([6.6, 25]));
        var_dump($classifier->predict([6.5, 25]));
        var_dump($classifier->predict([13.9, 52]));
        var_dump($classifier->predict([20.9, 78]));
        var_dump($classifier->predict([11.4, 43]));
        var_dump($classifier->predict([11.7, 44]));
    }

}
