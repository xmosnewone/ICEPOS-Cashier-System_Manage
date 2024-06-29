<?php
/**
 * bd_item_combsplit表
 */
namespace model;
use think\Db;
use app\admin\components\Enumerable\EOperStatus;

class BdItemCombsplit extends BaseModel {

	protected $name="bd_item_combsplit";
	protected $pk=['comb_item_no','item_no'];
    
    public function AddModel($combe_no, $models) {
        $result = $this->CheckModel($models);
        if ($result == 1) {
            Db::startTrans();
            try {
                $temp_models=$this->where("comb_item_no='$combe_no'")->select();
                foreach ($temp_models as $v) {
                    $bdBreakPoint = new BdItemCombsplitBreakpoint();
                    $bdBreakPoint->rtype = EOperStatus::DELETE;
                    $bdBreakPoint->item_no = $v["item_no"];
                    $bdBreakPoint->comb_item_no = $v["comb_item_no"];
                    $bdBreakPoint->updatetime = date(DATETIME_FORMAT);
                    $bdBreakPoint->save();
                }
                if (!empty($temp_models)) {
                    if ($this->DeleteModel($temp_models) == FALSE) {
                        $result = 0;
                    }
                }
                if ($result == 1) {
                    foreach ($models as $model) {
                        if ($model->save() == FALSE) {
                            $result = 0;
                            break;
                        }
                    }
                }
                foreach ($models as $k => $com) {
                    $bdBreakPoint = new BdItemCombsplitBreakpoint();
                    $bdBreakPoint->rtype = EOperStatus::ADD;
                    $bdBreakPoint->item_no = $com["item_no"];
                    $bdBreakPoint->comb_item_no = $com["comb_item_no"];
                    $bdBreakPoint->updatetime = date(DATETIME_FORMAT);
                    $bdBreakPoint->save();
                }
                if ($result == 1) {
                    Db::commit();
                } else {
                     Db::rollback();
                }
            } catch (\Exception $ex) {
                 Db::rollback();
                $result = -2;
            }
        }
        return $result;
    }


    public function GetModel($comb_item_no, $item_no='') {
        if (!empty($comb_item_no)) {
        	return $this->where("comb_item_no='$comb_item_no'")->find();
            //return $this->where("comb_item_no='$comb_item_no' and item_no='$item_no'")->find();
        }
        return NULL;
    }


    public function DeleteModel($models) {
        $result = 1;
        try {
            foreach ($models as $k => $model) {
                if ($model->delete() == FALSE) {
                    $result = 0;
                    break;
                }
            }
        } catch (\Exception $ex) {
            $result = -2;
        }
        return $result;
    }


    private function CheckModel($models) {
        $res = 1;
        try {
            if (empty($models)) {
                $res = -1;
            } else {
                foreach ($models as $model) {
                    if (empty($model->comb_item_no)) {
                        $res = 0;
                        break;
                    } else if (empty($model->item_no)) {
                        $res = 0;
                        break;
                    } else if (!is_numeric($model->item_qty)) {
                        $res = 0;
                        break;
                    }
                }
            }
        } catch (\Exception $ex) {
            $res = -2;
        }

        return $res;
    }


    public function SearchModel($page, $rows, $item_no) {
       
        $where="";
        if (!empty($item_no)) {
        	$where.=" and s.item_no like '%$item_no%'";
        }
        
        $offset = ($page - 1) * $rows;
        
        $rowCount=Db::name("bd_item_info")
        ->alias('s')
        ->join('bd_item_cls a','s.item_clsno=a.item_clsno',"LEFT")
        ->join('bd_base_code b','s.item_brand=b.code_id',"LEFT")
        ->where("s.combine_sta <> '0' and b.type_no ='PP' $where")
        ->count();
        
        
        $result = array();
        $result["total"] = $rowCount;
        
        $res=Db::name("bd_item_info")
        ->alias('s')
        ->field("s.item_no,s.item_subno,s.item_name,s.item_subname,s.item_clsno , a.item_clsname as cls_name," .
        		"s.item_brand,b.code_name as brand_name,s.unit_no,s.item_size,s.product_area," .
        		"s.price,s.sale_price,case s.combine_sta when '1' then '捆绑商品' when '2' then '制单拆分' when '3' then '制单组合' when '6' then  '自动转货' when '7' then '自动加工' else '' end as combine_sta ")
        		->join('bd_item_cls a','s.item_clsno=a.item_clsno',"LEFT")
        		->join('bd_base_code b','s.item_brand=b.code_id',"LEFT")
        		->where("s.combine_sta <> '0' and b.type_no ='PP' $where")
        		->limit($offset,$rows)
        		->select();
        
        $temp = array();
        foreach ($res as $k => $v) {
            $tt = array();
            $tt["item_no"] = $v["item_no"];
            $tt["item_subno"] = $v["item_subno"];
            $tt["item_name"] = $v["item_name"];
            $tt["item_subname"] = $v["item_subname"];
            $tt["item_clsno"] = $v["item_clsno"];
            $tt["cls_name"] = $v["cls_name"];
            $tt["item_brand"] = $v["item_brand"];
            $tt["brand_name"] = $v["brand_name"];
            $tt["unit_no"] = $v["unit_no"];
            $tt["item_size"] = $v["item_size"];
            $tt["product_area"] = $v["product_area"];
            $tt["price"] = $v["price"];
            $tt["sale_price"] = $v["sale_price"];
            $tt["combine_sta"] = $v["combine_sta"];
            array_push($temp, $tt);
        }
        $result["rows"] = $temp;
        return $result;
    }


    public function GetCombDetail($comb_item_no) {
        
        $where='';
        if (!empty($comb_item_no)) {
        	$where.=" and c.comb_item_no ='$comb_item_no'";
        }
        
        $res=Db::name("bd_item_info")
        ->alias('s')
        ->field("s.item_no,s.purchase_spec,s.item_subno,s.item_name,s.item_subname,s.item_clsno , a.item_clsname as cls_name," .
                "s.item_brand,b.code_name as brand_name,s.unit_no,s.item_size,s.product_area,c.item_qty as item_qty," .
                "s.price,s.price*c.item_qty as amount,s.sale_price,s.sale_price*c.item_qty as sale_amt")
        ->join('bd_item_cls a','s.item_clsno=a.item_clsno',"LEFT")
        ->join('bd_base_code b','s.item_brand=b.code_id',"LEFT")
        ->join('bd_item_combsplit c','s.item_no=c.item_no',"LEFT")
        ->where("s.combine_sta ='0' and b.type_no ='PP' $where")
        ->limit($offset,$rows)
        ->select();
        
        $result = array();
        foreach ($res as $v) {
            $tt = array();
            $tt["item_no"] = $v["item_no"];
            $tt["item_subno"] = $v["item_subno"];
            $tt["item_name"] = $v["item_name"];
            $tt["item_subname"] = $v["item_subname"];
            $tt["item_clsno"] = $v["item_clsno"];
            $tt["cls_name"] = $v["cls_name"];
            $tt["item_brand"] = $v["item_brand"];
            $tt["brand_name"] = $v["brand_name"];
            $tt["purchase_spec"] = $v["purchase_spec"];
            $tt["unit_no"] = $v["unit_no"];
            $tt["item_size"] = $v["item_size"];
            $tt["product_area"] = $v["product_area"];
            $tt["price"] = sprintf('%.2f', $v["price"]);
            $tt["sale_price"] = sprintf('%.2f', $v["sale_price"]);
            $tt["combine_sta"] = $v["combine_sta"];
            $tt["item_qty"] = sprintf('%.2f', $v["item_qty"]);
            $tt["amount"] = sprintf('%.2f', $v["amount"]);
            $tt["sale_amt"] = sprintf('%.2f', $v["sale_amt"]);
            array_push($result, $tt);
        }
        return $result;
    }


    public function GetModelsForPos() {
        return $this->findAll();
    }

    public $item_name;
    public $comb_item_name;


    public function GetModelsForCNS() {
        
        $res=Db::name($this->name)
        ->alias('s')
        ->field("s.comb_item_no,s.item_no,s.item_qty,s.memo,s.relation_px,a.item_name as comb_item_name,b.item_name")
        ->join('bd_item_info a','s.comb_item_no=a.item_no',"LEFT")
        ->join('bd_item_info b','s.item_no=b.item_no',"LEFT")
        ->select();
        
        $result = array();
        foreach ($res as $v) {
            $tt = array();
            $tt["comb_item_no"] = $v["comb_item_no"];
            $tt["item_no"] = $v["item_no"];
            $tt["item_qty"] = $v["item_qty"];
            $tt["relation_px"] = $v["relation_px"];
            $tt["comb_item_name"] = $v["comb_item_name"];
            $tt["item_name"] = $v["item_name"];
            array_push($result, $tt);
        }
        return $result;
    }


    public function GetSingle($item_no) {
        return $this->where("comb_item_no='$item_no'")->select();
    }

    public $rtype;
    public $rid;
    public $updatetime;

    public function GetUpdateDataForPos($rid = "", $updatetime = "") {
        
        if ($rid == "-1") {
        	$result = Db::table($this->table)
        				->alias('s')
	        			->field("0 as rid,'I' as rtype,now() as updatetime," .
                    			"s.comb_item_no,s.item_no,s.item_qty,s.memo,s.relation_px")
	        			->select();
        }else{
        	if (empty($rid)) {
        		$rid = 0;
        	}
        	//不查询已删除记录，否则右连接产生很多空白数据
        	$where="a.rid > $rid and a.rtype!='D'";
        	if (!empty($updatetime)) {
        		$where.=" and a.updatetime>$updatetime";
        	}
        
        	$result = Db::table($this->table)
        	->alias('s')
        	->field("a.rid,a.rtype,a.updatetime,a.comb_item_no,a.item_no,s.item_qty,s.memo,s.relation_px")
        	->join('bd_item_combsplit_breakpoint a','s.comb_item_no=a.comb_item_no and s.item_no=a.item_no',"RIGHT")
        	->where($where)
        	->select();
        }
        
        $list = array();
        if ($rid == "-1") {
        	$Breakpoint=new BdItemCombsplitBreakpoint;
            $r_id = $Breakpoint->GetMaxRidForUpdate();
        }
        foreach ($result as $v) {
            $tt = array();
            $tt["rid"] = ($rid == "-1") ? $r_id : $v["rid"];
            $tt["rtype"] = $v["rtype"];
            $tt["updatetime"] = $v["updatetime"];
            $tt["comb_item_no"] = $v["comb_item_no"];
            $tt["item_no"] = $v["item_no"];
            $tt["item_qty"] = $v["item_qty"];
            $tt["memo"] = $v["memo"];
            $tt["relation_px"] = $v["relation_px"];
            array_push($list, $tt);
        }
        return $list;
    }
    
    //删除组合商品整个项目
    public function delComb($comb_item_no,$item_no){
    	$result=$this->where("comb_item_no='$comb_item_no' and item_no='$item_no'")->delete();
    	if(!$result){
    		return false;
    	}
    	
    	$Breakpoint=new BdItemCombsplitBreakpoint;
    	$bdBreakPoint->rtype = EOperStatus::DELETE;
    	$bdBreakPoint->item_no = $item_no;
    	$bdBreakPoint->comb_item_no = $comb_item_no;
    	$bdBreakPoint->updatetime = date(DATETIME_FORMAT);
    	$bdBreakPoint->save();
    	
    	return true;
    }

}
