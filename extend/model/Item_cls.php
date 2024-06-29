<?php
//bd_item_cls表
namespace model;
use think\Db;
use model\BdItemClsBreakpoint;

class Item_cls extends BaseModel {

	protected $pk='item_clsno';
	protected $name="bd_item_cls";

    public function GetIitemClsByClsnoOrName($cls_no, $cls_name) {
      
        $where="1=1";
        if(!empty($cls_no) || !empty($cls_name)){
        	if (!empty($cls_no)) {
        		$where.=" and item_clsno like '%$cls_no%'";
        	}
        	if (!empty($cls_name)) {
        		$where.=" and item_clsname like '%$cls_name%'";
        	}
        }else{
        	$where="LENGTH(cls_parent)=0";
        }
        
        $list=Db::table($this->table)
        ->field("item_clsno,item_clsname,cls_parent")
        ->where($where)
        ->select();
        
        return $list;
    }

    public function GetItemClsByClsno($item_clsno) {
        return $this->where("item_clsno='$item_clsno'")->find();
    }

    public function GetItemClses() {
        return $this->order("update_time desc")->select();
    }

    public function GetItemCls($cls_parent) {
        return $this->where("cls_parent='$cls_parent'")->select();
    }

    public function AddOrSaveItemCls($item_cls, $isadd) {
        $vertify = $this->checkModel($item_cls);
        if ($vertify == "ok") {
            if ($item_cls->cls_parent == "-1") {
                $item_cls->cls_parent = "";
            }
            if ($isadd == "0") {
                $model = $this->where("item_clsno='{$item_cls->item_clsno}'")->select();
                if (count($model) > 0) {
                    return "REPEAT_CLSNO";
                }
            }
            if ($isadd == "0") {
                $name_model = $this->where("item_clsname='{$item_cls->item_clsname}'")->select();
            } else {
                $name_model = $this->where("item_clsname='{$item_cls->item_clsname}' and item_clsno <> '{$item_cls->item_clsno}'")->select();
            }
            if (count($name_model)) {
                return "REPEAT_CLSNAME";
            }

            if ($item_cls->save()) {
                return "OK";
            } else {
                return "ERROR";
            }
        } else {
            return $vertify;
        }
    }


    private function checkModel($item_cls) {
        if (mb_strlen($item_cls->item_clsno, "utf8") <= 0 || mb_strlen($item_cls->item_clsno, "utf8") > 12) {
            return "商品分类编号不能为空并且长度不能超过12";
        } else if (mb_strlen($item_cls->item_clsname, "utf8") <= 0 || mb_strlen($item_cls->item_clsname, "utf8") > 12) {
            return "商品分类名称不能为空并且长度不能超过20";
        }
        return "ok";
    }


    public function GetItemClsResult($pid) {
        $result = $this->GetItemCls($pid);
        $arr = array();
        foreach ($result as $k => $v) {
            array_push($arr, $v);
        }
        return $arr;
    }


    private function GetItemClsByClsParent(& $arr, $cls_parent) {
        $temp = $this->GetItemCls($cls_parent);
        if (!empty($temp)) {
            foreach ($temp as $k => $v) {
                array_push($arr, $v);
                $this->GetItemClsByClsParent($arr, $v->item_clsno);
            }
        }
    }


    public function GetItemClsByParentNo($clsno) {
        $result = $this->GetItemCls($clsno);
        $arr = array();
        foreach ($result as $k => $v) {
            array_push($arr, $v);
            $this->GetItemClsByClsParent($arr, $v->item_clsno);
        }
        return $arr;
    }

    public $rtype;
    public $rid;
    public $updatetime;

    public function GetUpdateDataForPos($rid = "", $updatetime = "") {
    	
        if ($rid == "-1") {
            $result=Db::name($this->name)
            ->alias('s')
            ->field("0 as rid,'I' as rtype,now() as updatetime,s.item_clsno,s.item_clsname,s.item_flag,s.cls_parent,s.display_flag")
            ->order("s.orderby asc")
            ->select();
        } else {
       
            if (empty($rid)) {
            	$rid = 0;
            }

            $where="1=1";
            if($rid>0){
                $where.=" and a.rid > $rid";
            }
            if (!empty($updatetime)) {
            	$where.=" and a.updatetime>$updatetime";
            }
            
            $result=Db::name($this->name)
            ->alias('s')
            ->join("bd_item_cls_breakpoint a","s.item_clsno=a.item_clsno","LEFT")
            ->field("a.rid,a.rtype,a.updatetime,a.item_clsno,s.item_clsname,s.item_flag,s.cls_parent,s.display_flag")
            ->where($where)
            ->order("s.orderby asc")
            ->select();
        }

        if ($rid == "-1") {
        	$BdItemClsBreakpoint=new BdItemClsBreakpoint();
            $r_id = $BdItemClsBreakpoint->GetMaxRidForUpdate();
        }

        $list = array();
        foreach ($result as $v) {
            $tt = array();
            $tt["rid"] = $rid == "-1" ? $r_id : $v["rid"];
            $tt["rtype"] = $v["rtype"];
            $tt["updatetime"] = $v["updatetime"];
            $tt["item_clsno"] = $v["item_clsno"];
            $tt["item_clsname"] = $v["item_clsname"];
            $tt["item_flag"] = $v["item_flag"];
            $tt["cls_parent"] = $v["cls_parent"];
            $tt["display_flag"] = $v["display_flag"];
            array_push($list, $tt);
        }
        return $list;
    }
    
    //添加或修改，如果有主键
    public function Add($model) {
    	try {
    		if ($model->save()) {
    			return TRUE;
    		} else {
    			return FALSE;
    		}
    	} catch (\Exception $ex) {
    		return FALSE;
    	}
    }

}
