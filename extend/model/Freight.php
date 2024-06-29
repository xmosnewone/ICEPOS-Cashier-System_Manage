<?php
//as_freight表
namespace model;
use think\Db;

class Freight extends BaseModel{
	
	protected $pk='id';
	protected $name="as_freight";
	
    public function GetFreightByIdForName($id){
     	$freight=Db::table($this->table)
     	->where(array("id"=>$id))->find();
     	return $freight["name"];
    }
    
    public function GetAllFreight(){
    	$freight = $this->select();
    	return $freight;
    }
}

