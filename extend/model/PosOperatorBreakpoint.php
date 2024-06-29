<?php
//pos_operator_breakpoint表
namespace model;
use think\Db;

class PosOperatorBreakpoint extends BaseModel {

	protected $pk='rid';
	protected $name="pos_operator_breakpoint";

    public function GetMaxRidForUpdate() {
        $max_rid=Db::name($this->name)->max("rid");
        return empty($max_rid) ? 0 : $max_rid;
    }
}
