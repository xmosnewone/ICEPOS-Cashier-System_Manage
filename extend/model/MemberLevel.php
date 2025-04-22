<?php 
//member_level表
namespace model;
use think\Db;
class MemberLevel extends BaseModel{

	protected $pk='lid';
	protected $name="member_level";

    public function getLevel($lid){
        return $this->where('lid',$lid)->find();
    }
}





















 ?>