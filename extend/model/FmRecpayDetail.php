<?php
/**
 * fm_recpay_detail客户结算表
 */
namespace model;
use think\Db;

class FmRecpayDetail extends BaseModel {

	protected $pk='flow_id';
	protected $name="fm_recpay_detail";
	
    public function addData($model) {
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

    public function delData($sheetno) {
    	$delNum=$this->where("sheet_no='$sheetno'")->delete();
        if ($delNum> 0) {
            return true;
        }
        return false;
    }

    public function GetRecord($sheetno, $voucherno){
        return $this->where("sheet_no='$sheetno' and voucher_no='$voucherno'")->find();
    }

    public function getPayRecordBySheetNo($sheetno){
        return $one=Db::name($this->name)
			        ->alias('d')
			        ->field("d.sheet_no, sum(d.sheet_amt) as sheet_amt, sum(d.dis_amt) as dis_amt")
			        ->join('fm_recpay_master m','m.sheet_no=d.sheet_no',"LEFT")
			        ->where("d.voucher_no='$sheetno' and m.approve_flag=1")
			        ->find();
    }

    //080
    public function getPayRecordBySupcustNo($supcustno){
        return $one=$this
			        ->alias('d')
			        ->field("d.sheet_no,d.voucher_no, sum(d.sheet_amt) as sheet_amt, sum(d.dis_amt) as dis_amt")
			        ->join('fm_recpay_master m','m.sheet_no=d.sheet_no',"LEFT")
			        ->where("m.supcust_no='$supcustno' and m.approve_flag=1")
			        ->group("d.voucher_no")
			        ->find();
    }
}
