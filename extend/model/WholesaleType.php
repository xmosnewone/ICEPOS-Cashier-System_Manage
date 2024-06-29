<?php
//bd_wholesale_type表
namespace model;
use think\Db;

class WholesaleType extends BaseModel{

	protected $pk='type_no';
	protected $name="bd_wholesale_type";
	
	public function GetOne($conditio=[]){
		return $this->where($conditio)->find();
	}
	
    public function getAllList(){
        return $this->select();
    }
}
