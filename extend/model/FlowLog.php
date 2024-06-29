<?php
//flow_log表
namespace model;
use think\Db;

class FlowLog extends BaseModel {

	protected $pk='id';
	protected $name="flow_log";
	
	protected $validateRules;
	protected $validateMessage;
	
    //流水号不能为空
    public function rules(){
    	$this->validateRules=array(
    			'sheet_no'=>'require|max:32'
    	);
    	$this->validateMessage=array(
    			'sheet_no.require'=>'流水号不能为空',
    			'sheet_no.max'=>'流水号最大长度32个字符'
    	);
    }
    
    public function search($condition=[]) {

    	$list=Db::table($this->table)
    	->where($condition)
    	->select();
    }

}
