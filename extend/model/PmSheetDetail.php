<?php
//pm_sheet_detail表
namespace model;
use think\Db;
use model\Item_info;
use model\PosBranchStock;
use model\PcBranchPrice;
use app\admin\components\Enumerable\ESheetStatus;

class PmSheetDetail extends BaseModel {

    public $item_subno;
    public $item_name;
    public $item_size;
    public $item_unit;
    public $purchase_spec;
    public $purchase_tax;
    public $item_price;
    public $memo;
    public $sale_amt;
    public $sale_price;
    public $sp_name;
    public $price;

    protected $pk='flow_id';
    protected $name="pm_sheet_detail";

    public function Add($model) {
        try {
            $res = $this->Check($model);
            if ($res == 1) {
                if ($model->save()) {
                    return TRUE;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        } catch (\Exception $ex) {
            return FALSE;
        }
    }


    private function Check($model) {
        $res = 1;
        if (empty($model->sheet_no)) {
            $res = "单据编号不能为空";
        } else if (empty($model->item_no)) {
            $res = "商品编号不能为空";
        } else if (!is_numeric($model->real_qty)) {
            $res = "订单数量格式不正确";
        } else if (!is_numeric($model->large_qty)) {
            $res = "箱数格式不正确";
        } else if (!is_numeric($model->orgi_price)) {
            $res = "单价格式不正确";
        } else if (!is_numeric($model->sub_amt)) {
            $res = "商品金额不正确";
        }
        return $res;
    }


    public function Del($sheet_no) {
    	$delNum=$this->where("sheet_no='$sheet_no'")->delete();
        if ($delNum > 0) {
            return true;
        }
        return false;
    }


    public function Get($sheet_no) {
        
        return $list=Db::name($this->name)
        ->alias('p')
        ->field("p.item_no,p.large_qty,p.order_qty,p.real_qty,p.orgi_price as item_price,p.sub_amt,p.other1 as memo,p.tax," .
                "b.item_subno,b.item_name,b.unit_no as item_unit,b.item_size,b.purchase_spec,b.purchase_tax,p.valid_date,b.sale_price")
        ->join('bd_item_info b','p.item_no=b.item_no',"LEFT")
        ->where("p.sheet_no='$sheet_no'")
        ->select();
    }


    public function UpdateOrderQty($sheetno, $itemno, $orderQty) {
        $model = $this->where("sheet_no='$sheetno' and item_no='$itemno'")->find();
        if (empty($model)) {
            return 1;
        } else {
            if (!empty($sheetno) && !empty($orderQty) && !empty($itemno)) {
                $attr = array("order_qty" => ($orderQty + $model->order_qty));
                $count = Db::name($this->name)->where("sheet_no='$sheetno' and item_no='$itemno'")->update($attr);
                if ($count > 0) {
                    return 1;
                } else {
                    return 0;
                }
            }
        }
        return 0;
    }


    public function GetSheetSendOut($sheetno) {
        return $list=$this->field("item_no")
        		->where("sheet_no='$sheetno' and real_qty <= order_qty")
        		->select();
    }


    public function SaveDetails($detail) {
        $res = $this->CheckModel($detail);
        if ($res) {
            try {
            	$Item_info=new Item_info();
                $item_model = $Item_info->where("item_no='{$detail->item_no}'")->find();
                $detail->orgi_price = $item_model->price;
                if ($detail->save()) {
                    return 1;
                } else {
                    return 0;
                }
            } catch (\Exception $ex) {
                return -2;
            }
        } else {
            return -1;
        }
    }


    private function CheckModel($detail) {
        $res = TRUE;
        if (empty($detail->item_no)) {
            $res = "商品编号不能为空";
        } else if (empty($detail->sheet_no)) {
            $res = "商品明细对应单号不能为空";
        } else if (!is_numeric($detail->order_qty)) {
            $res = "订单数量不能为空";
        } else if (!is_numeric($detail->large_qty)) {
            $res = "箱数数量不能为空";
        }
        else if (!is_numeric($detail->valid_price)) {
            $res = "商品确认价格不能为空";
        } else if (!is_numeric($detail->sub_amt)) {
            $res = "商品售价总额不能为空";
        }
        return $res;
    }


    public function DeleteDetail($sheet_no) {
        try {
            $res = $this->where("sheet_no='$sheet_no'")->delete();
            if ($res > 0) {
                return 1;
            } else {
                return 0;
            }
        } catch (\Exception $ex) {
            return -2;
        }
    }


    private function UpdateRealQty($flow_id, $real_qty) {
        $model = $this->where("flow_id='$flow_id'")->find();
        if (!empty($model)) {
            try {
                $model->real_qty = doubleval($model->real_qty) + doubleval($real_qty);
                if ($model->save()) {
                    return 1;
                } else {
                    return 0;
                }
            } catch (\Exception $ex) {
                return -2;
            }
        } else {
            return -1;
        }
    }


    public function GetOrderStatus($sheet_no) {
        $records = $this->where("real_qty < order_qty and sheet_no='$sheet_no'")->select();
        if (empty($records)) {
            return 2;
        } else {
            $records_all = $this->where("sheet_no='$sheet_no'")->select();
            $records_o = $this->where("real_qty=0 and sheet_no='$sheet_no'")->select();
            if (count($records_all) == count($records_o)) {
                return 0;
            } else {
                return 1;
            }
        }
    }


    public function UpdateDetail($pi_no, $po_no, $branch_no, $supcust_no) {
        $result = 1;
        try {
        	
            $detail_pi = $this->where("sheet_no='$pi_no'")->select();
            $detail_po = $this->where("sheet_no='$po_no'")->select();
            if (empty($detail_pi) || empty($detail_po)) {
                $result = -1;
            }
            foreach ($detail_pi as $k => $v) {
                $record = $this->GetRecord($detail_po, $v["item_no"]);
                $temp_res = 1;
                if (!empty($record)) {
                    $res = $this->UpdateRealQty($record["flow_id"], doubleval($v["order_qty"]));
                    if ($res == 1) {
                        $temp_res = 1;
                    } else {
                        $temp_res = 0;
                    }
                }
                if ($temp_res == 1) {
                	$PosBranchStock=new PosBranchStock();
                	$PcBranchPrice=new PcBranchPrice();
                    $res1 = $PosBranchStock->UpdateStockBySheetNo($v["sheet_no"], $branch_no, $v["item_no"], doubleval($v["order_qty"] + $v["send_qty"]), "+");
                    if ($res1 == TRUE) {
                        $res2 = $PcBranchPrice->UpdateBranchItemPriceForPI($branch_no, $v["item_no"], $record["valid_price"], $supcust_no);
                        if ($res2 != 1) {
                            $result = $res2;
                            break;
                        }
                    } else {
                        $result = 0;
                        break;
                    }
                } else {
                    $result = $res;
                    break;
                }
            }
        } catch (\Exception $ex) {
            $result = -2;
        }
        return $result;
    }


    public function UpdatePODetail($po_no, $branch_no, $supcust_no) {
        $result = 1;
        try {
            $detail_po = $this->where("sheet_no='$po_no'")->select();
            if (empty($detail_po)) {
                $result = -1;
            }
            $PcBranchPrice=new PcBranchPrice();
            foreach ($detail_po as $v) {
                $res2 = $PcBranchPrice->UpdateBranchItemPriceForPI($branch_no, $v["item_no"], $v["valid_price"], $supcust_no);
                if ($res2 != 1) {
                    $result = $res2;
                    break;
                }
            }
        } catch (\Exception $ex) {
            $result = -2;
        }
        return $result;
    }

	//获取非终止订单详细
    public function GetNoneDearDetail($sheet_no) {
        $sql="select".
     		" s.item_no,s.large_qty,(s.order_qty-s.real_qty) as order_qty,s.send_qty,s.sub_amt,s.other1 as memo,s.orgi_price,s.valid_price as item_price,a.sale_price,a.price," .
            " a.item_name,a.item_subno,a.unit_no as item_unit,a.item_size,a.sale_price,a.purchase_spec,a.price,a.purchase_tax,c.sp_company as sp_name,d.stock_qty as item_stock  ".
        	" from ".$this->table." as s ".
        	" LEFT JOIN " .$this->prefix."bd_item_info" . " as a on s.item_no=a.item_no  " .
            " LEFT JOIN " .$this->prefix."pm_sheet_master" . " as b on s.sheet_no=b.sheet_no and b.order_status <> '" . ESheetStatus::CLOSE . "'" .
            " LEFT JOIN " .$this->prefix."sp_infos" . " as c on b.supcust_no=c.sp_no  " .
            " LEFT JOIN " .$this->prefix."pos_branch_stock" . " as d on s.item_no=d.item_no and b.branch_no=d.branch_no";
        $sql.=" where s.sheet_no='$sheet_no' and s.real_qty < s.order_qty";
        
        return $this->GetDataGridDetail($sql);
        
    }

    public $item_stock;


    public function GetDetail($sheet_no) {

        $sql="select".
        		" s.flow_id,s.item_no,s.large_qty,s.order_qty,s.send_qty,s.sub_amt,s.other1 as memo,s.orgi_price,s.valid_price as item_price,a.sale_price,a.price," .
                " a.item_name,a.item_subno,a.unit_no as item_unit,a.item_size,a.sale_price,a.purchase_spec,a.purchase_tax,c.sp_company as sp_name,s.valid_date,d.stock_qty as item_stock ".
        		" from ".$this->table." as s ".
        		" LEFT JOIN " .$this->prefix."bd_item_info" . " as a on s.item_no=a.item_no  " .
        		" LEFT JOIN " .$this->prefix."pm_sheet_master" . " as b on s.sheet_no=b.sheet_no".
        		" LEFT JOIN " .$this->prefix."sp_infos" . " as c on b.supcust_no=c.sp_no  " .
        		" LEFT JOIN " .$this->prefix."pos_branch_stock" . " as d on s.item_no=d.item_no and b.branch_no=d.branch_no";
        $sql.=" where s.sheet_no='$sheet_no'";
        
        return $this->GetDataGridDetail($sql);
        
    }

    public $rowIndex;


    private function GetDataGridDetail($sql) {
        $model=Db::query($sql);
        $result = array();
        $i = 0;
        foreach ($model as $k => $v) {
            $result[$i]["rowIndex"] = $i + 1;
            $result[$i]["flow_id"] = $v["flow_id"];
            $result[$i]["item_no"] = $v["item_no"];
            $result[$i]["sp_name"] = $v["sp_name"];
            $result[$i]["large_qty"] = sprintf("%.2f", $v["large_qty"]);
            $result[$i]["sub_amt"] = sprintf("%.2f", $v["sub_amt"]);
            $result[$i]["order_qty"] = sprintf("%.2f", $v["order_qty"]);
            $result[$i]["item_name"] = $v["item_name"];
            $result[$i]["item_price"] = sprintf("%.2f", $v["item_price"]);
            $result[$i]["item_subno"] = $v["item_subno"];
            $result[$i]["item_size"] = $v["item_size"];
            $result[$i]["item_unit"] = $v["item_unit"];
            $result[$i]["purchase_spec"] = $v["purchase_spec"];
            $result[$i]["purchase_tax"] = $v["purchase_tax"];
            $result[$i]["memo"] = $v["memo"];
            $result[$i]["price"] = sprintf("%.2f", $v["price"]);
            $result[$i]["send_qty"] = sprintf("%.2f", $v["send_qty"]);
            $result[$i]["sale_price"] = sprintf("%.2f", $v["sale_price"]);
            $result[$i]["sale_amt"] = sprintf("%.2f", $v["sale_price"] * $result[$i]["order_qty"]);
            $result[$i]["valid_date"] = $v["valid_date"];
            $result[$i]["item_stock"] = $v["item_stock"];
            $i++;
        }
        $res1 = array();
        $temp = array();
        $res1["total"] = count($model);
        $res1["rows"] = $result;
        return $res1;
    }


    private function GetRecord($arr, $v) {
        $result = NULL;
        foreach ($arr as $k => $vv) {
            if ($vv["item_no"] == $v) {
                return $vv;
            }
        }
        RETURN $result;
    }

    public $unit_no;
    public $stock;
    public $stock_qty;


    public function GetModelsForPos($sheet_no, $trans_no) {
        
        $where="1=1";
        if (!empty($sheet_no)) {
        	$where.=" and s.sheet_no='$sheet_no'";
        }
        if (!empty($trans_no)) {
        	$where.=" and b.trans_no='$trans_no'";
        }
        $res=Db::name($this->name)
        ->alias('s')
        ->field("s.sheet_no,s.item_no,a.item_subno,a.item_name,a.item_size,a.unit_no,s.real_qty,s.other1,c.stock_qty  as stock,d.stock_qty")
        ->join('pm_sheet_master b','s.sheet_no=b.sheet_no',"LEFT")
        ->join('bd_item_info a','s.item_no=a.item_no',"LEFT")
        ->join('pos_branch_stock c','b.d_branch_no=c.branch_no and s.item_no=c.item_no',"LEFT")
        ->join('pos_branch_stock d','b.branch_no=d.branch_no and s.item_no=d.item_no',"LEFT")
        ->where($where)
        ->select();
        
        $result = array();
        foreach ($res as $k => $v) {
            $tt = array();
            $tt["sheet_no"] = $v["sheet_no"];
            $tt["item_no"] = $v["item_no"];
            $tt["item_subno"] = $v["item_subno"];
            $tt["item_name"] = $v["item_name"];
            $tt["item_size"] = $v["item_size"];
            $tt["unit_no"] = $v["unit_no"];
            $tt["real_qty"] = $v["real_qty"];
            $tt["other1"] = $v["other1"];
            $tt["stock"] = $v["stock"];
            $tt["stock_qty"] = $v["stock_qty"];
            array_push($result, $tt);
        }
        return $result;
    }

}
