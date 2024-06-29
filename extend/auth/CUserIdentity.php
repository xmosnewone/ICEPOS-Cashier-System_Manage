<?php
namespace auth;
class CUserIdentity
{
	public $id;
	public $username;
	public $password;
	
	const ERROR_NONE=0;
	const ERROR_USERNAME_INVALID=1;
	const ERROR_PASSWORD_INVALID=2;
	const ERROR_UNKNOWN_IDENTITY=100;

	public function __construct($username,$password)
	{
		$this->username=$username;
		$this->password=$password;
	}

	public function getId()
	{
		return $this->id;
	}
	
	public function getName()
	{
		return $this->username;
	}
}
