<?php
//im_stock_target表
namespace model;
use think\Model;

class StockTarget extends BaseModel {

	protected $pk='branch_no';
	protected $name="im_stock_target";

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

    public function Del($branchno, $itemno) {
        try {
        	$delNum=$this->where("branch_no='$branchno' and item_no='$itemno'")->delete();
            if ($delNum>0) {
                return TRUE;
            } else {
                return FALSE;
            }
        } catch (\Exception $ex) {
            return FALSE;
        }
    }

    public function GetStockTargetItem($branchno, $itemno) {
        if (empty($branchno)) {
            return FALSE;
        }
        if (empty($itemno)) {
            return FALSE;
        }
        $branchno= trim($branchno);
        $itemno=trim($itemno);
        $model = $this->where("branch_no='$branchno' and item_no='$itemno'")->find();
        return $model;
    }

}
