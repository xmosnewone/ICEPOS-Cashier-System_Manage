<?php
//pos_plan_rule表
namespace model;
use think\Db;

class PosPlanRule extends BaseModel {

	protected $pk='rule_no';
	protected $name="pos_plan_rule";
	
    public function GetRuleByRuleNoAndRangeFlag($rule_no, $range_flag) {
        return $this->where("rule_no='$rule_no' and range_flag='$range_flag'")->find();
    }

    public function GetModelsForPos() {
        return $this->select();
    }

}
