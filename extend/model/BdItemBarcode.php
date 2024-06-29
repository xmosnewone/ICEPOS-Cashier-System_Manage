<?php
//bd_item_barcode表
namespace model;
use think\Db;

class BdItemBarcode extends BaseModel
{
	protected $pk='item_barcode';
	protected $name="bd_item_barcode";
	
	public $rule = [
			'item_no'        =>'require|max:20',
			'item_barcode'   =>'require|max:20'
	];
	
	public $message = [
			'item_no.require'     	=> '商品编号不能为空',
			'item_no.max'        	=> '商品编号长度不能超过20个字符',
			'item_barcode.require'  => '商品条码不能为空',
			'item_barcode.max'      => '商品条码长度不能超过20个字符',
	];

	//添加商品编码
	public function addBdItemBarcode($data)
	{
		$valiResult=$this->validate($data,$this->rule,$this->message);
		if(!$valiResult['status'])
		{
			return false;
		}
		
		try{
			
			$result=$this->save($data);
			
		}catch(\Exception $e){
				return false;
		}
		
		return true;
	}
	
	//修改商品编码
	public function updateBdItemBarcode($data,$where)
	{
		$valiResult=$this->validate($data,$this->rule,$this->message);
		if(!$valiResult['status'])
		{
			return false;
		}
		
		try{
				
			$result=$this->save($data,$where);
				
		}catch(\Exception $e){
			return false;
		}
		
		return true;
	}
	
	public function search($where)
	{
		return $list=Db::table($this->table)
       		->where($where)
       		->select(); 
	}

}
