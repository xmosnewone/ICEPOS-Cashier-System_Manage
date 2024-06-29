<?php
namespace app\admin\components\Enumerable;
/*
 * 全局用户登录后所有Session键
 */
class ESession{
	
    public	$SessionKey=[
    			
    			'rid',//用户组id
    			'uid',//用户id
    			'loginname',//登录账户
    			'nickname',//用户名称
    			
    		];
    
   //约束性设置session
   public function setSession($data){
   		$SK=$this->SessionKey;	
   		foreach($data as $key=>$value){
   			if(in_array($key, $SK)){
   				session ( $key, $value);
   			}
   		}
   }
   
   //约束性返回session
   public function getSession($keys=''){
   		$SK=$this->SessionKey;
   		if(is_array($keys)){
   			$return=[];
	   		foreach($keys as $key){
	   			if(in_array($key, $SK)){
	   				$return[$key]=session ($key);
	   			}
	   		}
	   		
   		}else if(is_string($keys)){
   			return	session ($keys);
   		}else if(empty($keys)||!isset($keys)){
   			$return=[];
   			foreach($SK as $key){
   				$return[$key]=session ($key);
   			}
   			return $return;
   		}
   }  
}
