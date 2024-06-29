<?php
//sys_operator_role表
namespace model;
use think\Model;

class Operator extends BaseModel{

	protected $pk='id';
	protected $name="sys_operator_role";

    public function getPerm($id)
   {
		return $this->where("rid='$id'")->find();
    }

}
