<?php
//front_sendflow表
namespace model;
use think\Db;

class FrontSendflow extends BaseModel {
	
	protected $pk='sheet_no';
	protected $name="front_sendflow";
	
    public function search($condition=[]) {
    	$list=Db::table($this->table)
    	->where($condition)
    	->select();
    }
    
    public function GetSendFlow($sheet_no) {
    	$model = $this->where("sheet_no='$sheet_no'")->find();
    	return $model;
    }
}
