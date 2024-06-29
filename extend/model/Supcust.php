<?php
//sp_infos表
namespace model;
use think\Db;

/**
 * 供应商表
 */
class Supcust extends BaseModel {

    public $rowIndex;
    protected $pk='sp_id';
    protected $name="sp_infos";

    public function getall($content) {

        $condition=[];
        foreach ($content as $k => $v) {
            if ($v != '') {
            	$condition[$k]=$v;
            }
        }
        
        $pagesize = 30;
        
        $temp=Db::name($this->name)->where($condition)->paginate($pagesize);
        $page=$temp->render();
        
        $return['result'] = $temp;
        $return['pages'] = $page;
        return $return;
    }

    public function get() {
        return $this->select();
    }

    public function getFieldAll($field) {
    	$list = $this->field($field)->select();
    	$temp=array();
    	$fieldArray=explode(",",$field);
    	foreach ($list as $k => $v) {
    		$tt = array();
    		foreach($fieldArray as $one){
    			$tt[$one]=$v[$one];
    		}
    		array_push($temp, $tt);
    	}
    
    	return $temp;
    }
    
    public function getbyid($sp_id) {
    	return $this->where("sp_id='$sp_id'")->find();
    }
    
    
    public function getbyno($no) {
        return $this->where("sp_no='$no'")->find();
    }


    public function GetSupcustPager($rows, $page, $keyword) {
 
        $where="1=1";
        if (!empty($keyword)) {
        	$where.=" and sp_no like '%$keyword%' or sp_company like '%$keyword%'";
        }
        
        $offset = ($page - 1) * $rows;
        
        $rowCount=$this->where($where)->count();
        $list = $this
        		->alias("s")
        		->field("s.sp_no,sp_name,s.sp_company")
        		->where($where)
        		->limit($offset,$rows)
        		->select();
        
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $rowIndex = ($page - 1) * $rows + 1;
        foreach ($list as $k => $v) {
            $tt = array();
            $tt["sp_no"] = $v["sp_no"];
            $tt["sp_name"] = $v["sp_name"];
            $tt["sp_company"] = $v["sp_company"];
            $tt["rowIndex"] = $rowIndex;
            $rowIndex++;
            array_push($temp, $tt);
        }

        $result["rows"] = $temp;
        return $result;
    }

    public function ishave($sp_no) {
        return $this->where("sp_no='$sp_no'")->count();
    }


    public function GetModelsForPos() {
        $list = $this->alias("s")->field("s.sp_no,s.sp_company")->select();
        $result = array();
        foreach ($list as $k => $v) {
            $tt = array();
            $tt["sp_no"] = $v["sp_no"];
            $tt["sp_company"] = $v["sp_company"];
            array_push($result, $tt);
        }
        return $result;
    }
    
    //添加或修改，如果有主键
    public function Add($model) {
    	try {
    		if ($model->save()) {
    			return TRUE;
    		} else {
    			return FALSE;
    		}
    	} catch (\Exception $ex) {
    		return FALSE;
    	}
    }

}
