<?php
/**
 * bd_basecode_type表
 */
namespace model;
use think\Db;

class BaseCodeType extends BaseModel {

	protected $pk='type_no';
	protected $name="bd_basecode_type";
	
	
	public function getOne($condition=[]) {
		return $this->where($condition)->find();
	}
	
    public function getall() {
        return $this->select();
    }

    public function ishave($type_no) {
        return $this->where("type_no='$type_no'")->count();
    }

    public $rtype;
    public $rid;
    public $updatetime;

    public function GetUpdateDataForPos($rid = "", $updatetime = "") {
     
        if ($rid == "-1") {
        	
        	$result = Db::table($this->table)
        				->alias('s')
	        			->field("0 as rid,'I' as rtype,now() as updatetime,s.type_no,s.type_name")
	        			->select();
        }else{
        	
        	if (empty($rid)) {
        		$rid = 0;
        	}
        	
        	$where="a.rid > $rid";
        	
        	if (!empty($updatetime)) {
        		$where.=" and a.updatetime>$updatetime";
        	}
        	
        	$result = Db::table($this->table)
        	->alias('s')
        	->field("a.rid,a.rtype,a.updatetime,s.type_no,s.type_name")
        	->join('bd_basecode_type_breakpoint a','s.type_no=a.type_no',"RIGHT")
        	->where($where)
        	->select();
        }
        
  
        
        
        $list = array();
        if ($rid == "-1") {
        	$typeBreak=new BdBasecodeTypeBreakpoint;
            $r_id = $typeBreak->GetMaxRidForUpdate();
        }
        foreach ($result as $v) {
            $tt = array();
           	$tt["rid"] = ($rid == "-1") ? $r_id : $v["rid"];
            $tt["rtype"] = $v["rtype"];
            $tt["updatetime"] = $v["updatetime"];
            $tt["type_no"] = $v["type_no"];
            $tt["type_name"] = $v["type_name"];
            array_push($list, $tt);
        }
        return $list;
    }

}
