<?php
/**
 * bd_item_barcode_breakpoint表
 */
namespace model;
use think\Db;

class BdItemBarcodeBreakpoint extends BaseModel {

	protected $pk='rid';
	protected $name="bd_item_barcode_breakpoint";
    
    public function GetMaxRidForUpdate() {  
        $max_rid=Db::name($this->name)->max("rid");
        return empty($max_rid) ? 0 : $max_rid;
    }

}
