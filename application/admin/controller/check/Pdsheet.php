<?php
namespace app\admin\controller\check;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ESheetTrans;
use model\PosBranch;
use model\SysSheetNo;
use model\CheckInit;
use model\CheckSum;

/**
 * 库存盘点批号
 */
class Pdsheet extends Super {
	
	//盘点批号列表
	public function index() {
		$wareHouse=$this->getBranch();
		$this->assign ( "wareHouse", $wareHouse );
		return $this->fetch ( "check/index" );
	}
	
	// ajax列表数据
	public function getlist() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		
		$sheet_no = input ( "sheet_no" );
		$branch_no = input ( "branch_no" );
		$approve_flag = input ( "approve_flag" );
		
		$CheckInit = new CheckInit ();
		$result = $CheckInit->GetPager ( $rows, $page, 0, 0, $sheet_no, $approve_flag, 0, '', $branch_no );
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
	}
	
	// 显示添加盘点批号
	public function addSheetno() {
		$wareHouse=$this->getBranch();
		$this->assign ( "wareHouse", $wareHouse );
		return $this->fetch ( "check/addno" );
	}
	
	// 创建盘点批号
	public function createSheetno() {
		$operRange = input ( "oper_range" );
		$branch_no = input ( "branch_no" );
		$memo = input ( "memo" );
		$time = $this->_G ['time'];
		$CheckInit = new CheckInit ();
		$PosBranch = new PosBranch ();
		
		$model = $PosBranch->GetAllStoreOrShop ($branch_no);
		if (empty ( $model )) {
			return [
					'code' => false,
					'msg' => lang ( "pd_branch_empty" )
			];
		}
		
		$SysSheetNo = new SysSheetNo ();
		$sheetno = $SysSheetNo->CreateSheetNo ( ESheetTrans::PD, $branch_no );
		$checkInitModel = new CheckInit ();
		$checkInitModel->sheet_no = $sheetno;
		$checkInitModel->oper_date = date ( DATETIME_FORMAT, $time );
		$checkInitModel->start_time = date ( DATETIME_FORMAT, $time );
		$checkInitModel->oper_range = $operRange;
		$checkInitModel->branch_no = $branch_no;
		$checkInitModel->oper_id = session ( "loginname" );
		$checkInitModel->memo = $memo;
		if ($checkInitModel->CreateSheetno ( $checkInitModel ) === TRUE) {
			return [
					'code' => true,
					'msg' => lang ( "pd_sheetno_success" ),
					'sheetno'=>$sheetno
			];
		}else{
			return [
					'code' => false,
					'msg' => lang ( "pd_sheetno_fail" ),
					'sheetno'=>$sheetno
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
					'msg' => lang ( "pd_no_delete_success" )
			];
		}
	}
	
	//删除盘点单批号及下面信息
	public function delete($sheetno) {

		if (empty($sheetno)) {
			return ['code'=>false,'msg'=>lang("invalid_variable")];
		}
		$CheckInit=new CheckInit();
		$res = $CheckInit->Del($sheetno);

		switch ($res) {
			case -2:
				return ['code'=>false,'msg'=>lang("pd_sheetno_notexist")];
			case -1:
				return ['code'=>false,'msg'=>lang("pd_del_fail")];
			case 0:
				return ['code'=>false,'msg'=>lang("pd_del_not_chk")];
			default:
				return ['code'=>true,'msg'=>lang("pd_no_delete_success")];
		}
		
	}
	
	//获取门店数据
	private function getBranch(){
		$PosBranch = new PosBranch ();
		$fields='branch_no,branch_name';
		$wareHouseList = $PosBranch->GetAllBranchField ($fields);
		return $wareHouseList;
	}
}
