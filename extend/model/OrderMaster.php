<?php
//flow_order_master 表
namespace model;

class OrderMaster extends BaseModel{
	
    public $statusname;
    
    protected $pk='order_no';
    protected $name="flow_order_master";
   
}

