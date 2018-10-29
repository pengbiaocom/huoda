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

    public function  get_estimated_time(Request $request){
        $id = $request->param('id');

        if(empty($id))  return json(['code'=>1,'msg'=>'缺少参数']);

        $info = db("admin_region")->where(['id'=>$id])->find();
        if($info){
            return json(['code'=>0,'msg'=>'success','data'=>$info]);
        }else{
            return json(['code'=>1,'msg'=>'没有数据']);
        }
    }

    public function  get_address_byid(Request $request){
        $id = $request->param('id');

        if(empty($id))  return json(['code'=>1,'msg'=>'缺少参数']);

        $info = db("user_address")->where(['id'=>$id])->find();
        if($info){
            return json(['code'=>0,'msg'=>'success','data'=>$info]);
        }else{
            return json(['code'=>1,'msg'=>'没有数据']);
        }
    }

    public function get_address(Request $request){
        $keyword = $request->param('keyword');
        $uid = $request->param('uid');

        if(empty($keyword)) return json(['code'=>1,'msg'=>'缺少参数']);
//        $where = " "
    }

    public function  add(Request $request){

        $data['province_id'] = $request->param('province_id');
        $data['city_id'] = $request->param('city_id');
        $data['county_id'] = $request->param('county_id');
        $data['address'] = $request->param('address');
        $data['user_name'] = $request->param('user_name');
        $data['user_tel'] = $request->param('user_tel');
        $data['uid'] = $request->param('uid');
        $data['update_time'] = time();
        $data['types'] = $request->param('types');

        if(empty($data['user_name'])){
            return json(['code'=>1,'msg'=>'请输入发件人姓名']);
        }else if(!preg_match("/^1[345678]{1}\d{9}$/",$data['user_tel'])){
            return json(['code'=>1,'msg'=>'请输入正确的手机号']);
        }else if(empty($data['province_id']) || empty($data['city_id']) || empty($data['county_id']) || empty($data['address'])){
            return json(['code'=>1,'msg'=>'请填写地址']);
        }else{
            if($id = db("user_address")->insertGetId($data)){
                return json(['code'=>0,'msg'=>'新增地址成功','id'=>$id]);
            }else{
                return json(['code'=>1,'msg'=>'新增地址失败']);
            }
        }
    }

}