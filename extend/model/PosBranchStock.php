<?php
//pos_branch_stock表
namespace model;
use app\admin\components\Enumerable\EOperStatus;
use think\Db;
use model\BdItemCombsplit;
use model\Item_info;
use model\PosBranchStockBreakpoint;
use model\StockFlow;

class PosBranchStock extends BaseModel {

	protected $name="pos_branch_stock";
	
	public $item_name;

    public function search($condition) {
    	$list=Db::table($this->table)
    	->where($condition)
    	->select();
    }

    public function getall($content) {
    	
    	$where="1=1";
    	if ($content['branch_no'] != '') {
    		$where.=" and m.branch_no='{$content['branch_no']}'";
    	}
    	if ($content['item_no'] != '') {
    		$where.=" and m.item_no='{$content['item_no']}'";
    	}
    	if ($content['item_name'] != '') {
    		$where.=" and c.item_name like '%{$content['item_name']}%'";
    	}
    	
    	$pagesize = 30;
    	$list=$this
    			->alias("m")
    			->field("m.item_no,m.branch_no,m.stock_qty,m.last_inprice,c.item_name")
    			->join("bd_item_info c","c.item_no=m.item_no","LEFT")
    			->where($where)
    			->paginate($pagesize);
    	
    	$page=$list->render();
    	
    	$return['result'] = $list;
    	$return['pages'] = $page;
    	return $return;
    }
    
    
    public function GetStockByBraItem($branch_no, $item_no) {
    	return $this->where("branch_no='$branch_no' and item_no='$item_no'")->find();
    }
    
    //在api.php 的updateStock 函数调用该接口
    public function UpdateStock($branch_no, $item_no, $qty, $db_no) {
    	$res = FALSE;
    	try {
    		$BdItemCombsplit=new BdItemCombsplit();
    		$combs = $BdItemCombsplit->GetSingle($item_no);
    
    		if (empty($combs)) {
    			$res = $this->UpdateStock1($branch_no, $item_no, $qty, $db_no);
    		} else {
    			$res = $this->UpdateStock1($branch_no, $item_no, $qty, $db_no);
    			if ($res) {
    				$Item_info=new Item_info();
    				foreach ($combs as $com_model) {
    					$com_item_model = $Item_info->where("item_no='{$com_model->item_no}'")->find();
    					$res = $this->UpdateStock1($branch_no, $com_model->item_no, $qty * ($com_model->item_qty), $db_no);
    				}
    			}
    		}
    	} catch (\Exception $ex) {
    		return FALSE;
    	}
    	return $res;
    }
    
    //后台库存调整单审核之后，更新pos_branch_stock,stock_flow表，没更新pos_branch_stock_breakpoint
    public function UpdateStockBySheetNo($sheet_no, $branch_no, $item_no, $qty, $db_no) {
    	$res = FALSE;
    	try {
    		$BdItemCombsplit=new BdItemCombsplit();
    		$combs = $BdItemCombsplit->GetSingle($item_no);
    
    		$sell_way = $db_no == "+" ? "B" : "A";
    
    		if (empty($combs)) {
    			$res = $this->UpdateStockForFlow($sheet_no, $db_no, $branch_no, $item_no, $qty,$sell_way);
    		} else {
    			$res = $this->UpdateStockForFlow($sheet_no, $db_no, $branch_no, $item_no, $qty,$sell_way);
    			if ($res) {
    				$Item_info=new Item_info();
    				foreach ($combs as $com_model) {
    					$com_item_model = $Item_info->where("item_no='{$com_model->item_no}'")->find();
    					$res = $this->UpdateStockForFlow($sheet_no, $db_no, $branch_no, $com_model->item_no, $qty * ($com_model->item_qty),$sell_way);
    				}
    			}
    		}
    	} catch (\Exception $ex) {
    		return FALSE;
    	}
    	return $res;
    }
    
    //API POS向后台通讯执行更新后台库存/日结也有调用该函数
    public function UpdateStock1($branch_no, $item_no, $qty, $db_no) {
    	$model = $this->where("branch_no='$branch_no' and item_no='$item_no'")->find();
    	if (empty($model)) {
    		$model = new PosBranchStock();
    		$model->branch_no = $branch_no;
    		$model->item_no = $item_no;
    		$model->stock_qty = 0;
    	}
    	$model->oper_date = date(DATETIME_FORMAT, time());
    	if ($db_no == "+") {
    		$model->stock_qty = doubleval($model->stock_qty) + doubleval($qty);
    	} else {
    		$model->stock_qty = doubleval($model->stock_qty) - doubleval($qty);
    	}
    	if ($model->save()) {
    		$bdBreakPoint = new PosBranchStockBreakpoint();
    		$bdBreakPoint->rtype = EOperStatus::UPDATE;
    		$bdBreakPoint->item_no = $item_no;
    		$bdBreakPoint->branch_no = $branch_no;
    		$bdBreakPoint->updatetime = date(DATETIME_FORMAT);
    		$bdBreakPoint->save();
    		return TRUE;
    	} else {
    		return FALSE;
    	}
    }
    
    //API POS向后台通讯执行更新后台库存
    public function UpdateStockQty($sheet_no, $branch_no, $item_no, $qty) {
    	$old_qty = 0;
    	$real_qty = 0;
    	$new_qty = 0;
    	$sell_way = "P";
    	$db_no = "=";
    	$model = $this->where("branch_no='$branch_no' and item_no='$item_no'")->find();
    	if (empty($model)) {
    		$model = new PosBranchStock();
    		$model->branch_no = $branch_no;
    		$model->item_no = $item_no;
    		$old_qty = 0;
    		$real_qty = $qty;
    		$new_qty = $qty;
    	} else {
    		$old_qty = $model->stock_qty;
    		$real_qty = $qty;
    		$new_qty = $qty;
    	}
    	$model->oper_date = date(DATETIME_FORMAT, time());
    	$model->stock_qty = doubleval($qty);
    	if ($model->save()) {
    		$bdBreakPoint = new PosBranchStockBreakpoint();
    		$bdBreakPoint->rtype = EOperStatus::UPDATE;
    		$bdBreakPoint->item_no = $item_no;
    		$bdBreakPoint->branch_no = $branch_no;
    		$bdBreakPoint->updatetime = date(DATETIME_FORMAT);
    		$bdBreakPoint->save();
    		$StockFlow=new StockFlow();
    		$StockFlow->Add($branch_no, $sheet_no, $db_no, $item_no, $old_qty, $real_qty, $new_qty, $sell_way);
    		return TRUE;
    	} else {
    		return FALSE;
    	}
    }
    
    public $item_size;
    public $item_clsname;
    public $code_name;
    public $unit_no;
    
    
    public function GetBranchStockForPos($branch_no, $item_clsno, $item_brand, $item_supcust, $item_no, $last_time = "") {
    	$prefix=$this->prefix;
    	$date=date("Y-m-d",time());
    	$sql = "SELECT
distinct t.branch_no,t.item_no,i.item_name,i.item_size,t.stock_qty,i.item_no,c.item_clsname,d.code_name
FROM
(
SELECT s.branch_no,i.item_no,s.stock_qty,s.oper_date
FROM
".$prefix."pos_branch_stock s
LEFT JOIN  ".$prefix."bd_item_info i
on s.item_no=i.item_no
and i.combine_sta=0
UNION
(
		SELECT
			b.branch_no,
			b.comb_item_no as item_no,
			min(b.sum_qty) as stock_qty,
                        '".$date."' as oper_date
			FROM
			(
					SELECT s.branch_no,c.comb_item_no,c.item_no,round(s.stock_qty/c.item_qty,2)  as sum_qty
					FROM
					".$prefix."bd_item_combsplit c
					LEFT JOIN ".$prefix."bd_item_info  i
					on c.comb_item_no=i.item_no
					LEFT JOIN ".$prefix."pos_branch_stock s
					on c.item_no=s.item_no
			) b
			GROUP BY
			b.branch_no,
			b.comb_item_no
)
) t
LEFT JOIN ".$prefix."bd_item_info i
on t.item_no=i.item_no
LEFT JOIN ".$prefix."bd_item_cls c
on i.item_clsno=c.item_clsno
LEFT JOIN ".$prefix."bd_base_code d
on i.item_brand=d.code_id
and d.type_no='PP'
LEFT JOIN ".$prefix."bd_supcust_item e
on i.item_no=e.item_no";
    	$params = array();
    	$isWhere = FALSE;
    	if (!empty($branch_no)) {
    		$sql = $sql . "  WHERE t.branch_no='$branch_no'";
    		$isWhere = TRUE;
    	}
    	if (!empty($item_clsno)) {
    		if ($isWhere) {
    			$sql = $sql . "	and i.item_clsno  like '$item_clsno%'";
    		} else {
    			$sql = $sql . " WHERE i.item_clsno like  '$item_clsno%'";
    			$isWhere = TRUE;
    		}
    	}
    	if (!empty($item_brand)) {
    		if ($isWhere) {
    			$sql = $sql . " and i.item_brand='$item_brand'";
    		} else {
    			$sql = $sql . " WHERE i.item_brand='$item_brand'";
    			$isWhere = TRUE;
    		}
    	}
    	if (!empty($item_supcust)) {
    		if ($isWhere) {
    			$sql = $sql . " and e.supcust_no='$item_supcust'";
    		} else {
    			$sql = $sql . " WHERE e.supcust_no='$item_supcust'";
    			$isWhere = TRUE;
    		}
    	}
    	if (!empty($item_no)) {
    		if ($isWhere) {
    			$sql = $sql . " and (i.item_name like '%$item_no%' or  t.item_no like '%$item_no%' or t.item_no in (select item_no from " .$this->prefix."bd_item_barcode" . " where item_barcode like '%$item_no%' ) ) ";
    		} else {
    			$sql = $sql . " WHERE  (i.item_name like '%$item_no%' or  t.item_no like '%$item_no%' or t.item_no in (select item_no from " . $this->prefix."bd_item_barcode" . " where item_barcode like '%$item_no%') ) ";
    			$isWhere = TRUE;
    		}
    	}
    	if (!empty($last_time)) {
    		if ($isWhere) {
    			$sql = $sql . " and t.oper_date >= '$last_time'";
    		} else {
    			$sql = $sql . " WHERE  t.oper_date >= '$last_time'";
    			$isWhere = TRUE;
    		}
    	}
    	$result=Db::query($sql);
    	$res = array();
    	foreach ($result as $k => $v) {
    		$tt = array();
    		$tt["branch_no"] = $v["branch_no"];
    		$tt["item_no"] = $v["item_no"];
    		$tt["item_name"] = $v["item_name"];
    		$tt["item_size"] = $v["item_size"];
    		$tt["stock_qty"] = $v["stock_qty"];
    		$tt["item_clsname"] = $v["item_clsname"];
    		$tt["code_name"] = $v["code_name"];
    		$tt["unit_no"] = $v["unit_no"];
    		array_push($res, $tt);
    	}
    	return $res;
    }
    
    public $order_qty;
    public $large_qty;
    public $rowIndex;
    
    
    public $status;
    public function GetItemInfoForComControl($branch_no, $item_clsno = "", $spcust_no = "", $combine_sta = "", $keyword = "", $stock = "", $page = 1, $rows = 10) {
    	
    	$offset = ( $page - 1) * $rows;
    	
    	$where="c.type_no='PP'";
    	if (!empty($branch_no)) {
    		$where.=" and s.branch_no='$branch_no'";
    	}
    	if ($stock == "1") {
    		$where.=" and s.stock_qty > 0";
    	}
    	if (!empty($item_clsno)) {
    		if ($item_clsno != "top") {
    			$where.=" and a.item_clsno like '$item_clsno%'";
    		}
    	}
    	if (!empty($spcust_no)) {
    		$where.=" and g.supcust_no= '".trim($spcust_no)."'";
    	}
    	
    	if (!empty($keyword)) {
    		$where.=" and (a.item_name like '%$keyword%' or a.item_no like '%$keyword%' or a.item_clsno like '%$keyword%' or a.item_no in (SELECT item_no from " . $this->prefix."bd_item_barcode" . " WHERE item_barcode like '%$keyword%'))";
    	}

    	if (empty($spcust_no)) {
    		
    		$list=Db::name($this->name)
    				->alias('s')
    				->field("s.branch_no,s.item_no,s.stock_qty as item_stock," .
    				"a.item_subno,a.item_name,a.item_clsno,b.item_clsname," .
    				"a.item_brand,a.unit_no,a.item_size,c.code_name as item_brandname," .
    				"a.product_area,a.purchase_spec,a.num3,f.price as item_price,f.sale_price,a.status," .
    				"d.sp_company as sp_name")
    				->join('bd_item_info a','s.item_no = a.item_no',"LEFT")
    				->join('bd_item_cls b','a.item_clsno= b.item_clsno',"LEFT")
    				->join('bd_base_code c','a.item_brand = c.code_id',"LEFT")
    				->join('sp_infos d','a.main_supcust= d.sp_no',"LEFT")
    				->join('pc_branch_price f','s.branch_no=f.branch_no and s.item_no=f.item_no',"LEFT")
    				->limit($offset,$rows)
    				->where($where)
    				->select();
    		
    		$count=Db::name($this->name)
    				->alias('s')
    				->join('bd_item_info a','s.item_no = a.item_no',"LEFT")
    				->join('bd_item_cls b','a.item_clsno= b.item_clsno',"LEFT")
    				->join('bd_base_code c','a.item_brand = c.code_id',"LEFT")
    				->join('sp_infos d','a.main_supcust= d.sp_no',"LEFT")
    				->join('pc_branch_price f','s.branch_no=f.branch_no and s.item_no=f.item_no',"LEFT")
    				->where($where)
    				->count();
    		
    	} else {

    		$list=Db::name($this->name)
    				->alias('s')
    				->field("s.branch_no,s.item_no,s.stock_qty as item_stock," .
    				"a.item_subno,a.item_name,a.item_clsno,b.item_clsname," .
    				"a.item_brand,a.unit_no,a.item_size,c.code_name as item_brandname," .
    				"a.product_area,a.purchase_spec,a.num3,f.price as item_price,f.sale_price,a.status" .
    				"d.sp_company as sp_name")
    				->join('bd_item_info a','s.item_no = a.item_no',"LEFT")
    				->join('bd_item_cls b','a.item_clsno= b.item_clsno',"LEFT")
    				->join('bd_base_code c','a.item_brand = c.code_id',"LEFT")
    				->join('sp_infos d','a.main_supcust= d.sp_no',"LEFT")
    				->join('pc_branch_price f','s.branch_no=f.branch_no and s.item_no=f.item_no',"LEFT")
    				->join('bd_supcust_item g','s.item_no=g.item_no ',"LEFT")
    				->limit($offset,$rows)
    				->where($where)
    				->select();
    		
    		$count=Db::name($this->name)
    				->alias('s')
    				->join('bd_item_info a','s.item_no = a.item_no',"LEFT")
    				->join('bd_item_cls b','a.item_clsno= b.item_clsno',"LEFT")
    				->join('bd_base_code c','a.item_brand = c.code_id',"LEFT")
    				->join('sp_infos d','a.main_supcust= d.sp_no',"LEFT")
    				->join('pc_branch_price f','s.branch_no=f.branch_no and s.item_no=f.item_no',"LEFT")
    				->join('bd_supcust_item g','s.item_no=g.item_no ',"LEFT")
    				->where($where)
    				->count();
    
    	}
    	
    	
    	$result = array();
    	$result["total"] = $count;
    	$temp = array();
    	foreach ($list as $v) {
    		$tt = array();
    		$tt["rowIndex"] = $rowIndex;
    		$tt["branch_no"] = $v["branch_no"];
    		$tt["item_no"] = $v["item_no"];
    		$tt["item_stock"] = $v["item_stock"];
    		$tt["item_subno"] = $v["item_subno"];
    		$tt["item_name"] = $v["item_name"];
    		$tt["item_clsno"] = $v["item_clsno"];
    		$tt["item_clsname"] = $v["item_clsname"];
    		$tt["item_price"] = $v["item_price"];
    		$tt["sale_price"] = $v["sale_price"];
    		$tt["item_brand"] = $v["item_brand"];
    		$tt["item_brandname"] = $v["item_brandname"];
    		$tt["unit_no"] = $v["unit_no"];
    		$tt["item_size"] = $v["item_size"];
    		$tt["product_area"] = $v["product_area"];
    		$tt["purchase_spec"] = $v["purchase_spec"];
    		$tt["num2"] = $v["num2"];
    		$tt["sub_amt"] = formatMoneyDisplay($v["item_price"] * $v["purchase_spec"]);
    		$tt["sale_amt"] = formatMoneyDisplay($v["sale_price"] * $v["purchase_spec"]);
    		$tt["sp_name"] = $v["sp_name"];
    		$tt["order_qty"] = formatMoneyDisplay($v["purchase_spec"]);
    		$tt["large_qty"] = 1;
    
    		$tt["status"] = $v["status"];
    		$rowIndex++;
    		array_push($temp, $tt);
    	}
    	$result["rows"] = $temp;
    	return $result;
    }
    
    public $send_qty;
    
    
    public function GetInstanceForComSelect($branch_no, $item_no, $supcust_no = "", $item_stock = "") {
    	
    	$where="1=1";
    	if (!empty($branch_no)) {
    		$where.=" and s.branch_no='$branch_no'";
    	}
    	if ($item_stock == "1") {
    		$where.=" and s.stock_qty > 0";
    	}
    	if (!empty($supcust_no)) {
    		$where.=" and c.supcust_no='".trim($supcust_no)."'";
    	}
    	if (!empty($item_no)) {
    		$where.=" and ( a.item_no='$item_no' or a.item_no in (select item_no from " . $this->prefix."bd_item_barcode" . " where item_barcode='$item_no' ))";
    	}
    	
    	$temp=Db::name($this->name)
    			->alias('s')
    			->field('s.item_no,s.stock_qty as item_stock,a.item_subno,a.item_name,a.item_clsno,' .
    			"a.item_brand,a.unit_no,a.price,a.sale_price as asale_price,a.vip_price as avip_price,a.unit_no as item_unit,a.item_size,a.product_area,a.purchase_spec,a.num2," .
    			"b.price as item_price,a.sale_price,a.vip_price,a.sup_ly_rate,a.trans_price,d.sp_name")
    			->join('bd_item_info a','s.item_no= a.item_no',"LEFT")
    			->join('pc_branch_price b','s.item_no=b.item_no and s.branch_no=b.branch_no',"LEFT")
    			->join('bd_supcust_item c','s.item_no=c.item_no',"LEFT")
    			->join('sp_infos d','a.main_supcust= d.sp_no',"LEFT")
    			->where($where)
    			->find();
    	//echo Db::name($this->name)->getLastSql();
    	if (!empty($temp)) {
    		$tt = array();
    		$tt["item_no"] = $temp["item_no"];
    		$tt["item_stock"] = $temp["item_stock"];
    		$tt["item_subno"] = $temp["item_subno"];
    		$tt["item_name"] = $temp["item_name"];
    		$tt["item_clsno"] = $temp["item_clsno"];
    		$tt["item_brand"] = $temp["item_brand"];
    		$tt["unit_no"] = $temp["unit_no"];
    		$tt["item_unit"] = $temp["item_unit"];
    		$tt["item_size"] = $temp["item_size"];
    		$tt["product_area"] = $temp["product_area"];
    		$tt["purchase_spec"] = $temp["purchase_spec"];
    		$tt["num2"] = $temp["num2"];
    		$tt["send_qty"] = "0.00";
    		$tt["item_price"] = $temp["item_price"]>0?formatMoneyDisplay($temp["item_price"]):formatMoneyDisplay($temp["price"]);
    		$tt["sub_amt"] = $temp["item_price"]>0?formatMoneyDisplay($temp["item_price"]):formatMoneyDisplay($temp["price"]);
    		$tt["sale_price"] = $temp["sale_price"]>0?formatMoneyDisplay($temp["sale_price"]):formatMoneyDisplay($temp["asale_price"]);
    		$tt["sale_amt"] = $temp["sale_price"]>0?formatMoneyDisplay($temp["sale_price"]):formatMoneyDisplay($temp["asale_price"]);
    		$tt["sp_name"] = $temp["sp_name"];
    		$tt["order_qty"] = formatMoneyDisplay($temp["purchase_spec"]);
    		$tt["price"] = $temp["item_price"]>0?formatMoneyDisplay($temp["item_price"]):formatMoneyDisplay($temp["price"]);
    		$tt["price1"] = formatMoneyDisplay("0.00");
    		$tt["sale_price1"] = formatMoneyDisplay("0.00");
    		$tt["vip_price"] = formatMoneyDisplay($temp["vip_price"]);
    		$tt["vip_price1"] = formatMoneyDisplay("0.00");
    		$tt["sup_ly_rate1"] = formatMoneyDisplay("0.00");
    		$tt["sup_ly_rate"] = formatMoneyDisplay($temp["sup_ly_rate"]);
    		$tt["trans_price1"] = formatMoneyDisplay("0.00");
    		$tt["trans_price"] = formatMoneyDisplay($temp["trans_price"]);
    		$tt["large_qty"] = 1;
    		if (!empty($tt["sale_price"]) && doubleval($tt["sale_price"]) != 0) {
    			$t_mlx = doubleval(sprintf("%.4f", ($tt["sale_price"] - $tt["price"]) / $tt["sale_price"])) * 100;
    			$tt["mlv"] = strval($t_mlx) . "%";
    		} else {
    			$tt["mlv"] = "";
    		}
    		return $tt;
    	}
    }
    
    
    public function SetStockage($model) {
    
    }
    
    
    public function GetStockItem($branchno, $itemno) {
    	if (empty($branchno)) {
    		return FALSE;
    	}
    	if (empty($itemno)) {
    		return FALSE;
    	}
    	$branchno=trim($branchno);
    	$itemno=trim($itemno);
    	$model = $this->where("branch_no='$branchno' and item_no='$itemno'")->find();
    	return $model;
    }
    
    
    public $item_stock;
    
    public $item_subno;
    
    
    
    public $item_clsno;
    
    
    
    public $item_brand;
    
    public $item_brandname;
    
    public $item_unit;
    
    
    
    public $product_area;
    
    public $purchase_spec;
    
    public $num2;
    
    public $item_price;
    
    public $sale_price;
    
    public $sp_name;
    
    public $sub_amt;
    
    public $sale_amt;
    public $vip_price;
    public $sup_ly_rate;
    public $trans_price;
    
    
    public function GetModelsForCNS($branch_no, $item_no = "") {
    	
    	$where="a.is_open='1'";
    	
    	if (!empty($branch_no)) {
    		$where.=" and s.branch_no='$branch_no'";
    	}
    	
    	if (!empty($item_no)) {
    		$where.=" and a.item_no='$item_no'";
    	}
    	
    	$temp=Db::name($this->name)
    			->alias('s')
    			->field("s.branch_no,s.item_no,s.stock_qty as item_stock,a.item_name")
    			->join('bd_item_info a','s.item_no= a.item_no',"LEFT")
    			->where($where)
    			->select();
    	
    	$result = array();
    	if (!empty($temp)) {
    		foreach ($temp as $v) {
    			$tt = array();
    			$tt["branch_no"] = $v["branch_no"];
    			$tt["item_no"] = $v["item_no"];
    			$tt["item_stock"] = $v["item_stock"];
    			$tt["item_name"] = $v["item_name"];
    			array_push($result, $tt);
    		}
    	}
    	return $result;
    }
    
    public $rtype;
    public $rid;
    public $updatetime;
    
    public function GetUpdateDataForPos($branch_no, $rid = "", $updatetime = "") {
    	
    	$where="1=1";
    	if (!empty($branch_no)) {
    		$where.=" and s.branch_no='$branch_no'";
    	}
    	
    	if ($rid == "-1") {
    		
    		$result=Db::name($this->name)
		    		->alias('s')
		    		->field("0 as rid,'I' as rtype,now() as updatetime," .
		    				"s.branch_no,s.item_no,s.stock_qty")
		    		->where($where)
		    		->select();
    		
    	} else {
    		
    		if (empty($rid)) {
    			$rid = 0;
    		}
    		
    		$where.=" and a.rid > '$rid'";
    		if (!empty($updatetime)) {
    			$where.=" and a.updatetime > '$updatetime'";
    		}
    		
    		$result=Db::name($this->name)
    				->alias('s')
    				->field("a.rid,a.rtype,a.updatetime,a.branch_no,a.item_no,s.stock_qty")
    				->join('pos_branch_stock_breakpoint a','s.branch_no=a.branch_no and s.item_no=a.item_no',"RIGHT")
    				->where($where)
    				->select();
    	}

    	$list = array();
    	if ($rid == "-1") {
    		$PosBranchStockBreakpoint=new PosBranchStockBreakpoint();
    		$r_id = $PosBranchStockBreakpoint->GetMaxRidForUpdate();
    	}
    	foreach ($result as $v) {
    		$tt = array();
    		$tt["rid"] = ($rid == "-1") ? $r_id : $v["rid"];
    		$tt["rtype"] = $v["rtype"];
    		$tt["updatetime"] = $v["updatetime"];
    		$tt["branch_no"] = $v["branch_no"];
    		$tt["item_no"] = $v["item_no"];
    		$tt["stock_qty"] = $v["stock_qty"];
    		array_push($list, $tt);
    	}
    	return $list;
    }
    
    public function UpdateStockForFlow($sheet_no, $db_no, $branch_no, $item_no, $real_qty, $sell_way) {
        $result = 0;
        $old_qty = 0;
        $new_qty = 0;
        $PosBranchStock=new PosBranchStock();
        try {
        	$model=$PosBranchStock->where("branch_no='$branch_no' and item_no='$item_no'")->find();
            if (empty($model)) {
                if ($db_no == "-") {
                    $result = -1;
                } else {
                    $model = new PosBranchStock();
                    $model->branch_no = $branch_no;
                    $model->item_no = $item_no;
                    $model->stock_qty = $real_qty;
                    $model->oper_date = date(DATETIME_FORMAT);
                    $old_qty = 0;
                    $new_qty = $real_qty;
                }
            } else {
	                if ($db_no == "-") {
	                    $old_qty = $model->stock_qty;
	                    $new_qty = $model->stock_qty - $real_qty;
	                    $model->stock_qty = $new_qty;
	                    if (doubleval($new_qty) < 0) {
	                        $result = -1;
	                    }
	                } else {
		                $old_qty = $model->stock_qty;
		                $new_qty = $model->stock_qty + $real_qty;
		                $model->stock_qty = $new_qty;
           			 }
        	}
            if ($result == 0) {
                if ($model->save() === TRUE) {
                	$StockFlow=new StockFlow();
                    $result =$StockFlow->Add($branch_no,$sheet_no, $db_no, $item_no, $old_qty, $real_qty, $new_qty, $sell_way);
                }
            }
        } catch (\Exception $ex) {
        	write_log("库存表更新库存(UpdateStock)异常:" . $ex,"PosBranchStock");
            $result = -2;
        }
        return $result;
    }
    
    

}
