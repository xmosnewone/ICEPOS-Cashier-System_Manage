<?php
namespace model;
use think\Model;
use model\SalePurchaser;
use think\Db;

class PurchaserIdentity  extends Model{

    const ERROR_NONE=0;
    const ERROR_USERNAME_INVALID=1;
    const ERROR_PASSWORD_INVALID=2;
    const ERROR_UNKNOWN_IDENTITY=100;

    /**
     * 用户名
     */
    public $username;
    /**
     * 密码
     */
    public $password;
    /**
     * 错误码
     */
    public $errorCode;

    /**
     * 构造函数
     */
    public function __construct($username,$password)
    {
        parent::__construct([]);
        $this->username=$username;
        $this->password=$password;
    }

    public function authenticate() {

        $encript_key=config("encrypt");//获取配置的密码加密秘钥
        $SalePurchaser=new SalePurchaser();
        $user =	$SalePurchaser->GetPurchaseByMobile($this->username, "1");
        if ($user) {
            if ($user['pur_password'] != md5(md5($this->password . $encript_key['ENCRYPT_FOR_PASSWORD']))) {
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