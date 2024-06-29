<?php
//portal_channel_ext表
namespace model;
use think\Db;

class PortalChannelExt extends BaseModel {

	protected $pk='channel_id';
	protected $name="portal_channel_ext";

	public function Add($model) {
		if ($model->save()) {
			return true;
		} else {
			return false;
		}
	}
	
	public function Del($channelId) {
		$delNum=$this->where("channel_id='$channelId'")->delete();
		if ($delNum > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	
	public function GetChannelExtByOnce($channelId) {
		return $this->where("channel_id='$channelId'")->find();
	}
	
    public function GetChannelExtById($channelid){
        return $this->where("channelid='$channelid'")->find();
    }
}
