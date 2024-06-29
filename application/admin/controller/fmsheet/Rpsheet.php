<?php
namespace app\admin\controller\fmsheet;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ESheetTrans;
use model\FmRecpayMaster;
use model\FmRecpayDetail;
use model\WmSheetMaster;
use model\SysSheetNo;
/**
 * 客户结算
 */
class Rpsheet extends Super {
	
	public function index() {
		return $this->fetch ( "fmsheet/index" );
	}
	
	//结算单
	public function sheetDetail() {
		$operDate = date ( 'Y-m-d',$this->_G['time']);
		$orderMan = session ('loginname');
		$sheetno = input("sheetno");
		$approve = "0";
		if (! empty ( $sheetno )) {
			$FmRecpayMaster=new FmRecpayMaster();
			$master = $FmRecpayMaster->getRpMaster ( $sheetno );
			if (! empty ( $master )) {
				$params ["sheetNo"] = $master ["sheet_no"];
				$params ["sheet_no"] = $master ["sheet_no"];
				$params ["operId"] = $master ["oper_id"];
				$params ["orderMan"] = $master ["oper_name"];
				$params ["consumerNo"] = $master ["supcust_no"];
				$params ["consumerName"] = $master ["linkname"];
				$params ["operDate"] = $master ["oper_date"];
				$params ["payWay"] = $master ["pay_way"];
				$params ["payName"] = $master ["pay_name"];
				$params ["confirmMan"] = $master ["loginname"];
				$params ["workDate"] = $master ["work_date"];
				$params ["memo"] = $master ["memo"];
				$approve = $master ["approve_flag"];
				$operDate = $master ["oper_date"];
			}
		}
		$params ['approve'] = $approve;
		$params ['operDate'] = $operDate;
		$params ['orderMan'] = $orderMan;
		$this->assign ( "one", $params );
		return $this->fetch ( "fmsheet/rpsheet" );
	}
	
	//保存客户结算
	public function save() {
		
		$sheet = input ( "sheet/a" );
		$consumer_no = $sheet ["supcust_no"];
		$payWay = $sheet ["pay_way"];
		$operDate = $sheet ["oper_date"];
		$memo = $sheet ["memo"];
		$sheetno = $sheet['sheetno'];
		$oper_id=session("loginname");
		
		$items = input ( "items/a" );
		if (empty ( $items ) || empty ( $sheet )) {
			return [
					'code' => false,
					'msg' => lang ( "invalid_variable" )
			];
		}
			
		$res = 0;
		$FmRecpayMaster = new FmRecpayMaster ();
		$WmSheetMaster=new WmSheetMaster();
		$FmRecpayDetail=new FmRecpayDetail();
		$funcType = "add";
		
		if (! empty ( $sheetno )) {
			$funcType = "update";
		}
		
		$SysSheetNo=new SysSheetNo();
		$RpSheetNo=$funcType == "add" ? $SysSheetNo->CreateSheetNo ( ESheetTrans::RP, $wsm ["branch_no"] ) : $sheetno;
		
		if(!empty($sheetno)){
			$frm = $FmRecpayMaster->getRpMaster ($sheetno);
		}else{
			$frm = new FmRecpayMaster ();
			$frm->sheet_no =$RpSheetNo;
			$frm->trans_no = ESheetTrans::RP;
			$frm->settle_date = date ( "Y-m-d H:s:m", $this->_G['time'] );
			$frm->supcust_no = $consumer_no;
			$frm->sheet_amt = $v ["amountActual"];
			$frm->pay_way = $payWay;
			$frm->approve_flag = 0;
			$frm->oper_date = $operDate;
			$frm->oper_id = $oper_id;
			$frm->branch_no = $wsm ["branch_no"];
			$frm->memo = $memo;
			$frm->save();
		}
		
		foreach ( $items as $k => $v ) {
			if (! empty ( $v ['sheet_no'] ) && intval ( $v ['amountActual'] ) > 0) {
				$wsm = $WmSheetMaster->Get ( $v ["sheet_no"] );
			
					if ($funcType == "add") {
						$frd = new FmRecpayDetail ();
					} else {
						$frd = $FmRecpayDetail->GetRecord ( $frm->sheet_no, $wsm ["sheet_no"] );
					}
					
					$frd->sheet_no = $frm->sheet_no;
					$frd->voucher_no = $wsm ["sheet_no"];
					$frd->sheet_amt = $v ["amountActual"];
					$frd->dis_amt = $v ["amountCoupon"];
					$frd->coin_no = $wsm ["coin_no"];
					if ($frm->addDetails ($frd ) > 0) {
						$res = 1;
					}
				
			} else if (intval ( $v ['amountActual'] ) <= 0) {
					return [
							'code' => false,
							'msg' =>lang("rp_amount_empty")
					];
			}
		}
		if ($res > 0) {
			return [
					'code' => true,
					'msg' => $frm->sheet_no 
			];
		} else {
			return [
					'code' => false,
					'msg' => lang("save_error") 
			];
		}
		
	}
	
	//审核
	public function approve() {

		$sheetno = input ( "sheetno" );
		
		if (empty( $sheetno )) {
			return ['code'=>false,'msg'=>lang("invalid_variable") ];
		}
		
		$FmRecpayMaster=new FmRecpayMaster();
		$res = $FmRecpayMaster->RPApprove ( $sheetno );
		if (is_array ( $res )) {
			return [
					'code' => true,
					'msg' =>  lang("rp_approve_success")
			];
		}
		switch ($res) {
			case - 4 :
				return [
						'code' => false,
						'msg' =>  lang("rp_stock_lack")
				];
			case - 3 :
				return [
						'code' => false,
						'msg' => lang("rp_approve_fail")
				];
			case - 2 :
				return [
						'code' => false,
						'msg' =>  lang("rp_empty_record")
				];
			case - 1 :
				return [
						'code' => false,
						'msg' =>  lang("rp_delete_fail")
				];
			case 0 :
				return [
						'code' => false,
						'msg' => lang("rp_apv_not_chk")
				];
			default :
				return [
						'code' => true,
						'msg' =>  lang("rp_approve_success")
				];
		}
		
	}
	
	// 批量删除
	public function batchDelete() {
		if (! IS_AJAX) {
			return [
					'code' => false,
					'msg' => lang ( "illegal_operate" )
			];
		}
	
		$sheet_no = input ( "sheet_no" );
	
		if (empty ( $sheet_no )) {
			return [
					'code' => false,
					'msg' => lang ( "invalid_variable" )
			];
		}
	
		$arr = strToArray ( $sheet_no );
	
		if (count ( $arr ) <= 0) {
			return [
					'code' => false,
					'msg' => lang ( "invalid_variable" )
			];
		}
	
		$error = [ ];
		foreach ( $arr as $no ) {
			$res = $this->delete ( $no );
			if (! $res ['code']) {
				$error [] = $no . ":" . $res ['msg'];
			}
		}
	
		if (count ( $error ) > 0) {
			$error_no = implode ( ",", $error );
			return [
					'code' => false,
					'msg' => $error_no
			];
		} else {
			return [
					'code' => true,
					'msg' => lang ( "rp_del_success" )
			];
		}
	}
	
	//删除
	public function delete($sheetno) {

		if (empty( $sheetno )) {
			return ['code'=>false,'msg'=>lang("invalid_variable") ];
		}
		
		$FmRecpayMaster=new FmRecpayMaster();
		$res = $FmRecpayMaster->delData ( $sheetno );
		switch ($res) {
			case - 2 :
				return [
						'code' => false,
						'msg' =>  lang("rp_empty_record")
				];
			case - 1 :
				return [
						'code' => false,
						'msg' =>  lang("rp_delete_fail")
				];
			case 0 :
				return [
						'code' => false,
						'msg' =>  lang("rp_del_not_chk")
				];
			default :
				return [
						'code' => true,
						'msg' =>  lang("rp_del_success")
				];
		}
	
	}
}
