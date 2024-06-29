<?php
//bd_payment_info表
namespace model;
use think\Db;

class PaymentInfo extends BaseModel {

	protected $pk='pay_way';
	protected $name="bd_payment_info";

    public function GetModelsForPos() {
        return $this->select();
    }
    
    //获取记录数量
    public function getCount($condition=[]){
    	return $this->where($condition)->count();
    }

    public function GetTradeModelsForPayflow($mark) {
        if ($mark == "-1") {
            $where="s.pay_flag in (0,2)";
        } else {
            $where="s.pay_flag not in (0,2)";
        }
        
        $list=Db::name($this->name)
        		->alias("s")
        		->field("s.pay_way,s.pay_name")
        		->where($where)
        		->select();
        return $list;
    }
    
    
}
