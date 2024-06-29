<?php
namespace app\admin\controller\fmsheet;
use app\admin\controller\Super;
use model\FmRecpayMaster;
use model\FmRecpayDetail;
use model\WmSheetMaster;
use think\Db;
/**
 * 客户结算数据
 */
class Jsondata extends Super {
	
	public function getFmRecpaySheetNoApproveList() {
 		$page =input('page') ? intval(input('page')) : 1;
        $rows =input('limit') ? intval(input('limit')) : 10;
		$start = input ( "start" );
		$end = input ( "end" );
		$sheet_no = input ( "no" );
		$approve_flag = input ( "approve_flag" );
		$oper_id = input ( "oper_id" );
		$transno = input ( "transno" );
		$supcustno = input ( "clients_no" );
		$amount = input ( "amount" );
		$FmRecpayMaster=new FmRecpayMaster();
		$result=$FmRecpayMaster->GetPager ( $rows, $page, $start, $end, $sheet_no, $supcustno, $approve_flag, $oper_id, $transno,$amount) ;
		return listJson(0,lang("success_data"),$result['total'], $result['rows']);
	}
	
	public function getApproveSOSheet() {
 		
		$WmSheetMaster=new WmSheetMaster();
		$FmRecpayDetail=new FmRecpayDetail();
		$FmRecpayMaster=new FmRecpayMaster();
		$supcustno = input ( "supcustno" );
		$sql = "SELECT m.sheet_no,m.sheet_amt as amountReceivable,sum(d.dis_amt) as dis_amt
				,sum(f.sheet_amt) as amountReceived FROM " . $WmSheetMaster->tableName () . " as m " 
				. "LEFT JOIN " . $FmRecpayDetail->tableName () . " as d ON m.sheet_no=d.voucher_no " 
				. "LEFT JOIN " . $FmRecpayMaster->tableName () . " as f ON f.sheet_no=d.sheet_no " 
				. "WHERE m.trans_no='SO' and m.approve_flag=1 and m.supcust_no='" . $supcustno 
				. "' GROUP BY m.sheet_no ";
		$model = Db::query ( $sql );
		$tmpModel = array ();
		if (count ( $model ) > 0) {
			
			$modelMaster = $FmRecpayMaster->verifyApprove ( $supcustno );
			if (count ( $modelMaster ) > 0) {
				$array ["code"] = '-1';
				$array ["msg"] = str_replace("sheet_no", $modelMaster->sheet_no, lang("rp_must_chk"));
				return $array;
			}
			$rowIndex = 1;
			foreach ( $model as $key => $value ) {
				$value ["amountReceivable"] = $this->getRound2Float ( $value ["amountReceivable"] );
				$value ["amountReceived"] = $this->getRound2Float ( $value ["amountReceived"] + $value ["dis_amt"] );
				$value ["amountOutstanding"] = $this->getRound2Float ( $value ["amountReceivable"] - $value ["amountReceived"] );
				$value ["amountActual"] = $this->getRound2Float ( "0.00" );
				$value ["amountCoupon"] = $this->getRound2Float ( "0.00" );
				$value ["memo"] = "";
				$value ["rowIndex"] = $rowIndex ++;
				$tmpModel [] = $value;
			}
		}

		$total=count($tmpModel);
		if($total>0){
			$code=true;
		}else{
			$code=false;
		}
		return listJson($code,lang("success_data"),$total, $tmpModel); 
	}
	
	public function getRPSheetForSOSheet() {
		
		$WmSheetMaster=new WmSheetMaster();
		$FmRecpayDetail=new FmRecpayDetail();
		$FmRecpayMaster=new FmRecpayMaster();
		
		$sheetno = input ( "sheetno" );
		$sql = "SELECT m.memo, d.sheet_no,d.sheet_amt,d.voucher_no, w.sheet_amt as amountReceivable
				,d.dis_amt AS dis_amt, d.sheet_amt as amountReceived FROM " 
				. $FmRecpayDetail->tableName () . " as d " . "LEFT JOIN " 
				. $WmSheetMaster->tableName () . " as w ON w.sheet_no=d.voucher_no " 
				. "LEFT JOIN " . $FmRecpayMaster->tableName () 
				. " as m ON m.sheet_no=d.sheet_no " 
				. "WHERE d.sheet_no='$sheetno'";
		
		$model = Db::query ( $sql );
		$tmpModel = array ();
		$tmpValue = array ();
		if (count ( $model ) > 0) {
			$disAmt = 0;
			$amountReceived = 0;
			$amountReceivable = 0;
			$voucherno = "";
			$amountActual = 0;
			$amountCoupon = 0;
			$memo = "";
			$rowInex = 1;
			foreach ( $model as $key => $value ) {
				$disAmt += $value ["dis_amt"];
				$amountReceived += $value ["amountReceived"];
				$amountReceivable = $this->getRound2Float ( $value ["amountReceivable"] );
				$voucherno = $value ["voucher_no"];
				if ($value ["sheet_no"] == $sheetno) {
					$amountActual = $this->getRound2Float ( $value ["sheet_amt"] );
					$amountCoupon = $this->getRound2Float ( $value ["dis_amt"] );
					$memo = $this->getRound2Float ( $value ["memo"] );
				}
				$tmpValue ["rowIndex"] = $rowInex;
				$tmpValue ["amountReceivable"] = $amountReceivable;
				$tmpValue ["amountReceived"] = $this->getRound2Float ( $amountReceived + $disAmt );
				$tmpValue ["amountOutstanding"] = $this->getRound2Float ( $amountReceivable - $tmpValue ["amountReceived"] );
				$tmpValue ["sheet_no"] = $voucherno;
				$tmpValue ["amountActual"] = $amountActual;
				$tmpValue ["amountCoupon"] = $amountCoupon;
				$tmpValue ["memo"] = $memo;
				array_push ( $tmpModel, $tmpValue );
				$rowInex ++;
			}
		}
		$result ["rows"] = $tmpModel;
		return listJson(0,lang("success_data"),count($tmpModel), $result['rows']); 
	}
	
	private function getRound2Float($num) {
		return sprintf ( "%.2f", $num );
	}
}
