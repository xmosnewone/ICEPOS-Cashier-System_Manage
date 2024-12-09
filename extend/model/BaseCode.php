<?php
//bd_base_code表
namespace model;
use think\Db;
use model\BdBaseCodeBreakpoint;

class BaseCode extends BaseModel
{

	protected $pk='code_id';
	protected $name="bd_base_code";
	
    public function GetBaseCode($typeNo=""){
       $list=Db::name($this->name)->field("code_id, code_name, type_no,memo")
       		->where("type_no='$typeNo' and display_flag='1'")
       		->select();
       
       return $list;
    }
    
    public function getall($type_no = '', $code_name = '') {
    	
    	$condition="1=1";
    	if ($type_no != '') {
    		$condition.=" and type_no='$type_no'";
    	}
    	if ($code_name != '') {
    		$condition.=" and code_name like '%$code_name%'";
    	}
    	
    	$pagesize = 30;
    	$list=Db::name($this->name)->where($condition)->paginate($pagesize);
    	$page=$list->render();
    	
    	$return['result'] =$list;
    	$return['pages'] = $page;
    	return $return;
    }
    
    public function ishave($type_no, $code_id) {
    	return $this->where("type_no='$type_no' and code_id='$code_id'")->count();
    }
    
    public function del($type_no, $code_id) {
    	return $this->where("type_no='$type_no' and code_id='$code_id'")->delete();
    }

    public function GetItemClassForBrand($clsno){
        $list=Db::name($this->name)
        ->alias('c')
        ->join('bd_item_info i','c.code_id=i.item_brand',"RIGHT")
        ->where("c.type_no='PP' and i.item_clsno like '$clsno%' ")
        ->group("i.item_brand")
        ->select();
        
      	return $list;
    }

    public function GetItemBrand($itemBrand = ""){
            $ABCD = array("A","B","C","D");
            $EFG = array("E","F","G");
            $HIJK = array("H","I","J","K");
            $LMN = array("L","M","N");
            $OPQ = array("O","P","Q");
            $RST = array("R","S","T");
            $UVW = array("U","V","W");
            $XYZ = array("X","Y","Z");

            $brand = array();
            foreach ($itemBrand as $key => $value) {
                $brand[] = $itemBrand[$key]["attributes"];
            }
            $designer ['ABCD'] = '';
            $designer ['EFG'] = '';
            $designer ['HIJK'] = '';
            $designer ['LMN'] = '';
            $designer ['OPQ'] = '';
            $designer ['RST'] = '';
            $designer ['UVW'] = '';
            $designer ['XYZ'] = '';
            foreach ( $brand as $name ) {
                $firstn = strtoupper (substr ($name["code_id"], 0, 1 ) );
                if (in_array ( $firstn, $ABCD )) {
                    $designer ['ABCD'][] = array('codeName'=>$name["code_name"],'codeNo'=>$name["code_id"]);
                } elseif (in_array ( $firstn, $EFG )) {
                    $designer ['EFG'][] =array('codeName'=>$name["code_name"],'codeNo'=>$name["code_id"]);
                } elseif (in_array ( $firstn, $HIJK )) {
                    $designer ['HIJK'][] = array('codeName'=>$name["code_name"],'codeNo'=>$name["code_id"]);
                } elseif (in_array ( $firstn, $LMN )) {
                    $designer ['LMN'][] = array('codeName'=>$name["code_name"],'codeNo'=>$name["code_id"]);
                }elseif (in_array ( $firstn, $OPQ )) {
                    $designer ['OPQ'][] = array('codeName'=>$name["code_name"],'codeNo'=>$name["code_id"]);
                }elseif (in_array ( $firstn, $RST )) {
                    $designer ['RST'][] = array('codeName'=>$name["code_name"],'codeNo'=>$name["code_id"]);
                } elseif (in_array ( $firstn, $UVW )) {
                    $designer ['UVW'][] = array('codeName'=>$name["code_name"],'codeNo'=>$name["code_id"]);
                }elseif (in_array ( $firstn, $XYZ )) {
                    $designer ['XYZ'][] = array('codeName'=>$name["code_name"],'codeNo'=>$name["code_id"]);
                }else{
                    $designer ['其它'][] =array('codeName'=>$name["code_name"],'codeNo'=>$name["code_id"]);
                }
            }
            return  array_filter($designer);
    }
    
    public function get($type_no) {
    	return $this->where("type_no='$type_no'")->select();
    }
    
    
    public function GetByTypeAndCode($type_no, $code_id) {
    	return $this->where("type_no='$type_no' and code_id='$code_id'")->find();
    }
    
    
    public function GetAllModelsForPos() {
    	return $this->select();
    }
    
    public $rowIndex;
    
    
    public function GetAllModelsForControls($type_no = "", $keyword = "", $page = 1, $rows = 10) {

    	$where="1=1";
    	if (!empty($type_no)) {
    		$where.=" and type_no='$type_no'";
    	}
    	if (!empty($keyword)) {
    		$where.=" and code_id like '%$keyword%' or code_name like '%$keyword%'";
    	}

    	$offset = ($page - 1) * $rows;
    	
    	$temp = $this
		    	->alias("s")
		    	->field("code_id,code_name")
		    	->where($where)
		    	->limit($offset,$rows)
		    	->select();
    	$count=$this
		    	->alias("s")
		    	->where($where)
		    	->count();
    	
    	$list = array();
    	$rowIndex = ($page - 1) * $rows + 1;
    	foreach ($temp as $v) {
    		$tt = array();
    		$tt["rowIndex"] = $rowIndex;
    		$tt["code_id"] = $v["code_id"];
    		$tt["code_name"] = $v["code_name"];
    		array_push($list, $tt);
    	}
    	$result = array();
    	$result["total"] = $count;
    	$result["rows"] = $list;
    	return $result;
    }
    
    
    public function GetAllModelsByCodeIds($type_no, $arr_code_ids) {
    	
    	$where="1=1";
    	if (!empty($type_no)) {
    		$where.=" and type_no='$type_no'";
    	}
    	if (!empty($arr_code_ids)) {
    		$where.=" and code_id in (".simplode($arr_code_ids).") ";
    	}
    	
    	return $this->field("code_name")->where($where)->select();
    }
    
    public $rtype;
    public $rid;
    public $updatetime;
    
    public function GetUpdateDataForPos($rid = "", $updatetime = "",$where="") {

    	if ($rid == "-1") {
    	    //
    		$result=Db::name($this->name)
    				->alias('s')
    				->field("0 as rid,'I' as rtype,now() as updatetime," .
    				"s.type_no,s.code_id,s.code_name,s.english_name,s.display_flag,s.code_type,s.memo")
    				->select();
    	
    	} else {
    		
    		$where="1=1";
    		if (empty($rid)) {
    			$rid = 0;
    		}
    		$where.=" and a.rid > '$rid'";
    		if (!empty($updatetime)) {
    			$where.=" and  a.updatetime > '$updatetime'";
    		}
    		
    		$result=Db::name($this->name)
    				->alias('s')
    				->field("a.rid,a.rtype,a.updatetime,a.type_no,a.code_id,s.code_name,s.english_name,s.display_flag,s.code_type,s.memo")
    				->join('bd_base_code_breakpoint a','s.type_no=a.type_no and s.code_id=a.code_id',"RIGHT")
    				->where($where)
    				->select();
    		
    	}
    	
    	$list = array();
    	if ($rid == "-1") {
    		$BdBaseCodeBreakpoint=new BdBaseCodeBreakpoint();
    		$r_id = $BdBaseCodeBreakpoint->GetMaxRidForUpdate();
    	}
    	foreach ($result as $v) {
    		$tt = array();
    		$tt["rid"] = ($rid == "-1") ? $r_id : $v["rid"];
    		$tt["rtype"] = $v["rtype"];
    		$tt["updatetime"] = $v["updatetime"];
    		$tt["type_no"] = $v["type_no"];
    		$tt["code_id"] = $v["code_id"];
    		$tt["code_name"] = $v["code_name"];
    		$tt["english_name"] = $v["english_name"];
    		$tt["display_flag"] = $v["display_flag"];
    		$tt["code_type"] = $v["code_type"];
    		$tt["memo"] = $v["memo"];
    		array_push($list, $tt);
    	}
    	return $list;
    }
    
    
}