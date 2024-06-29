<?php
namespace app\admin\controller;
use app\admin\controller\Super;
use app\admin\components\Enumerable\EOperStatus;

use model\Item_cls;
use model\BdItemClsBreakpoint;
use model\ItemInfo;
/**
 * 商品分类
 *
 */
class Itemcls extends Super {

    public function index() {
       return $this->fetch("product/cls_list");
    }

    //分类列表数据
    public function dataList() {
    	$item_cls = new Item_cls();
    	$page = input('page') ? intval(input('page')) : 1;
    	$rows = input('limit') ? intval(input('limit')) : 10;
    	
    	$rowCount = $item_cls->count();
    	$offset = ($page - 1) * $rows;
    	$list = $item_cls->order("update_time desc,orderby asc")->limit($offset,$rows)->select();
    	return listJson(0,lang("success_data"),$rowCount, $list);
    }
    
    //显示添加商品分类页面
    public function clsAdd(){
    	
    	$item_cls = new Item_cls();
    	//选择父分类
    	$parent=input("parent");
    	$this->assign("parent",$parent);
    	
    	//编辑
    	$itemcls=input("itemcls");
    	if($itemcls){
    		$one=$item_cls->GetItemClsByClsno($itemcls);
    		$this->assign("one",$one);
    	}
    	
    	if($itemcls&&$one){
    		$cls_parent=$item_cls->GetItemClsByClsno($one['cls_parent']);
    	}else if($parent){
    		$cls_parent=$item_cls->GetItemClsByClsno($parent);
    	}

    	$this->assign("cls_parent",$cls_parent);
    	
    	return $this->fetch("product/cls_add");
    }
    
    //添加或者编辑商品分类
    public function clspost() {
        
            $cls_parent = input('cls_parent');
            $item_clsno = input('item_clsno');
            $item_clsname = input('item_clsname');
            $display_flag = input("display_flag");
            $orderby = input("orderby",0,'intval');
            $is_modify = input("modify");

            $Item_cls=new Item_cls();
            if ($cls_parent != '') {
                $num = $Item_cls->where("item_clsno='$cls_parent'")->count();
                if ($num == '0') {
                    $r['code']=false;
                    $r['msg']=lang("cls_parent_empty");
                    return $r;
                }
            }
            $is_add = true;
			$ok=false;
            $model = new Item_cls();
            if ($is_modify !=1) {
                $num = $Item_cls->where("item_clsno='$item_clsno'")->count();
                if ($num > 0) {
                    $r['code']=false;
                    $r['msg']=lang("cls_exist");
                    return $r;
                } else {
                    $content['item_clsno'] = $item_clsno;
                    $content['item_clsname'] = $item_clsname;
                    $content['cls_parent'] = $cls_parent;
                    $content["display_flag"] = $display_flag;
                    $content["update_time"] = date(DATETIME_FORMAT);
                    $content["orderby"] = $orderby;
                }
                $ok=$model->save($content);
            } else {
                $model = $Item_cls->where("item_clsno='$item_clsno'")->find();
                $model->cls_parent = $cls_parent;
                $model->display_flag = $display_flag;
                $model->item_clsname = $item_clsname;
                $model->orderby = $orderby;
                $model->update_time = date(DATETIME_FORMAT);
                $is_add = false;
                $ok=$model->save();
            }
            if ($model->save()) {
                $bdBreakPoint = new BdItemClsBreakpoint();
                $bdBreakPoint->rtype = $is_add ? EOperStatus::ADD : EOperStatus::UPDATE;
                $bdBreakPoint->item_clsno = $item_clsno;
                $bdBreakPoint->updatetime = date(DATETIME_FORMAT);
                $bdBreakPoint->save();
                $r['code'] = true;
                $r['msg'] = lang("update_success");
                return $r;
            } else {
                $r['code'] = false;
                $r['msg'] = lang("update_error");
                return $r;
            }
    }
	
    //删除商品分类
    public function clsDel() {
        $item_clsno = input('item_clsno');
        $model = new Item_cls();
        $num = $model->where("cls_parent='$item_clsno'")->count();
        if ($num > 0) {
            $r['code'] = false;
            $r['msg'] = lang("cls_child_notempty");
            return $r;
        }
		$ItemInfo=new ItemInfo();
        $num = $ItemInfo->where("item_clsno='$item_clsno'")->count();
        if ($num > 0) {
            $r['code'] = false;
            $r['msg'] = lang("cls_item_notempty");
            return $r;
        }
        $rrow = $model->where("item_clsno='$item_clsno'")->find();
        if ($rrow->delete()) {
            $bdBreakPoint = new BdItemClsBreakpoint();
            $bdBreakPoint->rtype = EOperStatus::DELETE;
            $bdBreakPoint->item_clsno = $item_clsno;
            $bdBreakPoint->updatetime = date(DATETIME_FORMAT);
            $bdBreakPoint->save();
            $r['code'] = true;
            $r['msg'] = lang("delete_success");
            exit(json_encode($r));
        } else {
            $r['code'] = false;
            $r['msg'] = lang("delete_error");
            return $r;
        }
    }

}
