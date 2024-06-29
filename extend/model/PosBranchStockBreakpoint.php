<?php
//pos_branch_stock_breakpoint表
namespace model;
use think\Db;

class PosBranchStockBreakpoint extends BaseModel {
	
	protected $pk='rid';
	protected $name="pos_branch_stock_breakpoint";

    public function GetMaxRidForUpdate() {
        $max_rid=Db::name($this->name)->max("rid");
        return empty($max_rid) ? 0 : $max_rid;
    }
}
