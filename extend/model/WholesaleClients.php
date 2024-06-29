<?php
//bd_wholesale_clients表
namespace model;
use think\Db;

class WholesaleClients extends BaseModel{

	protected $pk='clients_no';
	protected $name="bd_wholesale_clients";
	
    public function getall($username){
    	$where=[];
    	if($username!=''){
    		$where['username']=$username;
    	}
    	
    	$pagesize = 30;
    	$list=$this->where($where)->paginate($pagesize);
    	$page=$list->render();
    	
       $return['result']=$list;
       $return['pages']=$page;
       return $return;
    }
    
    //080
    public function getOne($no){
       return $this->where("clients_no='$no'")->find();
    }
}
