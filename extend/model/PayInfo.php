<?php
//bu_pay_info表
namespace model;
use think\Db;

class PayInfo extends BaseModel {

    public $order_type;
    public $order_receive;
    public $order_income;
    public $order_expend;
    
    protected $pk='pay_no';
    protected $name="bu_pay_info";

    public $rule = [
    		'ordername'  =>'max:30',
    		'wayorder'  =>'max:30',
    		'typename'  =>'max:30',
    		'money'  =>'max:16',
    		'type'  =>'max:10',
    		'agentid'  =>'max:10',
    		'agentname'  =>'max:50',
    		'desc'  =>'max:100',
    		
    ];
    public $message = [
    		'ordername.max' => '订单编号长度不能超过30个字符',
    		'wayorder.max'  => '助记单号长度不能超过30个字符',
    		'typename.max'  => '支付方式长度不能超过20个字符',
    		'type.max'  => '支付方式简称不能超过10个字符',
    		'desc.max'  => '订单描述不能超过100个字符',
    		'agentid.max'  => '代理id不能超过10个字符',
    		'agentname.max'  => '代理者名称不能超过100个字符',
    ];
    
   
    public function search() {
    	
    }

    public function GetOrderInfos($start, $end, $order_no, $order_type, $pur_no) {
       
    	$where="pay.state = '1' ";
    	$orderby = "pay.overtime desc";
    	
    	if (!empty($start)) {
    		$where.=" and  ord.overtime >= $start ";
    	}
    	if (!empty($end)) {
    		$where.=" and ord.overtime < $end ";
    	}
    	if (!empty($order_no)) {
    		$where.=" and ord.order_no='$order_no' ";
    	}
    	if (!empty($order_type)) {
    		$where.=" and ord.order_type in ($order_type) ";
    	}
    	
    	if (!empty($pur_no)) {
    		$where.=" and ord.owner='$pur_no' ";
    	}
    	
    	$list=Db::table($this->table)
    	->alias('pay')
    	->field("pay.overtime ,pay.ordername ,pay.wayorder ,
				case ord.order_type when '1' then '充值' when '0' then '在线支付' else '' END  as order_type,
				pay.`desc` ,case ord.order_type when '1' then '系统' else '' end  as order_receive,
				case ord.order_type when '1' then pay.money else '' end as order_income,
				case ord.order_type when '0' then pay.money else '' end  as order_expend,
				pay.account ,
				pay.typename ")
    			->join('flow_order_master ord','pay.ordername=ord.order_no',"LEFT")
    			->order($orderby)
    			->where($where)
    			->select();
    	return $list;
    	
    }

}
