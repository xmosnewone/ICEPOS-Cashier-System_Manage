<?php
//Capital-账户表
namespace model;
use think\Model;
class Capital extends BaseModel {
	
	protected $pk='id';
	protected $name="account";

	public function str_no($str='') {
		return $str.date("YmdHis").rand(0,9);
	}
	
	public function getall($con=[]){
		$list=$this->where($con)->order("id desc")->select();
		return $list;
	}
	
}
