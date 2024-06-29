<?php
//没实体表
namespace model;

class ContactForm
{
	public $name;
	public $email;
	public $subject;
	public $body;
	public $verifyCode;

	public $rules=array(
			'name'  => 'require',
			'email'  => 'require|email',
			'subject'  => 'require',
			'body'  => 'require',
	);

}