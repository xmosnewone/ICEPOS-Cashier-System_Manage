<?php
//bd_item_cls表
namespace model;
use think\Db;

class ItemClass extends BaseModel {
	
	protected $pk='item_clsno';
	protected $name="bd_item_cls";

    public function GetChildClass($clsno = "") {
        
        $where="display_flag <> 0 and cls_parent='". $clsno. "' and is_pifa='1' ";
        $list=Db::table($this->table)
        ->field("item_clsno,item_clsname,item_flag,cls_parent,display_flag")
       	->where($where)
        ->select();
        return $list;
    }

    public function GetClass() {

        $where="display_flag <> 0 and is_pifa='1' ";
        $list=Db::table($this->table)
        ->field("item_clsno,item_clsname,item_flag,cls_parent,display_flag")
        ->where($where)
        ->select();
        return $list;
    }

    public function GetRelateClass($cls_no) {
    	
        $table=$this->table;
        $sql = "select item_clsno,item_clsname from " .
                $table . " as s " .
                "   where s.cls_parent =( select cls_parent from " . $table .
                "   where item_clsno='$cls_no' ) and item_clsno <> '$cls_no' ";

        $list=Db::query($sql);
        return $list;
    }

}
