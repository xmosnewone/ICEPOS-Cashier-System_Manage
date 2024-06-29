<?php
namespace util;

use auth\CUserIdentity;
use model\SalePurchaser;

class PurchaserIdentity	extends CUserIdentity{

	public $username;

    public function authenticate() {
    	$SalePurchaser=new SalePurchaser();
        $user = $SalePurchaser->GetPurchaseByMobile($this->username, "1");
        if ($user != NULL) {
            if ($user->pur_password != md5(md5($this->password . ENCRYPT_FOR_PASSWORD))) {
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
