<?php
//im_sheet_detail表
namespace model;
use think\Db;
use model\PosBranchStock;
use model\PcBranchPrice;

class ImSheetDetail extends BaseModel {
    
    protected $pk='flow_id';
    protected $name="im_sheet_detail";

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
            $res = lang("st_md_empty_sheet_no");
        } else if (empty($model->item_no)) {
            $res = lang("st_md_empty_itemno");
        } else if (!is_numeric($model->large_qty)) {
            $res = lang("st_md_wrong_pack");
        } else if (!is_numeric($model->orgi_price)) {
            $res = lang("st_md_wrong_price");
        } else if (!is_numeric($model->sub_amt)) {
            $res = lang("st_md_wrong_itemprice");
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

    public $rowIndex;


    public function GetDearDetail($sheet_no) {
        
        $temp=Db::table($this->table)
        ->alias('s')
        ->field("s.item_no,s.large_qty,(s.real_qty-s.order_qty) as real_qty,(s.real_qty-s.order_qty) as real_qty1,s.orgi_price as item_price,s.sub_amt,s.other1 as memo,s.tax," .
                "b.item_subno,b.item_name,b.unit_no as item_unit,b.item_size,b.purchase_spec,b.purchase_tax,b.sale_price")
        ->join('bd_item_info b','s.item_no=b.item_no',"LEFT")
        ->where("s.sheet_no='$sheet_no' and (s.order_qty!=s.real_qty or s.real_qty=0.000)")
        ->select();
       
        $result = array();
        $i = 0;
        foreach ($temp as $k => $v) {
            foreach ($v as $kk => $vv) {
                $result[$i][$kk] = $vv;
                switch ($kk) {
                    case "large_qty":
                        $result[$i][$kk] = sprintf("%.2f", $vv);

                        break;
                    case "real_qty":
                        $result[$i][$kk] = sprintf("%.2f", $vv);


                        break;
                    case "sub_amt":
                        $result[$i][$kk] = sprintf("%.2f", $vv);

                        break;
                }
            }
            $result[$i]["rowIndex"] = $i + 1;
            $result[$i]["item_name"] = $v["item_name"];
            $result[$i]["real_qty1"] = sprintf("%.2f", $v["real_qty1"]);
            $result[$i]["item_price"] = sprintf("%.2f", $v["item_price"]);
            $result[$i]["item_subno"] = $v["item_subno"];
            $result[$i]["item_size"] = $v["item_size"];
            $result[$i]["order_qty"] = $v["real_qty"];
            $result[$i]["item_unit"] = $v["item_unit"];
            $result[$i]["purchase_spec"] = $v["purchase_spec"];
            $result[$i]["purchase_tax"] = $v["purchase_tax"];
            $result[$i]["memo"] = $v["memo"];
            $result[$i]["sale_price"] = sprintf("%.2f", $v["sale_price"]);
            $result[$i]["sale_amt"] = sprintf("%.2f", $v["sale_price"] * $result[$i]["real_qty"]);
            $i++;
        }
        $res1 = array();
        $res1["total"] = $i;
        $res1["rows"] = $result;
        return $res1;
    }


    public function GetDearDetailForPos($sheet_no) {

        $temp=Db::table($this->table)
        ->alias('s')
        ->field("s.item_no,s.large_qty,(s.real_qty-s.order_qty) as real_qty,s.other1 as memo," .
                "b.item_name,s.orgi_price as item_price,b.purchase_spec")
        ->join('bd_item_info b','s.item_no=b.item_no',"LEFT")
        ->where("s.sheet_no='$sheet_no' and s.order_qty!=s.real_qty")
        ->select();
       
        $result = array();
        $i = 0;
        foreach ($temp as $k => $v) {
            $result[$i]["item_no"] = $v["item_no"];
            $result[$i]["item_name"] = $v["item_name"];
            $result[$i]["item_price"] = $v["item_price"];
            $result[$i]["purchase_spec"] = $v["purchase_spec"];
            $result[$i]["large_qty"] = GobalFunc::FormatMoneyDisplay($v["large_qty"]);
            $result[$i]["real_qty"] = GobalFunc::FormatMoneyDisplay($v["real_qty"]);
            $result[$i]["memo"] = $v["memo"];
            $i++;
        }
        return $result;
    }

    public function GetMiDetail($mino) {
        
        $model=Db::table($this->table)
        ->alias('p')
        ->field("p.item_no,p.order_qty,p.large_qty,p.real_qty,p.real_qty as real_qty1,p.orgi_price as item_price,p.sub_amt,p.other1 as memo,p.tax," .
                "b.item_subno,b.item_name,b.unit_no as item_unit,b.item_size,b.purchase_spec,b.purchase_tax,b.sale_price")
        ->join('bd_item_info b','p.item_no=b.item_no',"LEFT")
        ->where("p.sheet_no='$mino'")
        ->select();
        
        $result = array();
        $i = 0;
        foreach ($model as $k => $v) {
            foreach ($v as $kk => $vv) {
                $result[$i][$kk] = $vv;
                switch ($kk) {
                    case "large_qty":
                        $result[$i][$kk] = sprintf("%.2f", $vv);
                        break;
                    case "real_qty":
                        $result[$i][$kk] = sprintf("%.2f", $vv);
                        break;
                    case "sub_amt":
                        $result[$i][$kk] = sprintf("%.2f", $vv);
                        break;
                }
            }
            $result[$i]["item_name"] = $v["item_name"];
            $result[$i]["real_qty1"] = sprintf("%.2f", $v["real_qty1"]);
            $result[$i]["item_price"] = sprintf("%.2f", $v["item_price"]);
            $result[$i]["item_subno"] = $v["item_subno"];
            $result[$i]["item_size"] = $v["item_size"];
            $result[$i]["item_unit"] = $v["item_unit"];
            $result[$i]["order_qty"] = floatval($v["order_qty"]);
            $result[$i]["purchase_spec"] = $v["purchase_spec"];
            $result[$i]["purchase_tax"] = $v["purchase_tax"];
            $result[$i]["memo"] = $v["memo"];
            $result[$i]["sale_price"] = sprintf("%.2f", $v["sale_price"]);
            $result[$i]["sale_amt"] = sprintf("%.2f", $v["sale_price"] * $result[$i]["real_qty"]);
            $i++;
        }
        $res1 = array();
        $res1["total"] = $i;
        $res1["rows"] = $result;
        return $res1;
    }


    public function UpdateMOOrderqty($flow_id, $real_qty) {
        $model = $this->where("flow_id='$flow_id'")->find();
        if (empty($model)) {
            return FALSE;
        } else {
            try {
                $order_qty = $model->order_qty;
                $up_order_qty = doubleval($model->order_qty) + doubleval($real_qty);
                if ($up_order_qty > $model->real_qty) {
                    return FALSE;
                }
                $model->order_qty = $up_order_qty;
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


    public function GetMoStatus($sheet_no) {
        $model = $this->where("order_qty!=real_qty and sheet_no='$sheet_no'")->select();
        if (empty($model)||count($model)<=0) {
            return 2;
        } else {
            $modelList = $this->where("sheet_no='$sheet_no'")->select();
            $modelList1 = $this->where("order_qty = 0 and sheet_no='$sheet_no'")->select();
            if (count($modelList1) == count($modelList)) {
                return 0;
            } else {
                return 1;
            }
        }
    }

    public function GetSheetDetails($sheetno) {
        return $list=Db::name($this->name)
        ->alias('p')
        ->field("p.item_no,p.large_qty,p.real_qty,p.order_qty,p.real_qty as real_qty1,p.orgi_price as item_price,p.sub_amt,p.other1 as memo,p.tax," .
                "b.item_subno,b.item_name,b.unit_no as item_unit,b.item_size,b.purchase_spec,b.purchase_tax,b.sale_price,s.branch_no,s.stock_qty")
        ->join('bd_item_info b','p.item_no=b.item_no ',"LEFT")
        ->join('pos_branch_stock s','p.item_no=s.item_no ',"LEFT")
        ->where("p.sheet_no='$sheetno'")
        ->select();
    }

    public function AddMi($model) {
        try {
            $res = $this->CheckMi($model);
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


    private function CheckMi($model) {
        $res = 1;
        if (empty($model->sheet_no)) {
            $res =lang("st_md_empty_sheet_no");
        } else if (empty($model->item_no)) {
            $res = lang("st_md_empty_itemno");
        } else if (!is_numeric($model->real_qty)) {
            $res =lang("st_md_wrong_order");
        } else if (!is_numeric($model->large_qty)) {
            $res =lang("st_md_wrong_pack");
        } else if (!is_numeric($model->orgi_price)) {
            $res = lang("st_md_wrong_price");
        } else if (!is_numeric($model->sub_amt)) {
            $res = lang("st_md_wrong_itemprice");
        }
        return $res;
    }

    public function DeleteMi($sheet_no) {
    	$delNum=$this->where("sheet_no='$sheet_no'")->delete();
        if ($delNum> 0) {
            return TRUE;
        }
        return FALSE;
    }

    public function UpdateMi($mino, $mono, $d_branch_no) {
        $isok = TRUE;
        try {
            if (!empty($mino) && !empty($mono)) {
                $details_mi = $this->where("sheet_no='$mino'")->select();
                $details_mo = $this->where("sheet_no='$mono'")->select();
                if (empty($details_mi)||count($details_mi)<=0 || empty($details_mo)||count($details_mo)<=0) {
                    $isok = FALSE;
                } else {
                	$posBranchStock=new PosBranchStock();
                	$posBranchPrice=new PcBranchPrice();
                    foreach ($details_mi as  $v) {
                        $vv = $this->GetRecord($details_mo, $v["item_no"]);
                        if (!empty($vv)) {
                            if ($this->UpdateMOOrderqty($vv["flow_id"], $v["real_qty"]) == FALSE) {
                                $isok = FALSE;
                            } else {
                                if ($posBranchStock->UpdateStockBySheetNo($v["sheet_no"],$d_branch_no, $v["item_no"], $v["real_qty"], "+") == FALSE) {
                                    $isok = FALSE;
                                } else {
                                    if ($posBranchPrice->SyncBranchItemPrice($d_branch_no, $v["item_no"], $v["orgi_price"]) <= 0) {
                                        $isok = FALSE;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            $isok = FALSE;
        }
        return $isok;
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

}
