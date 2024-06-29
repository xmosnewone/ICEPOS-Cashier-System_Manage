<?php
/**
 * im_check_detail表
 */
namespace model;
use think\Db;

class CheckDetail extends BaseModel {

	protected $pk='flow_id';
	protected $name="im_check_detail";

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

    public function Del($sheetno) {
    	$delNum=$this->where("sheet_no='$sheetno'")->delete();
       	return $delNum;
    }

    public function GetDetailItemInfo($sheetno, $itemno) {
    	return $this->where("sheet_no='$sheetno' and item_no='$itemno'")->find();
    }


    public function GetCheckDetailBySheetno($sheetno) {
    	
        $sql = "SELECT d.item_no,i.item_subno,i.price as item_price,i.item_name,i.unit_no as item_unit,i.item_size, d.recheck_qty as check_qty,d.real_qty as item_stock,i.purchase_spec,i.sale_price FROM ".$this->table." AS d".
                " LEFT JOIN ".$this->prefix."bd_item_info"." AS i ON d.item_no=i.item_no".
                " WHERE d.sheet_no='" . $sheetno . "'";
        $result = Db::query($sql);
        $tmp = array();
        $tt = array();
        $rowIndex = 1;
        foreach ($result as $key => $value) {
            $tt["rowIndex"] = $rowIndex;
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
            $tt["sale_amt"] = sprintf("%.2f", $value["check_qty"] * $value["sale_price"]);
            array_push($tmp, $tt);
            $rowIndex ++;
        }
        $result = $tmp;
        return $result;
    }

}
