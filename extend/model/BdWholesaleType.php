<?php
//bd_wholesale_type表
namespace model;
use think\Db;
class BdWholesaleType extends BaseModel {
	
	protected $pk='type_no';
	protected $name="bd_wholesale_type";
	
    public function search() {
    	return $list=Db::table($this->table)
    	->where($where)
    	->select();
    }

    public function GetDetaultDiscust($type_no = 1) {
    	
    	$one=Db::table($this->table)
    	->where("type_no='$type_no'")
    	->find();
    	
    	return $one;
    }
}
