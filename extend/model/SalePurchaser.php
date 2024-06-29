<?php
//sale_purchaser表
namespace model;
use model\PurchaserIdentity;
use model\BdWholesaleType;
use think\Model;
use think\Db;

class SalePurchaser extends BaseModel {

	protected $pk='pur_no';
	protected $name="sale_purchaser";
	
	const ERROR_NONE=0;
	const ERROR_USERNAME_INVALID=1;
	const ERROR_PASSWORD_INVALID=2;
	const ERROR_UNKNOWN_IDENTITY=100;
	
	public $pur_name;
	public $pur_password;

    public function search() {
    }

    public $_identity;
    public $rememberMe;


    public function Login() {
        if ($this->_identity === null) {
            $this->_identity = new PurchaserIdentity($this->pur_name, $this->pur_password);
        }
        if (!$this->_identity->authenticate()) {
            return false;
        }
        if ($this->_identity->errorCode === $this->ERROR_NONE) {
            $duration = $this->rememberMe ? 3600 * 24 * 30 : 0;
            $purchaser = $this->GetPurchaseByMobile($this->pur_name);
            session("pur_no",$purchaser['pur_no']);
            session("user_role","0");
            //设置登录状态 代码省略
            session("discust",null);
            $model=new BdWholesaleType();
            $discust = $model->GetDetaultDiscust($purchaser['pur_type']);
            session("discust",$discust->discount);
            return true;
        } else {
            return false;
        }
    }

	//检查唯一行
	//$mobile 是手机号， $mark 是检查唯一性返回1，=1是检查是否存在
    public function CheckSingle($mobile, $mark = "0") {
        $result = 0;
        try {
        	$model=Db::table($this->table)->where("pur_name='$mobile'")->find();
            if ($mark == "0") {
                if (empty($model)) {
                    $result = 1;
                } else {
                    $result = 0;
                }
            } else {
                if (empty($model)) {
                    $result = 0;
                } else {
                    $result = 1;
                }
            }
        } catch (\Exception $ex) {
            //"采购商表：采购商唯一性检查（CheckSingle）异常:" . $ex
            $result = -2;
        }
        return $result;
    }


    public function CheckNickname($nickname) {
        $result = 0;
        try {
        	$model=Db::table($this->table)->where("pur_nickname='$nickname'")->find();
            if (empty($model)) {
                $result = 1;
            } else {
                $result = 0;
            }
        } catch (\Exception $ex) {
            //"采购商表：采购商昵称唯一性检查（CheckSingle）异常:" . $ex
            $result = -2;
        }
        return $result;
    }

	/**
	 * 
	 * @param $purchase 完整的sale_purchaser表数据组
	 */
    public function AddPurchase($purchase) {
        $result = 0;
        try {
            $result1 = $this->CheckSingle($purchase["pur_mobile"]);
            if ($result1 === 1) {
                $model = new SalePurchaser();
                $model->data($purchase);
                if ($model->save()) {
                    $result = $purchase['pur_no'];
                }
            } else if ($result1 === 0) {
                $result = -1;
            }
        } catch (\Exception $ex) {
           //"采购商表：创建采购商（AddPurchase）异常:" . $ex
            $result = -2;
        }
        return $result;
    }


    public function UpdatePurchase($purchase) {
        $result = 0;
        try {
            if ($purchase->save()) {
                $result = $purchase->pur_no;
            }
        } catch (\Exception $ex) {
            //"采购商表：更新采购商（AddPurchase）异常:" . $ex
            $result = -2;
        }
        return $result;
    }

	
    public function GetPurchaseByMobile($pur_mobile, $pur_status = "") {
        if (empty($pur_status)) {
        	 return Db::table($this->table)->where("pur_name='$pur_mobile'")->find();
        } else {
        	return Db::table($this->table)->where("pur_name='$pur_mobile' and pur_status='$pur_status'")->find();
        }
    }


    public function GetPurchaseByPurNo($pur_no, $pur_status = "") {
        if (empty($pur_status)) {
        	return Db::table($this->table)->where("pur_no='$pur_no'")->find();
        } else {
        	return Db::table($this->table)->where("pur_no='$pur_no' and pur_status='$pur_status'")->find();
        }
    }

    public $rowIndex;

    public function GetPurchasesForControls($keyword = "", $page = 1, $rows = 10) {

        $condition="1";
        if (!empty($keyword)) {
        	$condition.=" and (pur_no like '%$keyword%' or pur_name like '%$keyword%') ";
        }
        
        $count=Db::table($this->table)
        ->where($condition)
        ->count();
        
        $offset = ($page - 1) * $rows;
        $limit = $rows;
        
        $temp=Db::table($this->table)
        ->field("pur_no,pur_name,pur_realname")
        ->where($condition)
        ->limit($offset,$limit)
        ->select();
        
        $list = array();
        $rowIndex = ($page - 1) * $rows + 1;
        foreach ($temp as $v) {
        	$tt = array();
        	$tt["rowIndex"] = $rowIndex;
        	$tt["pur_no"] = $v["pur_no"];
        	$tt["pur_name"] = $v["pur_name"];
        	$tt["pur_realname"] = $v["pur_realname"];
        	array_push($list, $tt);
        }
        
        $result = array();
        $result["total"] = $count;
        $result["rows"] = $list;
        
        return $result;
        
    }

}
