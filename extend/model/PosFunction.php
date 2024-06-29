<?php
//pos_function表
namespace model;
use think\Db;

class PosFunction extends BaseModel {

	protected $pk='func_id';
	protected $name="pos_function";

    public function GetModelsForPos($branch_no) {
        return $this->where("branch_no='$branch_no'")->select();
    }
}
