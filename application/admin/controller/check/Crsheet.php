<?php
namespace app\admin\controller\check;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ESheetTrans;
use model\PosBranch;
use model\SysSheetNo;
use model\CheckInit;
use model\CheckMaster;
use model\CheckDetail;
use model\CheckSum;
use model\PosBranchStock;
use model\BdItemCombsplit;
use model\Item_info;
/**
 * 盘点单
 *
 */
class Crsheet extends Super {
	
	//盘点单列表
	public function index() {
		return $this->fetch ( "check/crindex" );
	}
	
	//差异处理列表
	public function dfindex() {
		return $this->fetch ( "check/dfindex" );
	}
	
	// 库存盘点列表
	public function sheetDetail() {
		$sheetno = input ( "sheetno" );
		$orderMan=session("loginname");
		$isDif=input("isdiff");
		if (!empty ( $sheetno )) {
			$CheckMaster = new CheckMaster ();
			$CheckInit = new CheckInit ();
			if($isDif){
				$checkMaster = $CheckMaster->GetArraySheetPdno( $sheetno );
			}else{
				$checkMaster = $CheckMaster->GetArraySheet ( $sheetno );
			}
			$params ["sheetno"] = $checkMaster ["sheet_no"];
			$params ["checkno"] = $checkMaster ["check_no"];
			$params ["oper_range"] = $checkMaster ["oper_range"];
			$params ["operRange"] = $CheckInit->GetChectRange ( $checkMaster ["oper_range"] );
			$params ["branchNo"] = $checkMaster ["branch_no"];
			$params ["branchName"] = $checkMaster ["branch_name"];
			$params ["operDate"] = $checkMaster ["oper_date"];
			$params ["operId"] = $checkMaster ["oper_id"];
			$params ["memo"] = $checkMaster ["memo"];
			$params ["approve"] = $checkMaster ["approve_flag"];
			$orderMan=$checkMaster['oper_name'];
		}
		
		$this->assign ( "one", $params );
		$this->assign ( "orderMan", $orderMan );
		if($isDif){
			return $this->fetch ( "check/difsheet" );
		}else{
			return $this->fetch ( "check/crsheet" );
		}
		
	}
	
	// 保存库存盘点
	public function save() {
		if (! IS_AJAX) {
			return [ 
					'code' => false,
					'msg' => lang ( "illegal_operate" ) 
			];
		}
		
		$sheetno = input ( "sheetno" ); // 库存盘点单号
		$pdSheetno = input ( "pdsheetno" ); // 盘点申请批号
		$memo = input ( "memo" ); // 备注
		$items = input ( "items/a" );
		$time=$this->_G['time'];
		
		if (empty ( $items ) || empty ( $pdSheetno )) {
			return [ 
					'code' => false,
					'msg' => lang ( "illegal_data" ) 
			];
		}
		
		$CheckInit = new CheckInit ();
		$checkInitModel = $CheckInit->GetNotUsedPDSheet ($pdSheetno);
		if (empty ( $checkInitModel )) {
			return [ 
					'code' => false,
					'msg' => lang ( "pd_sheetno_notuse" ) 
			];
		}
		
		$Item_info=new Item_info();
		$BdItemCombsplit=new BdItemCombsplit();
		$PosBranchStock=new PosBranchStock();
		$CheckDetail=new CheckDetail();
		if(!empty($sheetno)){
			//清空盘点单商品
			$CheckDetail->Del($sheetno);
		}
		
		$amount = 0;
		$details = array ();
		foreach ( $items as $k => $v ) {
			if (! empty ( $v ['item_no'] )) {
				if ($Item_info->IsItemBundle ( trim ( $v ['item_no'] ) )) {
					$model = $BdItemCombsplit->GetCombDetail ( trim ( $v ['item_no'] ) );
					$checkQty = $v ["check_qty"];
					foreach ( $model as $key => $value ) {
						$stockModel = $PosBranchStock->GetStockByBraItem ( $checkInitModel->branch_no, $value ['item_no'] );
						$detail = new CheckDetail ();
						$detail->sheet_no = $checkInitModel->sheet_no;
						$detail->item_no = $value ['item_no'];
						$detail->check_date = date ( DATETIME_FORMAT, $time);
						$detail->in_price = $value ["price"];
						$detail->sale_price = $value ["sale_price"];
                        $detail->memo = $v ["memo"];
						$detail->real_qty = $stockModel->stock_qty;
						$detail->recheck_qty = $checkQty * $value ["item_qty"];
						$amount = $amount + doubleval ( $value ["sale_price"] * $checkQty );
						array_push ( $details, $detail );
					}
				} else {
						$detail = new CheckDetail ();
						$detail->sheet_no = $checkInitModel->sheet_no;
						$detail->item_no = $v ['item_no'];
						$detail->check_date = date ( DATETIME_FORMAT, $time);
						$detail->in_price = $v ["item_price"];
						$detail->sale_price = $v ["sale_price"];
						$detail->real_qty = $v ["item_stock"];
						$detail->recheck_qty = $v ["check_qty"];
                        $detail->memo = $v ["memo"];
						$amount = $amount + doubleval ( $v ["sale_price"] * $v ["check_qty"] );
						array_push ( $details, $detail );
				}
			}
		}
		
		if (count ( $details ) == 0) {
			return [
					'code' => false,
					'msg' => lang ( "illegal_data" )
			];
		}
		
		if (empty ( $sheetno )) {
			$SysSheetNo=new SysSheetNo();
			$checkMaster = new CheckMaster ();
			$checkMaster->sheet_no = $SysSheetNo->CreateSheetNo ( ESheetTrans::CR, $checkInitModel->branch_no );
			$checkMaster->check_no = $checkInitModel->sheet_no;
			$checkMaster->trans_no = ESheetTrans::CR;
			$checkMaster->branch_no = $checkInitModel->branch_no;
			$checkMaster->oper_range = $checkInitModel->oper_range;
			$checkMaster->dup_process = 1;
			$checkMaster->oper_id = session ( "loginname" );
			$checkMaster->oper_date = date ( DATETIME_FORMAT, $time);
			$checkMaster->approve_flag = 0;
			$checkMaster->check_cls = "";
			
			
		} else {
			$checkMaster = new CheckMaster ();
			$cmModel = $checkMaster->GetSheet ( $sheetno );
			if (empty ( $cmModel )) {
				return [
						'code' => false,
						'msg' => lang ( "pd_master_notexist" )
				];
			} else {
				$checkMaster = $cmModel;
			}
		}
				$checkMaster->memo = $memo;
				$checkMaster->sheet_amt = $amount;
		
		$res = 	$checkMaster->Add ( $checkMaster, $details, $checkInitModel );
		if ($res > 0) {
			return [
					'code' => true,
					'msg' => lang ( "pd_sheet_success" )
			];
		} else {
			return [
					'code' => false,
					'msg' => lang ( "pd_sheet_fail" )
			];
		}
		
	}
	
	//保存
	public function dfsave() {
	
		$sheetno = input("sheetno");
		$memo = input("txtMemo");
	
		$items = input ( "items/a" );
	
		if (empty ( $items ) || empty ( $sheetno )) {
			return [
					'code' => false,
					'msg' => lang ( "illegal_data" )
			];
		}
		
		if (!empty($sheetno)) {
			$CheckInit=new CheckInit();
			$CheckSum=new CheckSum();
			$checkInitModel = $CheckInit->GetNotUsedPDSheet($sheetno);
			if (empty($checkInitModel)) {
				return [
						'code' => false,
						'msg' => lang ( "pd_sheetno_notuse" )
				];
			}
	
			$details = array();

			foreach ($items as $k => $v) {
				if (!empty($v['item_no'])) {
					$detail = $CheckSum->GetSumItemInfo($checkInitModel->sheet_no, $v["item_no"]);
					$detail->memo = empty($v['memo']) ? "" : $v['memo'];
					array_push($details, $detail);
				}
			}

			$checkMaster = $CheckInit->GetSheet($sheetno);
			$res = $checkMaster->UpdateSheetProcessStatus($checkMaster, $details);
			if ($res > 0) {
				return [
						'code' => true,
						'msg' => $checkMaster->sheet_no
				];
			} else {
				return [
						'code' => false,
						'msg' =>  lang("save_error")
				];
			}
			
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
					'msg' => lang ( "pd_sheet_delete_ok" )
			];
		}
	}
	
	//删除盘点单
	public function delete($sheetno) {
		if (! IS_AJAX) {
			 return ['code'=>false,'msg'=>lang("illegal_operate")];
		}

		$CheckMaster=new CheckMaster();
		$res = $CheckMaster->Del( $sheetno );
		
		switch ($res) {
			case - 5 :
				return ['code'=>false,'msg'=>lang("pd_ud_amount_fail")];
			case - 4 :
				return ['code'=>false,'msg'=>lang("pd_dif_notexist")];
			case - 3 :
				return ['code'=>false,'msg'=>lang("pd_del_detail_fail")];
			case - 2 :
				return ['code'=>false,'msg'=>lang("pd_record_notexist")];
			case - 1 :
				return ['code'=>false,'msg'=>lang("pd_del_fail")];
			case 0 :
				return ['code'=>false,'msg'=>lang("pd_del_not_chk")];
			default :
				return ['code'=>true,'msg'=>lang("pd_sheet_delete_ok")];
		}
		
	}
	
	// 审核
	public function approve() {
		if (! IS_AJAX) {
			 return ['code'=>false,'msg'=>lang("illegal_operate")];
		}
		
		$sheetno =input ( "sheetno" );
		$items = input ( "items/a" );
		$rangeno =input ( "rangeno" );
		
		if (empty ( $sheetno )) {
			return ['code'=>false,'msg'=>lang("invalid_variable")];
		}
		
		$CheckInit = new CheckInit ();
		$chkInit = $CheckInit->GetNotUsedPDSheet ( $sheetno );
		if (empty ( $chkInit )) {
			return ['code'=>false,'msg'=>lang("pd_sheetno_notexist")];
		}
		$CheckSum = new CheckSum ();
		$chkSum = $CheckSum->GetValidSumBySheetno ( $sheetno );
		if (empty ( $chkSum )) {
			return ['code'=>false,'msg'=>lang("pd_sheetno_nodetail")];
		}
		
		$res = $CheckInit->Approve ( $chkInit, $chkSum, $rangeno, $items );
		if (is_array ( $res )) {
			return ['code'=>true,'msg'=>lang("pd_approve_success")];
		}
		switch ($res) {
			case - 4 :
				return ['code'=>false,'msg'=>lang("pd_apv_data_fail")];
			case - 3 :
				return ['code'=>false,'msg'=>lang("pd_apv_stock_fail")];
			case - 2 :
				return ['code'=>false,'msg'=>lang("pd_apv_error")];
			case - 1 :
				return ['code'=>false,'msg'=>lang("pd_apv_fail")];
			case 0 :
				return ['code'=>false,'msg'=>lang("pd_apv_not_chk")];
			default :
				return ['code'=>true,'msg'=>lang("pd_approve_success")];
		}
	}
}
