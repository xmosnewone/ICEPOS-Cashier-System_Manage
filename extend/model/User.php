<?php
//sys_manager表
namespace model;
use think\Model;

class User extends BaseModel
{
	protected $pk='id';
	protected $name="sys_manager";
	
	public $username;
	public $password;
	
	public $errorCode;
	
	const ERROR_NONE=0;
	const ERROR_USERNAME_INVALID=1;
	const ERROR_PASSWORD_INVALID=2;
	const ERROR_UNKNOWN_IDENTITY=100;
	
	public function __construct($username='',$password=''){
		$this->username=$username;
		$this->password=$password;
	}
	
	public function authenticate() {
		
		$user=Db::table($this->table)
		->where("username='{$this->username}'")
		->find();
		
		if ($user != false&&$user['id']>0) {
			if ($user['password']!= $this->password) {
				$this->errorCode = self::ERROR_PASSWORD_INVALID;
				return false;
			} else {
				$this->errorCode = self::ERROR_NONE;
				return true;
			}
		} else {
			$this->errorCode = self::ERROR_USERNAME_INVALID;
			return false;
		}
	}
    
}
?>
