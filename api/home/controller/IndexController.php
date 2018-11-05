<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\home\controller;

use think\Db;
use think\Validate;
use cmf\controller\RestBaseController;

use Phpml\Regression\LeastSquares;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\Regression\SVR;
use Phpml\Association\Apriori;

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

        /*将上面的数据放入$samples数组里
*/
        $samples = [[2010], [2011], [2012], [2013], [2014], [2015],[2016]];
        /*
        在labels中存入每年的股价涨势
        */
        $labels = [1.1, 1.2, 2.1, 3.1, 3.3, 4.1,5.1];
        /*
        下面我们采用最小二乘法逼近线性模型进行预测
        */
        $regression = new LeastSquares();
        /*
        下面我们采用libsvm的向量回归进行预测
        */
        $regression = new SVR(Kernel::LINEAR);
        /* 对其进行训练   */
        $regression->train($samples, $labels);
        /*
        如果我们想知道2017年张氏股的涨势是什么样的，我们用最小二乘法逼近线性模型来进行预测
        */
        print_r($regression->predict([2017]));
    }

    public function demo1(){
        /*将上面的数据放入$samples数组里
*/
        $samples = [['衣服', '鞋子', '辣条'], ['辣条', '面条', '席子'], ['衣服','席子', '面条'], ['衣服','面条','鞋子'],['衣服', '面条', '辣条'],['衣服', '鞋子', '辣条']];
        $labels  = [];
        /*
        参数
        support支持度
        confidence 自信度
        */
        $associator = new Apriori($support = 0.5, $confidence = 0.5);
        /* 对其进行训练   */
        $associator->train($samples, $labels);
        /*
        假设又有一位G用户，他购买了衣服，
        电商网站想要通过他购买的衣服给她推荐别的产品
        以便他购买更多的商品
        系统会根据以往用户的训练数据推断出G用户可能需要的商品
        */
        print_r($associator->predict(['衣服']));
    }

}
