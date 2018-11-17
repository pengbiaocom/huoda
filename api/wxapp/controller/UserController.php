<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use cmf\controller\RestBaseController;
use wxapp\aes\WXBizDataCrypt;
use app\user\model\UserModel;

class UserController extends RestBaseController
{
    // 获取用户信息
    public function getUserInfo()
    {

        $id        = $this->request->param("id", 0, "intval");
        $userModel = new UserModel();
        $user      = $userModel->where('id', $id)->find();
        if (empty($user)) {
            $this->error("查无此人！");
        }
        $user['birthday'] = date("Y-m-d",$user['birthday']);
        $this->success("查询成功",$user);

    }

    public function  update_user(){
        $id = $this->request->param("id", 0, "intval");;

        $userModel = new UserModel();
        $user      = $userModel->where('id', $id)->find();
        if (empty($user)) {
            $this->error("查无此人！");
        }

        $data['user_nickname'] = $this->request->param("user_nickname");
        $data['sex'] = $this->request->param("sex");
        $data['birthday'] = $this->request->param("birthday");
        $data['birthday'] = strtotime($data['birthday']);
        $data['mobile'] = $this->request->param("mobile");

        if(db("user")->where('id', $id)->update($data)){
            $this->success("修改成功");
        }else{
            $this->error("更新失败");
        }

    }

}
