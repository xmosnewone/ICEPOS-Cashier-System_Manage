<?php
//flow_order_detail 表
namespace model;

class OrderDetail extends BaseModel{
    
	protected $name="flow_order_detail";
	
	public function getDetail($order_no){
		return $this->where("order_no = '$order_no'")->select();
	}
}