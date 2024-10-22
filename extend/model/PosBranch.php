<?php
//pos_branch_info表
namespace model;
use think\Db;
use model\BaseModel;

class PosBranch extends BaseModel {

	protected $pk='branch_no';
	protected $name="pos_branch_info";
	
    public $rowIndex;

    //添加门店功能
    private $branc_functions="
INSERT INTO `dbprefix_pos_function` (`branch_no`, `func_id`, `func_name`, `func_udname`, `pos_key`, `flag`, `type`, `memo`, `other1`) VALUES
('{branch_no}', 'aca', '整单取消', '整单取消', 'e', '1', '2', NULL, NULL),
('{branch_no}', 'ads', '整单折扣', '整单折扣', 'a', '1', '2', NULL, NULL),
('{branch_no}', 'apr', '整单议价', '整单议价', 'l', '1', '2', NULL, NULL),
('{branch_no}', 'can', '删除', '删除', 'u', '1', '1', NULL, NULL),
('{branch_no}', 'cdt', '银行卡', '银行卡', 'k', '1', '2', NULL, NULL),
('{branch_no}', 'csc', '储值卡', '储值卡', '\\\\', '1', '2', NULL, NULL),
('{branch_no}', 'cut', '客户信息', '客户信息', 'n', '1', '1', NULL, NULL),
('{branch_no}', 'dct', '折扣', '折扣', 'o', '1', '1', NULL, NULL),
('{branch_no}', 'dou', '营业员', '营业员', 'l', '1', '1', NULL, NULL),
('{branch_no}', 'fkf', '付款方式', '付款方式', ',', '1', '2', NULL, NULL),
('{branch_no}', 'gdj', '挂单', '挂单', 'p', '1', '1', NULL, NULL),
('{branch_no}', 'giv', '赠送', '赠送', 's', '1', '1', NULL, NULL),
('{branch_no}', 'isc', '增值服务', '增值服务', 'b', '1', '1', NULL, NULL),
('{branch_no}', 'key', '键盘切换', '键盘切换', 'k', '1', '1', NULL, NULL),
('{branch_no}', 'led', '租借业务', '租借业务', 'l', '1', '1', NULL, NULL),
('{branch_no}', 'num', '数量', '数量', 'r', '1', '1', NULL, NULL),
('{branch_no}', 'opd', '开钱箱', '开钱箱', 'x', '1', '1', NULL, NULL),
('{branch_no}', 'oth', '其它', '其它', 'z', '1', '1', NULL, NULL),
('{branch_no}', 'plu', 'PLU', 'PLU', 'Enter', '1', '1', NULL, NULL),
('{branch_no}', 'prc', '单价', '单价', 'w', '1', '1', NULL, NULL),
('{branch_no}', 'ret', '退货', '退货', 'a', '1', '1', NULL, NULL),
('{branch_no}', 'rmb', '人民币', '人民币', '-', '1', '2', NULL, NULL),
('{branch_no}', 'tot', '结算', '结算', '+', '1', '1', NULL, NULL),
('{branch_no}', 'vip', '会员', '会员', 'm', '1', '1', NULL, NULL),
('{branch_no}', 'wsh', '外送', '外送', 't', '1', '1', NULL, NULL),
('{branch_no}', 'barcode', '条码', '条码', 'd', '1', '1', NULL, NULL),                                                                                                                                             
('{branch_no}', 'yezf', '余额支付', '余额支付', '=', '1', '2', NULL, NULL);";
    
    public function getone($branch_no) {
        $one = $this->where("branch_no='$branch_no'")->find();
        if($one!=false){
            $data=$one->toArray();
            $data['use_wechatpay']=$this->verifyWechat($data);
            $data['use_alipay']=$this->verifyAlipay($data);
            return $data;
        }
        return false;
    }

    //判断是否开启微信支付
    //$info 单个店铺数组
    private function verifyWechat($info){
        if($info['use_wechatpay']!=1){
            return 0;
        }
        $checkField=['wechat_appid','wechat_secret','wechat_merchantid','wechat_paykey'];
        $bool=1;
        foreach($checkField as $field){
            if(trim($info[$field])==''){
                $bool=0;
                break;
            }
        }
        return $bool;
    }

    //判断是否开启支付宝支付
    //$info 单个店铺数组
    private function verifyAlipay($info){
        if($info['use_alipay']!=1){
            return 0;
        }
        $checkField=['alipay_appid','alipay_public_key','alipay_private_key'];
        $bool=1;
        foreach($checkField as $field){
            if(trim($info[$field])==''){
                $bool=0;
                break;
            }
        }
        return $bool;
    }

    //自定义查询
    public function getoneWhere($where, $con = '') {
    	$list = $this->where($where)->find();
    	return $list;
    }


    public function GetAllBranch() {
        return $this->select();
    }
    
    public function GetAllBranchField($fields) {
        $res = $this->field($fields)->select();
        
        $result = array();
        $fieldkey=explode(",", $fields);
        foreach ($res as $v) {
        	$tt = array();
        	foreach($fieldkey as $fvalue){
        		$tt[$fvalue] = $v[$fvalue];
        	}
        	array_push($result, $tt);
        }
        return $result;
    }

	//获取全部的分店
    public function GetAllStoreOrShop($branch_params = "",$property="" ) {
      
    	$where="1=1";
        if (!empty($property)) {
            $where=" and property='$property'";
        }
        
        if (getStrlen($branch_params) > 0) {
            $where.=" and (branch_no like '%$branch_params%' or branch_name like '%$branch_params%')";
        }
        $result = array();
        $res = $this->field("branch_no,branch_name")->where($where)->select();
        
        foreach ($res as $v) {
            $tt = array();
            $tt["branch_no"] = $v["branch_no"];
            $tt["branch_name"] = $v["branch_name"];
            array_push($result, $tt);
        }
        return $result;
    }

	//获取分店数据分页
    public function GetAllStoreOrShopForPager($property, $branch_params = "", $page = 1, $rows = 10, $branch_no = "") {
        $where="1=1";
        
        if ($property != "") {
        	$where.=" and property='$property'";
        }
        
        if (getStrlen($branch_params) > 0) {
        	$where.=" and (branch_no like '%$branch_params%' or branch_name like '%$branch_params%')";
        }
        
        if (!empty($branch_no)) {
        	$where.=" and branch_no not in (".simplode($branch_no).") ";
        }
        
        $offset = ($page - 1) * $rows;
        $result = array();
        $count = $this->where($where)->count();
        $res = $this->field("branch_no,branch_name")->where($where)->limit($offset,$rows)->select();
        
        $res1 = array();
        $rowIndex = ($page - 1) * $rows + 1;
        foreach ($res as $v) {
            $tt = array();
            $tt["branch_no"] = $v["branch_no"];
            $tt["branch_name"] = $v["branch_name"];
            $tt["rowIndex"] = $rowIndex;
            $rowIndex++;
            array_push($res1, $tt);
        }
        $result = array();
        $result["total"] = $count;
        $result["rows"] = $res1;
        return $result;
    }
    
    //添加或修改，如果有主键
    public function Add($model) {
    	try {
    		if ($model->save()) {
    			return TRUE;
    		} else {
    			return FALSE;
    		}
    	} catch (\Exception $ex) {
    		return FALSE;
    	}
    }
    
    //删除
    public function deleteOne($branch_no){
    	$delNum=$this->where("branch_no='$branch_no'")->delete();
    	if ($delNum>0) {
    		return true;
    	}else{
    		return false;
    	}
    }
    //向pos_function插入数据
    public function addFunction($branch_no){
    	
    	$sql=str_replace('dbprefix_', $this->prefix, $this->branc_functions);
    	$sql=str_replace('{branch_no}', $branch_no, $sql);
    	
    	//执行新建数据
    	$ok=Db::execute($sql);
    	return $ok;
    }
    
    //删除pos_function数据
    public function delFunction($branch_no){
    	$num=Db::name("pos_function")->where("`branch_no`='$branch_no'")->delete();
    	return $num>0?true:false;
    }

}
