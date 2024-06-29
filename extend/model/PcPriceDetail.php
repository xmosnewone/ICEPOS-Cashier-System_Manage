<?php
//pc_price_flow_detail表
namespace model;
use think\Db;
use model\ImSheetDetail;

class PcPriceDetail extends BaseModel {

    
    protected $pk='flow_id';
    protected $name="pc_price_flow_detail";

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
            $res = lang("pcprice_sheetno_empty");
        } else if (empty($model->item_no)) {
            $res = lang("pcprice_itemno_empty");
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


    public function GetDearDetail($sheet_no) {
       
        $where="s.sheet_no='$sheet_no' and s.order_qty!=s.real_qty";
        $temp=Db::name($this->name)
        ->alias('s')
        ->field("s.item_no,s.large_qty,(s.real_qty-s.order_qty) as real_qty,(s.real_qty-s.order_qty) as real_qty1,s.orgi_price as item_price,s.sub_amt,s.other1 as memo,s.tax," .
                "b.item_subno,b.item_name, b.unit_no,b.item_size,b.purchase_spec,b.purchase_tax,b.sale_price")
        ->join('bd_item_info b','s.item_no=b.item_no',"LEFT")
        ->where($where)
        ->select();
        
        $result = array();
        $mod = new ImSheetDetail();
        $mod->item_no = "总计";
        $mod->large_qty = 0;
        $mod->real_qty = 0;
        $mod->sale_amt = 0;
        $mod->sub_amt = 0;
        $i = 0;
        foreach ($temp as $k => $v) {
            foreach ($v as $kk => $vv) {
                $result[$i][$kk] = $vv;
                switch ($kk) {
                    case "large_qty":
                        $result[$i][$kk] = sprintf("%.2f", $vv);
                        $mod->large_qty = $mod->large_qty + doubleval($vv);
                        break;
                    case "real_qty":
                        $result[$i][$kk] = sprintf("%.2f", $vv);
                        $mod->real_qty = $mod->real_qty + doubleval($vv);
                        break;
                    case "sub_amt":
                        $result[$i][$kk] = sprintf("%.2f", $vv);
                        $mod->sub_amt = $mod->sub_amt + doubleval($vv);
                        break;
                }
            }
            $result[$i]["item_name"] = $v["item_name"];
            $result[$i]["real_qty1"] = sprintf("%.2f", $v["real_qty1"]);
            $result[$i]["item_price"] = sprintf("%.2f", $v["item_price"]);
            $result[$i]["item_subno"] = $v["item_subno"];
            $result[$i]["item_size"] = $v["item_size"];
            $result[$i]["item_unit"] = $v["item_unit"];
            $result[$i]["purchase_spec"] = $v["purchase_spec"];
            $result[$i]["purchase_tax"] = $v["purchase_tax"];
            $result[$i]["memo"] = $v["memo"];
            $result[$i]["sale_price"] = sprintf("%.2f", $v["sale_price"]);
            $result[$i]["sale_amt"] = sprintf("%.2f", $v["sale_price"] * $result[$i]["real_qty"]);
            $mod->sale_amt = $mod->sale_amt + doubleval($result[$i]["sale_amt"]);
            $i++;
        }
        $res1 = array();
        $temp = array();
        array_push($temp, $mod);
        $res1["total"] = $i;
        $res1["rows"] = $result;
        $res1["footer"] = $temp;
        return $res1;
    }

    public function GetMiDetail($mino) {
        
        $where="p.sheet_no='$mino'";
        $model=Db::name($this->name)
        ->alias('p')
        ->field("p.item_no,p.large_qty,p.real_qty,p.real_qty as real_qty1,p.orgi_price as item_price,p.sub_amt,p.other1 as memo,p.tax," .
                "b.item_subno,b.item_name,b.unit_no as item_unit,b.item_size,b.purchase_spec,b.purchase_tax,b.sale_price")
       	->join('bd_item_info b','p.item_no=b.item_no',"LEFT")
        ->where($where)
        ->select();
        
        $result = array();
        $i = 0;
        foreach ($model as $k => $v) {
            foreach ($v as $kk => $vv) {
                $result[$i][$kk] = $vv;
                switch ($kk) {
                    case "large_qty":
                        $result[$i][$kk] = sprintf("%.2f", $vv);
                        $mod->large_qty = $mod->large_qty + doubleval($vv);
                        break;
                    case "real_qty":
                        $result[$i][$kk] = sprintf("%.2f", $vv);
                        $mod->real_qty = $mod->order_qty + doubleval($vv);
                        break;
                    case "sub_amt":
                        $result[$i][$kk] = sprintf("%.2f", $vv);
                        $mod->sub_amt = $mod->sub_amt + doubleval($vv);
                        break;
                }
            }
            $result[$i]["item_name"] = $v["item_name"];
            $result[$i]["real_qty1"] = sprintf("%.2f", $v["real_qty1"]);
            $result[$i]["item_price"] = sprintf("%.2f", $v["item_price"]);
            $result[$i]["item_subno"] = $v["item_subno"];
            $result[$i]["item_size"] = $v["item_size"];
            $result[$i]["item_unit"] = $v["item_unit"];
            $result[$i]["purchase_spec"] = $v["purchase_spec"];
            $result[$i]["purchase_tax"] = $v["purchase_tax"];
            $result[$i]["memo"] = $v["memo"];
            $result[$i]["sale_price"] = sprintf("%.2f", $v["sale_price"]);
            $result[$i]["sale_amt"] = sprintf("%.2f", $v["sale_price"] * $result[$i]["real_qty"]);
            $mod->sale_amt = $mod->sale_amt + doubleval($result[$i]["sale_amt"]);
            $i++;
        }
        $res1 = array();
        $temp = array();
        array_push($temp, $mod);
        $res1["total"] = $i;
        $res1["rows"] = $result;
        $res1["footer"] = $temp;
        return $res1;
    }



    public function GetSheetDetails($sheetno) {
        
        $where="p.sheet_no='$sheetno'";
        $model=Db::name($this->name)
        ->alias('p')
        ->field("p.item_no,p.old_price,p.old_price1,p.old_price2,p.old_price3,p.old_price4,p.new_price,p.new_price1,p.new_price2,p.new_price3,p.new_price4," .
                "b.item_subno,b.item_name,b.unit_no as unit_no")
        ->join('bd_item_info b','p.item_no=b.item_no',"LEFT")
        ->where($where)
        ->select();
        return $model;
    }

    public function DeleteMi($sheet_no) {
    	$delNum=$this->where("sheet_no='$sheet_no'")->delete();
        if ($delNum > 0) {
            return TRUE;
        }
        return FALSE;
    }


    public function UpdateMi($sheetno) {
        $isok = TRUE;
        try {
            if (!empty($sheetno)) {
                $details_mi = $this->where("sheet_no='$sheetno'")->select();
                if (empty($details_mi) || empty($details_mo)) {
                    $isok = FALSE;
                } else {
                    foreach ($details_mi as $k => $v) {

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
