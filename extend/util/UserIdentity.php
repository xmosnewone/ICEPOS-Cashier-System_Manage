<?php
namespace util;

use auth\CUserIdentity;

class UserIdentity	extends CUserIdentity{

    public $username;
    
    public function authenticate() {
        $user = M("sys_operator")->where("oper_id='{$this->username}'")->find();
        if ($user != NULL) {
            if ($user->oper_pw != $this->password) {
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
