<?php
//purchaser_address表
namespace model;
use think\Model;
use think\Db;

class PurchaserAddress extends BaseModel {

	protected $pk='address_no';
	protected $name="purchaser_address";

    public function search() {
    		
    }

    public function findAllData($pur_no) {
        $address = Db::table($this->table)->where("pur_no='$pur_no'")
        			->order("isdefault desc")
        			->select();
       	if($address===false||count($address)==0){
       		return array();
       	}
        $arrget = array();
        if (count($address) > 0) {
        	foreach($address as $key=>$value)
        	{
                $arrget1 =$value;
                $arrget1['provname'] = $value['provice'];
                $arrget1['cityname'] = $value['city'];
                $arrget1['areaname'] = $value['area'];
                $arrget1['telephone'] = $arrget1['telephone'];
                array_push($arrget, $arrget1);
        	}
        };
        return $arrget;
    }

    public function findAddressByNo($addrno) {
    	
    	$address =  Db::table($this->table)->where("address_no='$addrno'")
        			->find();
    	if (count($address) > 0) {
    		$username = $address["username"];
    		$mobile = $address["mobile"];
    		$provice = $address["provice"];
    		$city = $address["city"];
    		$area = $address["area"];
    
    		$range = $arr['provname'] . ' ' . $arr['cityname'] . ' ' . (isset($arr['areaname'])?$arr['areaname']:'');
    		$addr = $address["address"];
    		$zipcode = $address["zipcode"];
    		return $username . ', ' . $mobile . ', ' . $range . ', ' . $addr . ', ' . $zipcode;
    	}
    }
    
    //$address 是一个model对象
    public function addOrSaveAddress($address, $pur_no) {
    	
    	$model=new PurchaserAddress();
    	
    	$vertify = $this->checkData($address);
    	if ($vertify != "ok") {
    		return $vertify;
    	}
    	$address_no = $address->address_no;
    	$isadd = TRUE;
    	if ($address_no != 0) {
    		$isadd = FALSE;
    		
    		$address_current=$model->where('address_no',$address_no)->find();
    		
    		if (!empty($address_current)) {
    			if ($address_current->pur_no != $pur_no) {
    				return "FORBIDDED";
    			}
    		} else {
    			return "INVALID";
    		}
    	}

    	$address_models = $model->where("pur_no='$pur_no'")
    	->order("isdefault desc")
    	->select();
    	$a_models=$address_models->toArray();
    	
    	if ($isadd == TRUE) {
    		if (!empty($a_models) && count($a_models) >= 5) {
    			return "LIMIT";
    		}
    	}
    	$default_address = NULL;
    	foreach ($address_models as $v) {
    	 	if ($v->isdefault == "1" && $v->address_no != $address_no) {
                $default_address = $v;
                break;
            }
    	}
    	
    	Db::startTrans();
    	$isok = TRUE;
    	try {
    		if (!empty($default_address)) {
    			$default_address->isdefault = "0";
    			if ($default_address->save() == FALSE) {
    				$isok = FALSE;
    			}
    		}
    		if ($isok) {
    			if ($isadd == TRUE) {
    				$address->save();
    				$recordId =$address->address_no;
    				 Db::commit();
    				return $recordId;
    			} else {
    				$address->isUpdate(true)->save();
    				 Db::commit();
    				return $address->address_no;
    			}
    		} else {
    			Db::rollback();
    			return "ERROR";
    		}
    	} catch (\Exception $ex) {
    		Db::rollback();
    		return "ERROR";
    	}
    }
    
    
    private function checkData($address) {
    	$zipcode_arr = array();
    	$mobile_arr = array();
    	$telephone_arr = array();
    	if (mb_strlen($address->username, "utf8") == 0 || mb_strlen($address->username, "utf8") > 30) {
    		return "ERROR";
    	} else if (empty($address->provice)) {
    		return "ERROR";
    	} else if (empty($address->city)) {
    		return "ERROR";
    	} else if (empty($address->area)) {
    		return "ERROR";
    	} else if (mb_strlen($address->address, "utf8") <= 0 || mb_strlen($address->address, "utf8") > 200) {
    		return "ERROR";
    	} else if (preg_match("/^\d{6}$/", $address->zipcode, $zipcode_arr) <= 0) {
    		return "ERROR";
    	} else if (empty($address->mobile) && empty($address->telephone)) {
    		return "ERROR";
    	} else if (!empty($address->mobile) && preg_match("/^1[3|4|5|7|8][0-9]{9}$/", $address->mobile, $mobile_arr) <= 0) {
    		return "ERROR";
    	} else if (!empty($address->telephone) && preg_match("/^((0\d{2,3})-)?(\d{7,8})(-(\d{1,4}))?$/", $address->telephone, $telephone_arr) <= 0) {
    		return "ERROR";
    	}
    	return "ok";
    }


}
