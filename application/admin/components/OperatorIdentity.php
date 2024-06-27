<?php
namespace app\admin\components;

use auth\CUserIdentity;
use model\SysManager;

class OperatorIdentity	extends CUserIdentity
{
	public $id;
	private $rid;
	public $nickname;
	
	/**
	 * Authenticates a user.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$SysManager=new SysManager();
		$username=strtolower($this->username);
		$user=$SysManager->where("loginname='$username'")->find();
		if($user===null)
		{
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		}
		else if(!$user->validatePassword($this->password))
		{
			$this->errorCode=self::ERROR_PASSWORD_INVALID;
		}
		else
		{       
			$this->id=$user->id;
            $this->rid=$user->rid;
			$this->username=$user->loginname;
			$this->nickname=$user->username;
			$this->errorCode=self::ERROR_NONE;
		}
		return $this->errorCode==self::ERROR_NONE;
	}

	/**
	 * @return integer the ID of the user record
	 */
	public function getloginname()
	{
		return $this->username;
	}
	
	public function getnickname()
	{
		return $this->nickname;
	}
      
	public function getId()
	{
		return $this->id;
	}
	
    public function getrid()
	{
		return $this->rid;
	}
}
