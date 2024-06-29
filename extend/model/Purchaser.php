<?php
//purchaser表
namespace model;
use model\PurchaserIdentity;
use model\SalePurchaser;
use think\Model;
use think\Db;

class Purchaser extends BaseModel {
	
	protected $pk='pur_no';
	protected $name="purchaser";
	
    public $rememberMe;
    public $captcha;
    public $repass;
    public $vertifyCode;
    private $_identity;
    
    public $username;
    public $password;
    
    protected $validateRules;
    protected $validateMessage;

    const ERROR_NONE=0;
    const ERROR_USERNAME_INVALID=1;
    const ERROR_PASSWORD_INVALID=2;
    const ERROR_UNKNOWN_IDENTITY=100;
    
    //流水号不能为空
    public function rules(){
    	$this->validateRules=array(
    			'username'=>'require|max:32'
    	);
    	$this->validateMessage=array(
    			'username.require'=>'用户名不能为空',
    			'password.require'=>'密码不能为空'
    	);
    }

    public function authenticate($attribute, $params) {
            $this->_identity = new PurchaserIdentity($this->username, $this->password);
            if (!$this->_identity->authenticate())
            return false;
    }


    public function login() {
        if ($this->_identity === null) {
            $this->_identity = new PurchaserIdentity($this->username, $this->password);
        }

        if (!$this->_identity->authenticate()) {
            return false;
        }
        if ($this->_identity->errorCode === $this->ERROR_NONE) {
            $duration = $this->rememberMe ? 3600 * 24 * 30 : 0;
            $purchaser = Db::table($this->table)->where("username='{$this->username}'")->find();
            session("pur_no",$purchaser['pur_no']);
           	 //设置登录状态 代码省略
            return true;
        } else {
            return false;
        }
    }

    public function GetPurchaserToPurno($purno) {
        return Db::table($this->table)
        ->field("mobile,email,username,password,activation_code,status")
        ->where("pur_no='$purno'")
        ->find();
    }


    public function GetPurchaserToUsername($username) {
        return Db::table($this->table)
        ->field("pur_no,mobile,email,username,password,status,activation_code")
        ->where("username='$username'")
        ->find();
    }
    
    public function getall($username){
    	
    	$where=[];
    	if($username!=''){
    		$where['username']=$username;
    	}
    	$pagesize = 30;
    	$list=$this->where($where)->paginate($pagesize);
    	$page=$list->render();
    		
    	$return['result']=$list;
    	$return['pages']=$page;
    	return $return;
    }
    
    public function getone($no){
    	return $this->where("pur_no = '$no'")->find();
    }

}
