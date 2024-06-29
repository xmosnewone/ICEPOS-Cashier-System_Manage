<?php
//sp_products表
namespace model;
use think\Db;

class SpProducts extends BaseModel{

	protected $pk='pro_id';
	protected $name="sp_products";
	
    public function getall($content){
    	
       $where=[];
       foreach($content as $k=>$v){
       	if($v!=''){
       		$where[$k]=trim($v);
       	}
       }
       
       $pagesize = 30;
       $list=$this->where($where)->order("pro_id desc")->paginate($pagesize);
       $page=$list->render();
       
       $return['result']=$list;
       $return['pages']=$page;
       return $return;
    }
}
