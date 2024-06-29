<?php
//portal_guestbook_ext表
namespace model;
use think\Db;

class PortalGuestbookExt extends BaseModel {
	
	protected $pk='guestbook_id';
	protected $name="portal_guestbook_ext";
	
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
    
    public function Del($gbid) {
    	try {
    		$delNUm=$this->where("guestbook_id='$gbid'")->delete();
    		if ($delNUm> 0) {
    			return TRUE;
    		} else {
    			return FALSE;
    		}
    	} catch (\Exception $e) {
    		return FALSE;
    	}
    }
    
    public function GetGuestbookExtById($gbid) {
    	return $this->where("guestbook_id='$gbid'")->find();
    }
}