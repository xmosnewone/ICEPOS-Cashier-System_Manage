<?php
//portal_guestbook_ctg表
namespace model;
use think\Db;

class PortalGuestbookCtg extends BaseModel {

	protected $pk='guestbook_ctg_id';
	protected $name="portal_guestbook_ctg";
	
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

    public function Del($gbcid) {
        try {
        	$delNum=$this->where("guestbook_ctg_id='$gbcid'")->delete();
            if ($delNum > 0) {
                return TRUE;
            } else {
                return FALSE;
            }
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    public function GetGuestbookCtg() {
        return $this->select();
    }

    public function GetGuestbookCtgById($gbcid) {
        return $this->where("guestbook_ctg_id='$gbcid'")->find();
    }

}
