<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/22
 * Time: 16:39
 */
namespace api\wxapp\controller;

use think\Db;
use cmf\controller\RestBaseController;
use api\portal\model\PortalCategoryModel;
use api\portal\model\PortalPostModel;

class MessageController extends RestBaseController{

    protected $postModel;

    public function __construct(PortalPostModel $postModel)
    {
        parent::__construct();
        $this->postModel = $postModel;
    }

    /**
     * 新闻消息
     */
    public function  lists(){
        $categoryId = $this->request->param('category_id', 2, 'intval');

        $limit = $this->request->param('limit', 10, 'intval');
        $page = $this->request->param('page', 1, 'intval');

        $portalCategoryModel = new  PortalCategoryModel();

        $findCategory = $portalCategoryModel->where('id', $categoryId)->find();

        //分类是否存在
        if (empty($findCategory)) {
            $this->error('分类不存在！');
        }

        $param = $this->request->param();

        if(empty($param['order'])){
            $param['order']='-post.published_time';
        }

        $articles = $portalCategoryModel->paramsFilter($param, $findCategory->articles()->alias('post'))->select();

        if (!empty($param['relation'])) {
            if (count($articles) > 0) {
                $articles->load('user');
                $articles->append(['user']);
            }
        }

        $this->success('ok', ['list' => $articles,'paginate'=>array('page'=>sizeof($articles) < $limit ? $page : $page+1, 'limit'=>$limit)]);
    }

    public function  problem(){
        $categoryId = $this->request->param('category_id', 1, 'intval');

        $limit = $this->request->param('limit', 10, 'intval');
        $page = $this->request->param('page', 1, 'intval');

        $portalCategoryModel = new  PortalCategoryModel();

        $findCategory = $portalCategoryModel->where('id', $categoryId)->find();

        //分类是否存在
        if (empty($findCategory)) {
            $this->error('分类不存在！');
        }

        $param = $this->request->param();

        if(empty($param['order'])){
            $param['order']='-post.published_time';
        }

        $articles = $portalCategoryModel->paramsFilter($param, $findCategory->articles()->alias('post'))->select();

        if (!empty($param['relation'])) {
            if (count($articles) > 0) {
                $articles->load('user');
                $articles->append(['user']);
            }
        }

        $this->success('ok', ['list' => $articles,'paginate'=>array('page'=>sizeof($articles) < $limit ? $page : $page+1, 'limit'=>$limit)]);
    }

    /**
     * 获取指定的文章
     * @param int $id
     */
    public function read()
    {
        $id = $this->request->param('id', 1, 'intval');
        if (intval($id) === 0) {
            $this->error('无效的文章id！');
        } else {
            $params                       = $this->request->get();
            $params['where']['post_type'] = 1;
            $params['id']                 = $id;
            $data                         = $this->postModel->getDatas($params);
            if (empty($data)) {
                $this->error('文章不存在！');
            } else {
                $this->postModel->where('id', $id)->setInc('post_hits');
                $data        = $data->toArray();
                if($data){
                    $data['user_name'] = db("user")->where(['id'=>$data['user_id']])->value("user_nickname");
                }
                $this->success('请求成功!', $data);
            }

        }
    }

}