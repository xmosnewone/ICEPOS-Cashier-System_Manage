<?php
//pc_branch_price_breakpoint表
namespace model;
use think\Db;

class PcBranchPriceBreakpoint extends BaseModel {
	
	protected $pk='rid';
	protected $name="pc_branch_price_breakpoint";

    public function GetMaxRidForUpdate() {
        $max_rid=Db::name($this->name)->max("rid");
        return empty($max_rid) ? 0 : $max_rid;
    }

}
