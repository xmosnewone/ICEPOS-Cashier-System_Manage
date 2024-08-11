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
 * 库存调整单
 */
class Stocksheet extends Super {
	public function index() {
		return $this->fetch ( "imsheet/stocksheet" );
	}
	
	// 库存调整单详细
	public function sheetDetail() {
		$operDate = date ( 'Y-m-d', $this->_G ['time'] );
		$orderMan = session ( 'loginname' );
		$sheetno = input ( "sheetno" );
		$approve = "0";
		if (! empty ( $sheetno )) {
			$ImSheetMaster = new ImSheetMaster ();
			$master = $ImSheetMaster->Get ( $sheetno );
			$params ["sheetNo"] = $master ["sheet_no"];
			$params ["branchNo"] = $master ["branch_no"];
			$params ["branchName"] = $master ["branch_name"];
			$params ["operId"] = $master ["oper_id"];
			$params ["operName"] = $master ["oper_name"];
			$params ["confirmMan"] = $master ["confirm_man"];
			$params ["workDate"] = $master ["work_date"];
			$params ["reasonName"] = $master ["other1"];
			$params ["reasonNo"] = $master ["other2"];
			$params ["db_no"] = $master ["db_no"] == ESheetTrans::PLUS ? 0 : 1;
			$params ["memo"] = $master ["memo"];
			$operDate = $master ["oper_date"];
			$orderMan = $master ["order_man"];
			$approve = $master ["approve_flag"];
		}
		$params ['approve'] = $approve;
		$params ['operDate'] = $operDate;
		$params ['orderMan'] = $orderMan;
		$this->assign ( "one", $params );
		
		//调整库存单原因
		$reason=$this->getReason();
		$this->assign ( "reason", $reason );
		
		return $this->fetch ( "imsheet/sheetdetail" );
	}
	
	// 保存
	public function save() {
		$post = input ( "" );
		$branch_no = $post ["branch_no"];//仓库
		$oper_date = $post ["oper_date"];//制单日期
		$db_no = $post ["db_no"];//出入库
		$reasonNo = $post ["reason"];//调整原因
		$reasonName = $post ["reasonname"];//原因名称
		$oper_id = $post ["oper_id"];//制单人
		$memo = $post ["memo"];//备注
		$operFunc = "add";
		$sheetno = input ( "sheetno" );//编辑带单号
		if (empty ( $sheetno )) {
			$SysSheetNo = new SysSheetNo ();
			$sheetno = $SysSheetNo->CreateSheetNo ( ESheetTrans::OO, $branch_no );
		} else {
			$sheetno = input ( "sheetno" );
			$operFunc = "update";
		}
		
		$items = input ( "items/a" );
		if (empty ( $items ) || empty ( $post )) {
			return [ 
					'code' => false,
					'msg' => lang ( "invalid_variable" ) 
			];
		}
		
		$amount = 0;
		$details = array ();
		foreach ( $items as $k => $v ) {
			
			if (! empty ( $v ['item_no'] )) {
				$detail = new ImSheetDetail ();
				$detail->item_no = $v ["item_no"];
				$detail->large_qty = $v ["large_qty"];
				$detail->real_qty = isset ( $v ["real_qty"] ) ? $v ["real_qty"] : "0.00";
				$detail->order_qty = $v ["item_stock"];
				$detail->orgi_price = $v ["item_price"];
				$detail->sub_amt = $v ["sub_amt"];
				$detail->other1 = isset ( $v ["memo"] ) ? $v ["memo"] : "";
				$amount = $amount + doubleval ( $v ["sub_amt"] );
				array_push ( $details, $detail );
			}
		}
		if (count ( $details ) == 0) {
			return ['code'=>false,'msg'=>lang("st_empty_details")];
		}
		$orderman = session ( "loginname" );
		$master = new ImSheetMaster ();
		if ($operFunc == "add") {
			$master->sheet_no = $sheetno;
		} else {
			$master = $master->Get( $sheetno );
		}
		$master->sheet_amt = $amount;
		$master->db_no = $db_no == 0 ? ESheetTrans::PLUS : ESheetTrans::MINUS;
		$master->trans_no = ESheetTrans::OO;
		$master->order_man = $orderman;
		$master->branch_no = $branch_no;
		$master->oper_date = $oper_date;
		$master->oper_id = $oper_id;
		if($reasonNo!=''){
			$master->other1 = $reasonName;
			$master->other2 = $reasonNo;
		}
		$master->memo = $memo;
		$res = $master->Add ( $master, $details, $operFunc, ESheetTrans::OO );
		if ($res > 0) {
			return ['code'=>true,'msg'=> $master->sheet_no ];
		} else {
			return ['code'=>false,'msg'=>lang("save_error") ];
		}
	}
	
	// 审核
	public function approve() {
		
		$sheetno = input ( "sheetno");
		
		if (empty( $sheetno )) {
			return ['code'=>false,'msg'=>lang("invalid_variable") ];
		}
			
		$res = $this->SaveApprove( $sheetno );
		if (is_array ( $res )) {
			return ['code'=>true,'msg'=>lang("st_approve_success") ];
		}
		$ress = explode ( ":", $res );
		switch ($ress ['0']) {
			case - 4 :
				return ['code'=>false,'msg'=>$ress ['1'] . lang("st_no_stock")];
			case - 3 :
				return ['code'=>false,'msg'=> lang("st_approve_fail")];
			case - 2 :
				return ['code'=>false,'msg'=>lang("st_emtpy_record")];
			case - 1 :
				return ['code'=>false,'msg'=>lang("st_del_record_fail")];
			case 0 :
				return ['code'=>false,'msg'=>lang("st_apv_not_chk")];
			default :
				return ['code'=>true,'msg'=>lang("st_approve_success")];
		}
		
	}
	
	//执行审核
	private function SaveApprove($sheetno) {
		$ImSheetMaster=new ImSheetMaster();
		$model = $ImSheetMaster->Get($sheetno);
		if (empty ( $model )) {
			return - 2;
		}
		if ($model->approve_flag == 1) {
			return - 0;
		}
		
		Db::startTrans();
		try {
			$model->approve_flag = '1';
			$model->work_date = date ( DATETIME_FORMAT, $this->_G['time']);
			$model->confirm_man = session ('loginname');
			
			if (! $model->save ()) {
				 Db::rollback();
				return - 3;
			}
			
			$ImSheetDetail=new ImSheetDetail();
			$modelDetail = $ImSheetDetail->where("sheet_no='$sheetno'")->select();
			$isCommit = TRUE;
			$PosBranchStock=new PosBranchStock();
			foreach ( $modelDetail as $k => $v ) {
				if (! empty ( $v->item_no ) && intval ( $v->real_qty ) != 0) {
					if ($PosBranchStock->UpdateStockBySheetNo ( $sheetno, $model->branch_no, $v->item_no, intval ( $v->real_qty ), $model->db_no ) == FALSE) {
						$isCommit = FALSE;
						break;
					}
				}
			}
			if ($isCommit) {
				Db::commit();
				$ary ["work_date"] = $model->work_date;
				$ary ["confirm_man"] = $model->confirm_man;
				return $ary;
			} else {
				 Db::rollback();
				return - 1;
			}
		} catch ( \Exception $e ) {
			 Db::rollback();
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
					'msg' => lang ( "st_del_success" )
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
				return ['code'=>false,'msg'=> lang("st_emtpy_record")];
			case - 1 :
				return ['code'=>false,'msg'=> lang("st_del_record_fail")];
			case 0 :
				return ['code'=>false,'msg'=> lang("st_del_not_apv")];
			default :
				return ['code'=>true,'msg'=> lang("st_del_success")];
		}
		
	}
	
	//库存调整原因
	public function getReason() {
	
		$where="type_no='OO'";
		$list=Db::name("bd_base_code")
		->field("code_id, code_name, type_no,memo")
		->where($where)
		->select();
	
		$temp = array ();
		foreach ( $list as $v ) {
			$tt = array ();
			$tt ["code_id"] = $v ["code_id"];
			$tt ["code_name"] = $v ["code_name"];
			$tt ["type_no"] = $v ["type_no"];
			$tt ["memo"] = $v ["memo"];
			array_push ( $temp, $tt );
		}
		
		return $temp;
	}
}
