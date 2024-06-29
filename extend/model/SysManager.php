<?php
//sys_manager表
namespace model;
use think\Db;

class SysManager extends BaseModel {

	protected $pk='id';
	protected $name="sys_manager";
	
    public function validatePassword($password) {
        return $password === $this->password;
    }

    public function checkloginname($loginname) {
        $ishave = $this->where("loginname='$loginname'")->count();
        return $ishave;
    }

    public function GetByName($loginname) {
        return $this->where("loginname='$loginname'")->find();
    }

}
