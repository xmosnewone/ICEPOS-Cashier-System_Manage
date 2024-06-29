<?php
//news表
namespace model;
use think\Db;

class News extends BaseModel{

	protected $pk='id';
	protected $name="news";

    public function getall($con=''){
      
       $pagesize = 30;
       $order='id desc';
        
       $list=Db::name($this->name)
       ->where($con)
       ->order($order)
       ->paginate($pagesize);
        
       $page=$list->render();
        
       $return['result']=$list;
       $return['pages']=$page;
       return $return;
    }
    public function add($content){
       if ($content->save()) {
                return "OK";
        } 
        else {
                return "ERROR";
        }
    }
}
