<?php
namespace app\admin\controller\imsheet;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ESheetTrans;
use model\ImSheetMaster;
use model\ImSheetDetail;
use model\SysSheetNo;
use model\PosBranchStock;
use think\Db;
/**
 * 报损单
 */
class Josheet extends Super {
	// 报损单列表
	public function index() {
		return $this->fetch ( "imsheet/joindex" );
	}
	
	/**
	 * 报损单详细
	 */
	public function sheetDetail() {
		$operDate = date ( 'Y-m-d H:i', $this->_G ['time'] );
		$orderMan = session ('loginname');
		$sheetno = input ( "sheetno" );
		$approve = "0";
		if (! empty ( $sheetno )) {
			$ImSheetMaster = new ImSheetMaster ();
			$master = $ImSheetMaster->Get ($sheetno);
			$params ["sheetNo"] = $master ["sheet_no"];
			$params ["branchNo"] = $master ["branch_no"];
			$params ["branchName"] = $master ["branch_name"];
			$operDate = $master ["oper_date"];
			$params ["operId"] = $master ["oper_id"];
			$params ["confirmMan"] = $master ["confirm_man"];
			$params ["workDate"] = $master ["work_date"];
			$params ["operName"] = $master ["oper_name"];
			$orderMan = $master ["order_man"];
			$params ["sheetAmt"] = $master ["sheet_amt"];
			$approve = $master ["approve_flag"];
			$params ["memo"] = $master ["memo"];
		}
		$params ['approve'] = $approve;
		$params ['operDate'] = $operDate;
		$params ['orderMan'] = $orderMan;
		$this->assign ( "one", $params );
		return $this->fetch ( "imsheet/josheet" );
	}
	
	// 保存报损单
	public function save() {
		$post = input ( "" );
		$branch_no = $post["branch_no"];
		$oper_date = $post["oper_date"];
		$oper_id = $post["oper_id"];
		$memo = $post["memo"];
		$sheetno = input ( "sheetno" );
		
		$items = input ( "items/a" );
		if (empty ( $items ) || empty ( $post )) {
			return [
					'code' => false,
					'msg' => lang ( "invalid_variable" )
			];
		}
		
		$operFunc = "add";
		
		if (empty ( $sheetno )) {
			$SysSheetNo=new SysSheetNo();
			$sheetno = $SysSheetNo->CreateSheetNo ( ESheetTrans::JO, $branch_no );
			$master = new ImSheetMaster ();
		} else {
			$operFunc = "update";
			$ImSheetMaster=new ImSheetMaster();
			$master=$ImSheetMaster->GetModel($sheetno);
		}
		
		$amount = 0;
		$details = array ();
		foreach ( $items as $k => $v ) {
			if (! empty ( $v ['item_no'] )) {
				$detail = new ImSheetDetail ();
				$detail->item_no = $v ["item_no"];
				$detail->large_qty = isset ( $v ["large_qty"] ) ? $v ["large_qty"] : "0.00";
				$detail->real_qty = isset ( $v ["order_qty"] ) ? $v ["order_qty"] : "0.00";
				$detail->orgi_price = $v ["item_price"];
				$detail->sub_amt = $v ["sub_amt"];
				$detail->other1 = isset ( $v ["other1"] ) ? $v ["other1"] : "";
				$amount = $amount + doubleval ( $v ["sub_amt"] );
				array_push ( $details, $detail );
			}
		}
		if (count ( $details ) == 0) {
			return ['code'=>false,'msg'=>lang("jo_empty_details")];
		}
		$orderman = session ( "loginname" );
		
		$master->sheet_no = $sheetno;
		$master->sheet_amt = $amount;
		$master->db_no = ESheetTrans::MINUS;
		$master->trans_no = ESheetTrans::JO;
		$master->order_man = $orderman;
		$master->branch_no = $branch_no;
		$master->oper_date = $oper_date;
		$master->oper_id = $oper_id;
		$master->memo = $memo;
		
		$ImSheetMaster=new ImSheetMaster();
		$res = $ImSheetMaster->Add ( $master, $details, $operFunc, ESheetTrans::JO );
		if ($res > 0) {
			return ['code'=>true,'msg'=> $master->sheet_no ];
		} else {
			return ['code'=>false,'msg'=> lang("save_error") ];
		}
		
	}
	
	//审核
	public function approve() {
		
		$sheetno = trim ( input ( "sheetno" ) );
		$branchno = trim ( input ( "branch_no" ) );
		$items = input ( "items/a" );
		if (empty ( $sheetno )||empty($branchno)) {
			return [
					'code' => false,
					'msg' => lang ( "invalid_variable" )
			];
		}
		
		$details = array ();
		$amount = 0;
		foreach ( $items as $k => $v ) {
			if (! empty ( $v ['item_no'] )) {
				$detail = new ImSheetDetail ();
				$detail->item_no = $v ["item_no"];
				$detail->large_qty = $v ["large_qty"];
				$detail->real_qty = $v ["real_qty"];
				$detail->orgi_price = $v ["item_price"];
				$detail->sub_amt = $v ["sub_amt"];
				$detail->other1 = $v ["other1"];
				$amount = $amount + doubleval ( $v ["sub_amt"] );
				array_push ( $details, $detail );
			}
		}
		if (count ( $details ) == 0) {
			return ['code'=>false,'msg'=>lang("jo_empty_details")];
		}
		
		$ImSheetMaster=new ImSheetMaster();
		$res = $ImSheetMaster->Approve ( $sheetno, $branchno, $details );
		if (is_array ( $res )) {
			return ['code'=>true,'msg'=>lang("jo_approve_success") ];
		}
		
		$ress = explode ( ":", $res );
		
		switch ($ress ['0']) {
			case - 5001 :
				return ['code'=>false,'msg'=> $ress ['1'] ];
			case - 4 :
				return ['code'=>false,'msg'=>$ress ['1'] . lang("jo_no_stock")];
			case - 3 :
				return ['code'=>false,'msg'=> lang("jo_approve_fail")];
			case - 2 :
				return ['code'=>false,'msg'=>lang("jo_emtpy_record")];
			case - 1 :
				return ['code'=>false,'msg'=>lang("jo_del_record_fail")];
			case 0 :
				return ['code'=>false,'msg'=>lang("jo_apv_not_chk")];
			default :
				return ['code'=>true,'msg'=>lang("jo_approve_success") ];
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
					'msg' => lang ( "jo_del_success" )
			];
		}
	}
	//删除
	public function delete($sheetno) {
		
		if (empty ( $sheetno )) {
			return ['code'=>false,'msg'=> lang("invalid_variable")];
		}
		
		$ImSheetMaster=new ImSheetMaster();
		$res = $ImSheetMaster->Del ( $sheetno );
		switch ($res) {
			case - 2 :
				return ['code'=>false,'msg'=> lang("jo_emtpy_record")];
			case - 1 :
				return ['code'=>false,'msg'=> lang("jo_del_record_fail")];
			case 0 :
				return ['code'=>false,'msg'=> lang("jo_del_not_apv")];
			default :
				return ['code'=>true,'msg'=> lang("jo_del_success")];
		}
		
	}
}
