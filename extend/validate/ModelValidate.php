<?php
//数据模型基础验证器表
namespace validate;
use think\Validate;

class ModelValidate extends Validate{

	protected $rule = [];
	
	protected $message  = [];
	
    /**
     * 重写构造器
     */
	public function __construct($data=[]){
		
		parent::__construct();
		
		$this->rule=isset($data['rule'])?$data['rule']:[];
		$this->message=isset($data['message'])?$data['message']:[];
		
	}
}

