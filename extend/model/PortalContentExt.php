<?php
//portal_content_ext表
namespace model;
use think\Db;

class PortalContentExt extends BaseModel {

	protected $pk='content_id';
	protected $name="portal_content_ext";
	
	public function Add($model) {
		if ($model->save()) {
			return true;
		} else {
			return false;
		}
	}
	
	//单个删除
	public function Del($contentid) {
		$delNum=$this->where("content_id='$contentid'")->delete();
		if ($delNum > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	//批量删除
	public function BatchDel($idString) {
		$delNum=$this->where("content_id in ($idString)")->delete();
		if ($delNum > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
    public function GetContentExtById($contentid){
    	return $this->where("content_id='$contentid'")->find();
    }
 
}
