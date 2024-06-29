<?php
//没实体表
namespace model;
use model\User;

class LoginForm{

    public $username;
    public $password;
    public $rememberMe;
    public $vertifyCode;
    private $_identity;


    public $rule = [
    		'username'        =>'require',
    		'password'   =>'require'
    ];
    public $message = [
    		'username.require'     	=> '用户名不能为空',
    		'password.require'        	=> '密码不能为空',
    ];

    public function authenticate() {
        
            $user= new User($this->username, $this->password);
            if (!$user->authenticate()){
            	return array('password'=>'用户名或密码错误','status'=>'0');
            }else{
            	return array('status'=>'1');
            }
     }


    public function login() {
        if ($this->_identity === null) {
        	$user= new User($this->username, $this->password);
            $user->authenticate();
        }
        //其他例如session、cookie登记记录在别的controler执行
        if ($user->errorCode === User::ERROR_NONE) {
            return true;
        } else
            return false;
    }
    
}
