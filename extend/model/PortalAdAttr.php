<?php
//portal_ad_attr表
namespace model;
use think\Db;

class PortalAdAttr extends BaseModel {
	
	protected $name="portal_ad_attr";
   
    public function GetAdAttrByAdid($adId) {
        return $this->where("ad_id='$adId'")->select();
    }

    public function GetAdAttrByPid($pid) {
        return $this->where("pid='$pid'")->select();
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
    
    
    public function Del($adId) {
    	try {
    		$delNum=$this->where("ad_id='$adId'")->delete();
    		if ($delNum > 0) {
    			return TRUE;
    		} else {
    			return FALSE;
    		}
    	} catch (\Exception $e) {
    		return FALSE;
    	}
    }

    public function DelByPid($pid) {
        try {
            $delNum=$this->where("pid='$pid'")->delete();
            if ($delNum > 0) {
                return TRUE;
            } else {
                return FALSE;
            }
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    public function InsertGetId($model) {
        try {
            if ($model->save()) {
                return $model->id;
            } else {
                return FALSE;
            }
        } catch (\Exception $ex) {
            return FALSE;
        }
    }
}
