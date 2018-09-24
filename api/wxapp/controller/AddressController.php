<?php
/**
 * Created by PhpStorm.
 * User: 离殇<pengxuancom@164.com>
 */
namespace api\wxapp\controller;

use think\Controller;
use think\Request;

class AddressController extends Controller{


    public function get_provinces(){

        $district = db("admin_region")->where(['parent_id'=>0,'status'=>1])->order("list_order asc,id asc")->select();

        return json($district);
    }

    public function get_citys(Request $request){
        $upid = $request->param('upid');

        if(empty($upid))  $upid = 7;

        $district = db("admin_region")->where(['parent_id'=>$upid,'status'=>1])->order("list_order asc,id asc")->select();

        return json($district);
    }

    public function get_countys(Request $request){
        $upid = $request->param('upid');

        if(empty($upid))  $upid = 1;

        $district = db("admin_region")->where(['parent_id'=>$upid,'status'=>1])->order("list_order asc,id asc")->select();

        return json($district);
    }

}