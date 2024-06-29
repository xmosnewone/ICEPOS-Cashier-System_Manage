<?php
//bu_item_stock表
namespace model;
use think\Db;

class ItemStock extends BaseModel {
	
	protected $pk='item_no';
	protected $name="bu_item_stock";
	
    public function ModifyItemStock($item_no, $qty) {
    	
    	$item_stock=Db::table($this->table)
    	->where("item_no='$item_no'")
    	->find();
    	
        if (!empty($item_stock)) {
            if ($item_stock['item_stock'] >= $qty) {
                $sql = "UPDATE ".$this->table." SET item_stock=item_stock-" . $qty . " WHERE item_no='".$item_no."' AND item_stock-" . $qty . " >= 0";
                $ok=Db::execute($sql);
                return $ok!==false? TRUE : FALSE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

}
