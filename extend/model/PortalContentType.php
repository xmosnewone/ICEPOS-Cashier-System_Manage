<?php
//portal_content_type表
namespace model;
use think\Db;

class PortalContentType extends BaseModel {

	protected $pk='type_id';
	protected $name="portal_content_type";
	
    public function GetAllType(){
        return $list=$this->select();
    }

     public function GetTypeById($typeid){
     	return Db::table($this->table)
     	->where("type_id='$typeid'")
     	->find();
    }
}
