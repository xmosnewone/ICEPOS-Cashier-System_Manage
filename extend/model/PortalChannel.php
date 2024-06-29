<?php
//portal_channel表
namespace model;
use model\PortalChannelExt;
use think\Db;

class PortalChannel extends BaseModel {

	protected $pk='channel_id';
    protected $name="portal_channel";
    
    public function Add($cModel, $eModel) {
    	Db::startTrans();
    	try {
    		if ($cModel->save()) {
    			$eModel->channel_id = $cModel->channel_id;
    			if ($eModel->Add($eModel)) {
    				Db::commit();
    				return TRUE;
    			} else {
    				Db::rollback();
    				return FALSE;
    			}
    		} else {
    			Db::rollback();
    			return FALSE;
    		}
    	} catch (\Exception $ex) {
    		Db::rollback();
    		return FALSE;
    	}
    }
    
    
    public function Del($channelId) {
    	Db::startTrans();
    	try {
    		$delNum=$this->where("channel_id='$channelId'")->delete() ;
    		if ($delNum > 0) {
    			$channelExt = new PortalChannelExt();
    			if ($channelExt->Del($channelId)) {
    				Db::commit();
    				return TRUE;
    			} else {
    				return FALSE;
    			}
    		} else {
    			return FALSE;
    		}
    	} catch (\Exception $e) {
    		Db::rollback();
    		return FALSE;
    	}
    }
    
    
    public function GetChannelByOnce($channelId) {
    	return $this->where("channel_id='$channelId'")->find();
    }
    
    
    public function GetChildChannel($parentid) {
    	return $this->where("parent_id='$parentid'")->select();
    }
    
    public function GetChannelChild($channelid){
        $table=$this->table;
        $ChannelExtModel=new PortalChannelExt();
        $ChannelExtModelTable=$ChannelExtModel->tableName();
        
        $sql = "SELECT c.channel_id,e.channel_name,e.link, e.title, e.keywords,e.description"
        		. " FROM " . $table . " AS c"
        		. " LEFT JOIN " .$ChannelExtModelTable. " AS e ON c.channel_id=e.channel_id"
        		. " WHERE c.is_display=1 AND c.parent_id=" . $channelid.""
        		. " ORDER BY c.priority DESC";
        $list=Db::query($sql);
        return $list;
    }

}
