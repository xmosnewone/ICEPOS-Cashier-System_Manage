<?php
/**
 * im_check_sum表
 */
namespace model;
use think\Db;

class CheckSum extends BaseModel {
	
	protected $pk='flow_id';
	protected $name="im_check_sum";

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


    public function Del( $sheetno, $itemno) {
        try {
        	$delNum=$this->where("sheet_no='$sheetno' and item_no='$itemno'")->delete();
           	return $delNum;
        } catch (\Exception $ex) {
            return FALSE;
        }
    }

    public function DelAll( $sheetno) {
        try {
        	$delNum=$this->where("sheet_no='$sheetno'")->delete();
            if ($delNum !==false) {
                return TRUE;
            } else {
                return FALSE;
            }
        } catch (\Exception $ex) {
            return FALSE;
        }
    }

    public function GetSumItemInfo($sheetno, $itemno) {
    	return $this->where("sheet_no='$sheetno' and item_no='$itemno'")->find();
    }

    public function GetAllSum($sheetno) {
        return $this->where("sheet_no='$sheetno'")->select();
    }

    public function GetCheckDetailBySheetno($sheetno) {
        $sql = "SELECT d.item_no,i.item_subno,i.price as item_price,i.item_name,i.unit_no,d.process_status,d.memo,i.item_size, d.check_qty,d.stock_qty as item_stock,d.balance_qty,i.purchase_spec,i.sale_price FROM ".$this->table." AS d".
                " LEFT JOIN ".$this->prefix."bd_item_info"." AS i ON d.item_no=i.item_no".
                " WHERE d.sheet_no='" . $sheetno . "'";
        $result = Db::query($sql);
        $tmp = array();
        $tt = array(); 
        $rowIndex = 1;
        foreach ($result as $key => $value) {
            foreach ($value as $kk => $vv) {
                switch ($kk) {
                    case "check_qty":
                        $vv = sprintf("%.2f", $vv);
                        break;
                    case "item_stock":
                        $vv = sprintf("%.2f", $vv);
                        break;
                    case "sale_price":
                        $vv = sprintf("%.2f", $vv);
                        break;
                     case "purchase_spec":
                        $vv = sprintf("%.0f", $vv);
                        break;
                    default :
                        
                }
                $tt[$kk] = $vv;
            }
            $tt["rowIndex"] = $rowIndex;
            $tt["sale_amt"] = sprintf("%.2f", $value["check_qty"] * $value["sale_price"]);
            array_push($tmp, $tt);
            $rowIndex ++;
        }
        
        $result = $tmp;
        return $result;
    }

    public function GetValidSumBySheetno($sheetno) {
         return $this->field("branch_no,item_no,check_qty")->where("sheet_no='$sheetno' and process_status='1'")->select();
    }
}
