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

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use tree\Tree;
use app\admin\model\AdminCargoModel;

class CargoController extends AdminBaseController{
    /**
    * 区域列表
    * @date: 2018年9月13日 下午4:21:11
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function index()
    {
        $content = hook_one('admin_cargo_index_view');
        
        if (!empty($content)) {
            return $content;
        }
        
        session('admin_cargo_index', 'Cargo/index');
        
        $result = Db::name('AdminCargo')->order(["list_order" => "ASC"])->select()->toArray();
        $tree = new Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        
        $newRegions = [];
        foreach ($result as $m) {
            $newRegions[$m['id']] = $m;
        }
        
        foreach ($result as $key => $value) {
            $result[$key]['parent_id_node'] = ($value['parent_id']) ? ' class="child-of-node-' . $value['parent_id'] . '"' : '';
            $result[$key]['style'] = empty($value['parent_id']) ? '' : 'display:none;';
            $result[$key]['str_manage'] = '<a href="' . url("Cargo/add", ["parent_id" => $value['id'], "cargo_id" => $this->request->param("region_id")])
                . '">' . lang('ADD_SUB_CARGO') . '</a>  <a href="' . url("Cargo/edit", ["id" => $value['id'], "cargo_id" => $this->request->param("cargo_id")])
                . '">' . lang('EDIT') . '</a>  <a class="js-ajax-delete" href="' . url("Cargo/delete", ["id" => $value['id'], "menu_id" => $this->request->param("menu_id")]) . '">' . lang('DELETE') . '</a> ';
            $result[$key]['status'] = $value['status'] ? lang('OPEN') : lang('CLOSE');
        }
        
        $tree->init($result);
        $str = "<tr id='node-\$id' \$parent_id_node style='\$style'>
                        <td style='padding-left:20px;'><input name='list_orders[\$id]' type='text' size='3' value='\$list_order' class='input input-order'></td>
                        <td>\$id</td>
                        <td>\$spacer\$name</td>
                        <td>\$status</td>
                        <td>\$str_manage</td>
                    </tr>";
        $category = $tree->getTree(0, $str);
        $this->assign("category", $category);
        return $this->fetch();
    }
    
    /**
    * 添加区域页面
    * @date: 2018年9月13日 下午4:21:24
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function add()
    {
        $tree     = new Tree();
        $parentId = $this->request->param("parent_id", 0, 'intval');
        $result   = Db::name('AdminCargo')->order(["list_order" => "ASC"])->select();
        $array    = [];
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $parentId ? 'selected' : '';
            $array[]       = $r;
        }
        $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $selectCategory = $tree->getTree(0, $str);
        $this->assign("select_category", $selectCategory);
        return $this->fetch();
    }
    
    /**
    * 添加区域提交保存
    * @date: 2018年9月13日 下午4:21:33
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function addPost()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'AdminCargo');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                Db::name('AdminCargo')->strict(false)->field(true)->insert($data);

                $sessionAdminRegionIndex = session('admin_cargo_index');
                $to = empty($sessionAdminRegionIndex) ? "Cargo/index" : $sessionAdminRegionIndex;
                cache(null, 'admin_cargos');//删除后台菜单缓存
                $this->success("添加成功！", url($to));
            }
        }
    }
    
    /**
    * 编辑区域
    * @date: 2018年9月13日 下午4:22:07
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function edit()
    {
        $tree   = new Tree();
        $id     = $this->request->param("id", 0, 'intval');
        $rs     = Db::name('AdminCargo')->where(["id" => $id])->find();
        $result = Db::name('AdminCargo')->order(["list_order" => "ASC"])->select();
        $array  = [];
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $rs['parent_id'] ? 'selected' : '';
            $array[]       = $r;
        }
        $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $selectCategory = $tree->getTree(0, $str);
        $this->assign("data", $rs);
        $this->assign("select_category", $selectCategory);
        return $this->fetch();
    }
    
    /**
    * 区域编辑提交保存
    * @date: 2018年9月13日 下午4:26:52
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $id      = $this->request->param('id', 0, 'intval');
            $oldMenu = Db::name('AdminCargo')->where(['id' => $id])->find();
    
            $result = $this->validate($this->request->param(), 'AdminCargo.edit');
    
            if ($result !== true) {
                $this->error($result);
            } else {
                Db::name('AdminCargo')->strict(false)->field(true)->update($this->request->param());
                
                $sessionAdminRegionIndex = session('admin_cargos_index');
                $to = empty($sessionAdminRegionIndex) ? "Cargo/index" : $sessionAdminRegionIndex;
                cache(null, 'admin_cargos');// 删除后台菜单缓存
                $this->success("保存成功！", url($to));
            }
        }
    }
    
    /**
    * 区域删除
    * @date: 2018年9月13日 下午4:30:50
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function delete()
    {
        $id    = $this->request->param("id", 0, 'intval');
        $count = Db::name('AdminCargo')->where(["parent_id" => $id])->count();
        if ($count > 0) {
            $this->error("该区域下还有子分类，无法删除！");
        }
        if (Db::name('AdminCargo')->delete($id) !== false) {
            $this->success("删除分类成功！");
        } else {
            $this->error("删除失败！");
        }
    }
    
    /**
    * 区域排序
    * @date: 2018年9月13日 下午4:32:49
    * @author: onep2p <324834500@qq.com>
    * @param: variable
    * @return:
    */
    public function listOrder()
    {
        $adminCargoModel = new AdminCargoModel();
        parent::listOrders($adminCargoModel);
        $this->success("排序更新成功！");
    }
}