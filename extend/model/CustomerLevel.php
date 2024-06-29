<?php
/**
 * customer_level表
 */
namespace model;
use think\Db;

class CustomerLevel extends BaseModel{

	protected $pk='id';
	protected $name="customer_level";
	
    public function getAllList(){
        return $this->select();
    }
}
