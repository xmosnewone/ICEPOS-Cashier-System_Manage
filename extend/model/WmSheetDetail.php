<?php
//wm_sheet_detail表
namespace model;
use think\Db;

class WmSheetDetail extends BaseModel {

    protected $pk='flow_id';
    protected $name="wm_sheet_detail";

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
            $res = lang("ws_md_empty_sheet_no");
        } else if (empty($model->item_no)) {
            $res = lang("ws_md_empty_itemno");
        } else if (!is_numeric($model->large_qty)) {
            $res = lang("ws_md_wrong_pack");
        } else if (!is_numeric($model->orgi_price)) {
            $res = lang("ws_md_wrong_amt");
        } else if (!is_numeric($model->sub_amt)) {
            $res = lang("ws_md_wrong_itemprice");
        }
        return $res;
    }
    
    public function Del($sheet_no) {
    	$delNum=$this->where("sheet_no='$sheet_no'")->delete();
        if ($delNum> 0) {
            return true;
        }
        return false;
    }
   
    public function GetSheetDetail($sheetno){
       
        $list = $this
        ->alias("p")
        ->field("p.item_no,p.large_qty,p.real_qty,p.real_qty as real_qty1,p.orgi_price as item_price,p.sub_amt,p.memo,p.other1,p.tax," .
                "b.item_subno,b.item_name,b.unit_no as item_unit,b.item_size,b.purchase_spec,b.purchase_tax,b.sale_price")
        ->join("bd_item_info b","p.item_no=b.item_no","LEFT")
        ->where(["p.sheet_no"=>$sheetno])
        ->select();
        
        return $list;
    }
   

}
