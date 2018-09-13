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

class RegionController extends AdminBaseController{
    public function index()
    {
//         $content = hook_one('admin_region_index_view');
        
//         if (!empty($content)) {
//             return $content;
//         }
        
        session('admin_region_index', 'Region/index');
        
        $result = Db::name('AdminRegion')->order(["list_order" => "ASC"])->select()->toArray();
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
            $result[$key]['str_manage'] = '<a href="' . url("Region/add", ["parent_id" => $value['id'], "region_id" => $this->request->param("region_id")])
                . '">' . lang('ADD_SUB_REGION') . '</a>  <a href="' . url("Region/edit", ["id" => $value['id'], "region_id" => $this->request->param("region_id")])
                . '">' . lang('EDIT') . '</a>  <a class="js-ajax-delete" href="' . url("Region/delete", ["id" => $value['id'], "menu_id" => $this->request->param("menu_id")]) . '">' . lang('DELETE') . '</a> ';
            $result[$key]['status'] = $value['status'] ? lang('OPEN') : lang('CLOSE');
        }
        
        $tree->init($result);
        $str      = "<tr id='node-\$id' \$parent_id_node style='\$style'>
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
}