<?php
//portal_ad_space表
namespace model;
use think\Db;

class PortalAdSpace extends BaseModel {
	
	protected $pk='ad_space_id';
	protected $name="portal_ad_space";

    public function GetAllAdSpace() {
        return $this->select();
    }

    public function GetEnabledAdSpace() {
    	return $this->where("is_enabled='1'")->select();
    }

    public function GetAdSpaceById($adSpaceid) {
    	return $this->where("ad_space_id='$adSpaceid'")->find();
    }
    
    public function Add($model) {
    	try {
    		if ($model->save()) {
    			return TRUE;
    		} else {
    			return FALSE;
    		}
    	} catch (\Exception $ex) {
    		return FALSE;
    	}
    }
    
    
    public function Del($adspaceid) {
    	try {
    		$delNum=$this->where("ad_space_id = '{$adspaceid}'")->delete();
    		if ($delNum > 0) {
    			return TRUE;
    		} else {
    			return FALSE;
    		}
    	} catch (\Exception $e) {
    		return FALSE;
    	}
    }

}
