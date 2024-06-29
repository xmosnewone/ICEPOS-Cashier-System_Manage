<?php
//portal_guestbook表
namespace model;
use think\Db;
use model\PortalGuestbookExt;

class PortalGuestbook extends BaseModel {
	
	protected $pk='guestbook_id';
	protected $name="portal_guestbook";

	//$model 是PortalGuestboook实例化对象
	//$gbExt 是PortalGuestbookExt实例化对象
    public function Add($model, $gbExt) {
        $isCommit = false;
        Db::startTrans();
        try {
            if ($model->save()) {

                $gbExt->guestbook_id = $model->guestbook_id;
                if ($gbExt->Add($gbExt)) {
                    $isCommit = TRUE;
                } else {
                    Db::rollback();
                    return FALSE;
                }
            } else {
                Db::rollback();
                return FALSE;
            }
            if ($isCommit) {
                Db::commit();
                return TRUE;
            }
        } catch (\Exception $ex) {
             Db::rollback();
            return FALSE;
        }
    }
    
    public function Del($gbid) {
    	$isCommit = false;
    	Db::startTrans();
    	try {
    		$delNum=$this->where("guestbook_id='$gbid'")->delete();
    		if ($delNum > 0) {
    			$PortalGuestbookExt=new PortalGuestbookExt();
    			$gbExt = $PortalGuestbookExt->GetGuestbookExtById($gbid);
    			if (!$PortalGuestbookExt->Del($gbid)) {
    				Db::rollback();
    				return FALSE;
    			}
    			$isCommit = TRUE;
    		} else {
    			return FALSE;
    		}
    		if ($isCommit) {
    			Db::commit();
    			return TRUE;
    		}
    	} catch (\Exception $e) {
    		Db::rollback();
    		return FALSE;
    	}
    }
    
    
    public function GetAllGuestBook() {
    	return $this->select();
    }

    public function GetGuestbookById($gbid) {
    	return $this->where("guestbook_id='$gbid'")->find();
    }
    
    public function GetGuestbookByIp($ip) {
        return $this->field("ip, create_time")
        			->where("ip='$ip'")
        			->order("create_time DESC")
        			->find();
    }
    
}
