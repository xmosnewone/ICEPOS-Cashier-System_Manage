<?php
namespace app\admin\controller;
//接口调用其他服务器数据
class Connector extends Super {

   //接口服务器地址
   private $http_server;
   //接口访问的token
   private $token;
   //缓存文件夹
   private $cache_path;
   
   //构造函数
   public function __construct(){
   		exit(json_encode(array("code" => 0)));
   		$this->http_server="Interface/";
   		$this->token='';
   		$this->cache_path="./cache/";
   }
   /**
    * 获取json 等数据
    * @$url 是要获取sap数据的脚本路径（已加）
    */
   private function get_data($url,$data=array(),$method='POST'){
   	$data = http_build_query($data);
   	$opts = array (
   			'http' => array (
   					'timeout'=>300,
   					'method' => $method,
   					'header'=> "Content-type: application/x-www-form-urlencoded\r\n" .
   					"Content-Length: " .(strlen($data)>0?strlen($data):0) . "\r\n",
   					'content' => $data
   			)
   	);
   	$ctx = stream_context_create($opts);
   	$return = @file_get_contents($url,'',$ctx);
   	return $return;
   }
   
   /**
    * 处理返回的JSON数据
    * $json的数组格式:array('status'=>$status,'msg'=>$msg,"data"=>$data,"rows"=>$data_row)
    */
   private function decode_data($json){
   	
   		$data=json_decode($json,true);//解释为数组
   		return 	$data;
   }
   
   //合并并推送到接口，并返回数据
   //$interface_name 是要调用的接口名称
   //$data 是要提交到接口的数据
   private function combind_function($interface_name,$data){
   	
   		//拼接地址
   		$url=$this->http_server.$interface_name."/token/".$this->token;
   	
	   	$json=$this->get_data($url,$data);
	   	 
	   	//处理获取得到的产品数据
	   	$array=$this->decode_data($json);
	   	
	   	return $array;
   }

}
