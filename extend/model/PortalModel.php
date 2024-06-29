<?php
//portal_guestbook表
namespace model;
use think\Db;

class PortalModel extends BaseModel {

	protected $pk='model_id';
	protected $name="portal_model";

    public function GetAllModel(){
        return $list=Db::table($this->table)
    	->where("1=1")
    	->select();
    }
    
    public function GetModelById($modelid){
    	return $this->where("model_id='$modelid'")->find();
    }
    
}
