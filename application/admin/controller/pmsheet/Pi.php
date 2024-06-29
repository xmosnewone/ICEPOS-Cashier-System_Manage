<?php
namespace app\admin\controller\pmsheet;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ESheetTrans;
use model\PmSheetMaster;
use model\PmSheetDetail;
use model\SysSheetNo;
use model\SysManager;

/**
 * 采购收货订单
 */
class Pi extends Super {
	
	// 列表首页模板
	public function index() {
		return $this->fetch ( "pmsheet/pilist" );
	}
	
	// ajax数据
	public function getlist() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		
		$start = input ( "start" );
		$end = input ( "end" );
		$sheet_no = input ( "no" );
		$approve_flag = input ( "approve_flag" );
		$oper_id = input ( "oper_id" );
		$supcust_no = input ( "supcust_no" );
		$sheet_amt = input ( "amt" );
		$PmSheetMaster = new PmSheetMaster ();
		$result = $PmSheetMaster->GetSheetPager ( $rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $supcust_no, "0", $sheet_amt, ESheetTrans::PI );
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
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
					'msg' => lang ( "pi_delete_success" ) 
			];
		}
	}
	
	// 删除
	public function delete($no) {

			$PmSheetMaster = new PmSheetMaster ();
			$res = $PmSheetMaster->DeleteSheet ( $no );
			switch ($res) {
				case - 2 :
				case 0 :
					return [
							'code' => false,
							'msg' => lang ( "delete_error" )
					];
				case - 1 :
					return [ 
						'code' => false,
						'msg' => lang ( "empty_record" ) 
					];
				case - 3 :
					return [ 
						'code' => false,
						'msg' => lang ( "po_check_not_approve" ) 
					];
				default :
					return [ 
						'code' => true,
						'msg' => lang ( "po_delete_success" ) 
					];
			}
		
	}
	
	//审核
	public function approve() {
		if (! IS_AJAX) {
			return [ 
					'code' => false,
					'msg' => lang ( "illegal_operate" ) 
			];
		}
		
		$no = input ( "no" );
		
		if (empty($no)) {
			return ['code'=>false,'msg'=>lang("invalid_variable")];
		}

		$confirm_man = session ( "loginname" );
		if (! empty ( $confirm_man )) {
			$PmSheetMaster = new PmSheetMaster ();
			$res = $PmSheetMaster->ApproveSheet ( $no, $confirm_man );
			switch ($res) {
				case - 1 :
					return [
						'code' => false,
						'msg' => lang ( "empty_record" )
					];
				case - 2 :
				case 0 :
					return [
						'code' => false,
						'msg' => lang ( "po_check_fail" )
					];
				case - 3 :
					return [
						'code' => false,
						'msg' => lang ( "po_check_not_approve" )
					];
				default :
					return [
						'code' => true,
						'msg' => lang ( "po_check_success" )
					];
			}
		}
		
	}
	
	//详细
	public function detail() {
		
		$no=input("no");
		$orderMan = session('loginname');
		
		if (!empty($no)) {
			$PmSheetMaster=new PmSheetMaster();
			$model = $PmSheetMaster->GetSheet($no);
			if (!empty($model)) {
				$orderMan=$model->order_man;
				$model->approve=$model->approve_flag;
				$this->assign("one", $model);
				$this->assign("approve", $model->approve_flag);
			}
		} else {
			$this->assign("approve", "-1");
		}
		$this->assign("orderMan",$orderMan);
		return $this->fetch("pmsheet/pidetail");
		
	}
	
	//ajax采购单详细
	public function getdetail() {
	
		$no = input("no");
		if (empty($no)) {
			return ['code'=>false,'msg'=>lang("invalid_variable")];
		}
		$PmSheetDetail=new PmSheetDetail();
		$result = $PmSheetDetail->GetDetail($no);
		return listJson(0,lang("success_data"),$result['total'], $result['rows']);
	}
	
	//保存采购单
	public function save() {
		
		if (!IS_AJAX) {
        	return ['code'=>false,'msg'=>lang("illegal_operate")];
        }
		
        $sheet=input("sheet/a");
        $items=input("items/a");
        
        if (empty($sheet) || empty($items)) {
        	return ['code'=>true,'msg'=>lang("invalid_variable")];
        }
        if (empty ( $items )) {
        	return [
        			'code' => false,
        			'msg' => lang ( "illegal_data" )
        	];
        }

		$no =  $sheet ["sheet_no"];
		if (empty ($sheet["voucher_no"] )) {
			return [
					'code' => false,
					'msg' => lang("pi_empty_po")
			];
		}
		
		$PmSheetMaster=new PmSheetMaster();
		//判断是否有未审核的采购单转收货单记录，有则返回-1，要先审核
		$res = $PmSheetMaster->CanDear ($sheet["voucher_no"] );
		if (empty ( $no ) && ($res === - 1)) {
			return [
					'code' => false,
					'msg' => lang("pi_unhandle_po")
			];
		}
		
		$flag = 0;
		if (empty ( $no )) {
			$SysSheetNo=new SysSheetNo();
			$master = new PmSheetMaster ();
			$master->db_no = ESheetTrans::PLUS;
			$master->trans_no = ESheetTrans::PI;
			$master->sheet_no = $SysSheetNo->CreateSheetNo ( $master->trans_no, $sheet["branch_no"] );
		} else {
			$master = $PmSheetMaster->GetSheet ( $no );
			if (empty ( $master )) {
				return [ 
					'code' => false,
					'msg' => lang ( "empty_record" ) 
				];
			}
			$master->db_no = ESheetTrans::PLUS;
			$master->trans_no = ESheetTrans::PI;
			$flag = 1;
		}
		
		unset($sheet['sheet_no']);
		foreach($sheet as $k=>$value){
			$master->$k=$value;
		}
		$master->d_branch_no=$sheet["branch_no"];
		
		$amount = 0;
		$details = array ();
		foreach ( $items as $k => $v ) {
			if (! empty ( $v ["item_no"] )) {
				$detail = new PmSheetDetail ();
				$detail->item_no = $v ["item_no"];
				$detail->large_qty = $v ["large_qty"];
				$detail->order_qty = isset ( $v ["order_qty"] ) ? $v ["order_qty"] : 0;
				$detail->send_qty = isset ( $v ["send_qty"] ) ? $v ["send_qty"] : 0;
				$detail->valid_price = isset ( $v ["item_price"] ) ? $v ["item_price"] : 0;
				$detail->valid_date = isset ( $v ["valid_date"] ) ? $v ["valid_date"] : "";
				$detail->tax = isset ( $v ["purchase_tax"] ) ? $v ["purchase_tax"] : 0;
				$detail->sub_amt = $v ["sub_amt"];
				$detail->other1 = isset ( $v ["memo"] ) ? $v ["memo"] : "";
				$amount = $amount + doubleval ( $v ["sub_amt"] );
				array_push ( $details, $detail );
			}
		}
		if (count ( $details ) == 0) {
			return [ 
				'code' => false,
				'msg' => lang ( "po_empty_details" ) 
			];
		}
		$order_man =input("order_man")?input("order_man"):session( "loginname" );
		$master->sheet_amt = $amount;
		$master->order_man = $order_man;
		$addflag = $flag == 1 ? "edit" : "add";
		$res = $PmSheetMaster->SaveSheet ( $master, $details, $addflag );
		switch ($res) {
			case - 1 :
			case - 2 :
			case 0 :
				return [
						'code' => false,
						'msg' => lang ( "save_error" )
				];
			default :
				return [
						'code' => true,
						'msg' => lang ( "save_success" ),
						'data' => $master->sheet_no
				];
		}
		
	}
	
	//采购订单详细
	public function getdeardetail() {
		$no = input ( "no" );
		if (! empty ( $no )) {
			$PmSheetDetail=new PmSheetDetail();
			$result = $PmSheetDetail->GetNoneDearDetail ( $no );
		 	return listJson(0,lang("success_data"),$result['total'], $result['rows']);
		}
	}
	
	public function candear() {
		$no = input ( "no" );
		if (! empty ( $no )) {
			$PmSheetMaster=new PmSheetMaster();
			$res = $PmSheetMaster->CanDear ( $no );
			return [
						'code' => $res
			];
		}
	}
	
	//导出采购收货单
	public function export() {
		$no = input ( "no" );
		if (empty ( $no )) {
			echo "参数错误";
			exit();
		} 
		
		$PmSheetMaster=new PmSheetMaster();
		$PmSheetDetail=new PmSheetDetail();
		
		$objPHPExcel = new \PHPExcel ();
		$model = $PmSheetMaster->GetSheet ( $no );
		$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( "A1", "采购收货" );
		$objPHPExcel->getActiveSheet ()->mergeCells ( 'A1:M2' );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFill ()->setFillType ( \PHPExcel_Style_Fill::FILL_SOLID );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFont ()->setBold ( true );
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A3", "单据编号：" )->setCellValue ( "B3", $no )->setCellValue ( "D3", "供 应 商：" )->setCellValue ( "E3", $model->sp_name )->mergeCells ( 'E3:M3' )->setCellValue ( "A4", "采购单号：" )->setCellValue ( "B4", $model->voucher_no )->setCellValue ( "D4", "采 购 员：" )->setCellValue ( "E4", $model->oper_name )->mergeCells ( 'E4:H4' )->setCellValue ( "I4", "仓    库：" )->setCellValue ( "J4", $model->branch_name )->mergeCells ( 'J4:M4' )->setCellValue ( "A5", "制单人员：" )->setCellValue ( "B5", $model->username )->setCellValue ( "D5", "制单日期:" )->setCellValue ( "E5", $model->oper_date )->mergeCells ( 'E5:H5' )->setCellValue ( "I5", "付款期限：" )->setCellValue ( "J5", $model->pay_date )->mergeCells ( 'J5:M5' )->setCellValue ( "A6", "审核人员：" )->setCellValue ( "B6", $model->confirm_name )->setCellValue ( "D6", "审核日期：" )->setCellValue ( "E6", $model->work_date )->mergeCells ( 'E6:H6' )->setCellValue ( "I6", "备    注：" )->setCellValue ( "J6", $model->memo )->mergeCells ( 'J6:M6' );
		$models = $PmSheetDetail->GetDetail ( $no );
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A7", "行号" )->setCellValue ( "B7", "货号 " )->setCellValue ( "C7", "品名" )->mergeCells ( "C7:D7" )->setCellValue ( "E7", "单位" )->setCellValue ( "F7", "规格" )->setCellValue ( "G7", "箱数" )->mergeCells ( "G7:H7" )->setCellValue ( "I7", "数量" )->mergeCells ( "I7:J7" )->setCellValue ( "K7", "赠送数量" )->mergeCells ( "K7:L7" )->setCellValue ( "M7", "进货规格" );
		$i = 8;
		foreach ( $models ["rows"] as $v ) {
			$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( 'A' . $i, ($i - 7) )->setCellValue ( 'B' . $i, ' ' . $v ["item_no"] )->setCellValue ( "C" . $i, $v ["item_name"] )->mergeCells ( "C" . $i . ":D" . $i )->setCellValue ( "E" . $i, $v ["item_unit"] )->setCellValue ( "F" . $i, $v ["item_size"] )->setCellValue ( "G" . $i, $v ["large_qty"] )->mergeCells ( "G" . $i . ":H" . $i )->setCellValue ( "I" . $i, $v ["order_qty"] )->mergeCells ( "I" . $i . ":J" . $i )->setCellValue ( "K" . $i, $v ["send_qty"] )->mergeCells ( "K" . $i . ":L" . $i )->setCellValue ( "M" . $i, $v ["purchase_spec"] );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A' . $i . ':E' . $i )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A' . $i . ':M' . $i )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'F' . $i . ':M' . $i )->getNumberFormat ()->setFormatCode ( '0.00' );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'C' . $i )->getAlignment ()->setWrapText ( true );
			$i ++;
		}
		$last = $i - 1;
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A" . ($i), "总    计：" )->

		setCellValue ( "G" . ($i), "=SUM(G8:G$last)" )->mergeCells ( "G" . $i . ":H" . $i )->setCellValue ( "I" . ($i), "=SUM(H8:H$last)" )->mergeCells ( "I" . $i . ":J" . $i );

		
		$objPHPExcel->getActiveSheet ()->getStyle ( 'F' . $i . ':M' . $i )->getNumberFormat ()->setFormatCode ( '0.00' );
		
		$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'A' )->setWidth ( 8.71 );
		$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'B' )->setWidth ( 13 );
		$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'C' )->setWidth ( 18 );
		$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'D' )->setWidth ( 10 );
		$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'E' )->setWidth ( 8 );
		$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'F' )->setWidth ( 7 );
		$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'G' )->setWidth ( 8 );
		$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'H' )->setWidth ( 8 );
		$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'I' )->setWidth ( 8 );
		
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A" . ($i + 2), "经手：" )->setCellValue ( "C" . ($i + 2), "审核：" )->setCellValue ( "E" . ($i + 2), "签收：" )->setCellValue ( "G" . ($i + 2), "财务：" )->setCellValue ( "I" . ($i + 2), "仓库：" );
		
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A" . ($i + 3), "公司名称：" )->setCellValue ( "B" . ($i + 3), "总部" )->setCellValue ( "E" . ($i + 3), "公司地址：" )->setCellValue ( "F" . ($i + 3), "您的详细地址" )->mergeCells ( "F" . ($i + 3) . ":M" . ($i + 3) );
		
		$loginname = session ( "loginname" );
		$SysManager=new SysManager();
		$user = $SysManager->Get ( $loginname );
		
		$objPHPExcel->getActiveSheet ()->setCellValue ( "I" . ($i + 4), "打印人：" )->setCellValue ( "J" . ($i + 4), $user->username )->setCellValue ( "K" . ($i + 4), "打印时间：" )->setCellValue ( "L" . ($i + 4), date ( DATETIME_FORMAT, time () ) )->mergeCells ( "L" . ($i + 4) . ":M" . ($i + 4) );
		
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A3' . ':M' . ($i + 4) )->getFont ()->setName ( '宋体' )->setSize ( 9 );
		
		$styleArray = array (
				'borders' => array (
						'allborders' => array (
								
								'style' => \PHPExcel_Style_Border::BORDER_THIN 
						)
						 
				) 
		);
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1:M' . ($i + 4) )->applyFromArray ( $styleArray );
		
		$objWriter = \PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' );
		$filename = "采购收货" . $no . ".xls";
		header ( 'Content-Type: application/vnd.ms-excel' );
		header ( 'Content-Disposition: attachment;filename="' . $filename . '"' );
		header ( 'Cache-Control: max-age=0' );
		$objWriter->save ( 'php://output' );
	}
	
}
