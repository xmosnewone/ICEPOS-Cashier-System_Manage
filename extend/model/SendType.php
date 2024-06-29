<?php
//send_type表
namespace model;
use think\Db;

class SendType extends BaseModel {

	protected $pk='send_no';
	protected $name="send_type";

    public function GetSend($send_no) {
    	return $this->where("send_no='$send_no'")->find();
    }

}
