<?php
namespace util;
use think\Db;
/**
 +------------------------------------------------------------------------------
 * SAP接口类
 * $sapHtppServer=array(
 * 		'http_url'=>'http://192.168.0.18/sap.php'	
 * )
 +------------------------------------------------------------------------------
 */
class Sap {
	
protected $sapHtppServer;								//可以外部修改的SAP服务器的配置信息
		
	const SAPCONNECT_SUCCESS	=1;						//SAP HTTP服务器连接成功
	const SAPCONNECT_FAIL		=0;						//SAP HTTP服务器连接不成功
	
	const SAP_PRODUCTS			='sap_products';		//产品接口
	const SAP_CLIENTS			='sap_clients';			//客户接口
	const SAP_SALES_CHANNEL		='sap_sales_channel';	//销售渠道
	const SAP_PRICE				='sap_price';			//商品价格接口
	const SAP_STOCK				='sap_stock';			//可卖库存查询
	const SAP_ORDER				='sap_order';			//下单接口
	
	
	public function __construct($config){
		
		$this->sapHtppServer=$config;
		
	}
	
	/**
	 * 检测SAP HTTP服务器是否可以可以连通和使用
	 */
	public function pingHttp(){
		
		$headers=get_headers($this->sapHtppServer['http_url']);
		$status=substr($headers[0], 9, 3);
		if($status=='200'){
			return SAPCONNECT_SUCCESS;
		}else{
			return SAPCONNECT_FAIL;
		}
	}
   
	/**
	 * 同步SAP的产品数据，产品数据是整个公司的产品，接口下发的是整个商品表
	 */
	public function sap_products(){
		
		$url=$this->sapHtppServer['http_url']."?action=".SAP_PRODUCTS;
		
		$data=file_get_contents($url);
		
		return $data;
	}
	
	/**
	 * 同步SAP客户信息接口
	 */
	public function sap_clients(){
		
		$url=$this->sapHtppServer['http_url']."?action=".SAP_CLIENTS;
		
		$data=file_get_contents($url);
		
		return $data;
	}
	
	/**
	 * 销售渠道接口
	 */
	public function sap_sales_channel(){
		
		$url=$this->sapHtppServer['http_url']."?action=".SAP_SALES_CHANNEL;
		
		$data=file_get_contents($url);
		
		return $data;
	}
	
	/**
	 * 产品价格同步
	 */
	public function sap_price(){
		
		$url=$this->sapHtppServer['http_url']."?action=".SAP_PRICE;
		
		$data=file_get_contents($url);
		
		return $data;		
	}
	
	/**
	 * 可卖库存查询
	 */
	public function sap_stock(){
		
		$url=$this->sapHtppServer['http_url']."?action=".SAP_STOCK;
		
		$data=file_get_contents($url);
		
		return $data;
	}
	
	/**
	 * 销售订单创建接口
	 */
	public function sap_order(){
		
		$url=$this->sapHtppServer['http_url']."?action=".SAP_ORDER;
		
		$data=file_get_contents($url);
		
		return $data;
		
	}
}