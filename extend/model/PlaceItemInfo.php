<?php
//bd_place_item_info表
namespace model;
use think\Db;

class PlaceItemInfo extends BaseModel {

	protected $pk=['place_no','item_no','branch_no'];
	protected $name="bd_place_item_info";
	
    public function Add($model) {
        try {
            if ($model->save()) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function Del($placeno, $itemno, $branchno) {
        try {
        	$delNum=$this->where("place_no='$placeno' and item_no='$itemno' and branch_no='$branchno'")->delete();
            if ($delNum > 0) {
                return TRUE;
            } else {
                return FALSE;
            }
        } catch (\Exception $ex) {
            return FALSE;
        }
    }


    public function GetOncePlaceItem($placeno, $branchno, $itemno) {
        $condition=[
        	'branch_no'=>$branchno,
        	'place_no'=>$placeno,
        	'item_no'=>$itemno,
        ];
        return $this->where($condition)->find();
    }

	//获取货架下的陈列商品
    public function GetPlaceItemInfo($branchno, $placeno) {
        $sql = "SELECT p1.place_no,p2.place_name,p1.branch_no,p1.item_no,i.item_size,i.unit_no,"
                . "b.branch_name,p1.min_qty,p1.max_qty,i.item_clsno,i.item_supcust,i.item_name,i.item_subno,i.sale_price,"
                . "i.purchase_spec "
                . "FROM " . $this->table . " AS p1 "
                . "LEFT JOIN " . $this->prefix."bd_place_info" . " AS p2 ON p2.place_no=p1.place_no "
                . "LEFT JOIN " . $this->prefix."pos_branch_info" . " AS b ON b .branch_no=p1.branch_no "
                . "LEFT JOIN " . $this->prefix."bd_item_info" . " AS i ON p1.item_no= i.item_no "
                . "WHERE p1.branch_no='" . $branchno . "'";
        if (!empty($placeno)) {
            $sql .= " AND p1.place_no='" . $placeno . "'";
        }
        
        $data=Db::query($sql);
        return $data;
    }
    
    //获取货位下的商品（分页）
    //$keyword 是关键词搜索
    public function GetPlaceItemInfoPager($branchno, $placeno,$page,$rows,$keyword='') {
    	$sql = "SELECT p1.place_no,p2.place_name,p1.branch_no,p1.item_no,p1.memo,i.item_size,i.unit_no,"
    			. "b.branch_name,p1.min_qty,p1.max_qty,i.item_clsno,i.item_supcust,i.item_name,i.item_subno,i.sale_price,"
    			. "i.purchase_spec "
    			. "FROM " . $this->table . " AS p1 "
    			. "LEFT JOIN " . $this->prefix."bd_place_info" . " AS p2 ON p2.place_no=p1.place_no "
    			. "LEFT JOIN " . $this->prefix."pos_branch_info" . " AS b ON b .branch_no=p1.branch_no "
    			. "LEFT JOIN " . $this->prefix."bd_item_info" . " AS i ON p1.item_no= i.item_no "
    			. "WHERE 1=1 ";
    	
    	if (!empty($branchno)) {
    		$sql .= " AND p1.branch_no='" . $branchno . "'";
    	}
    	
    	if (!empty($placeno)) {
    		$sql .= " AND p1.place_no='" . $placeno . "'";
    	}
    	
    	if (!empty($keyword)) {
    		$sql .= " AND (p2.place_no like '%$keyword%' or p2.place_name like '%$keyword%')";
    	}
    
    	$offset = ($page - 1) * $rows;
    	
    	$sql.=" limit $offset,$rows";
    	$data=Db::query($sql);
    	return $data;
    }

}
