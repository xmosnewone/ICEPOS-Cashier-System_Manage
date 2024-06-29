<?php
namespace app\admin\controller\pmsheet;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ESheetTrans;
use model\PmSheetMaster;
use model\PmSheetDetail;
use model\SysSheetNo;
use model\SysManager;
/**
 * 分店要货单
 */
class Yhsheet extends Super {
	
	public function index() {
		return $this->fetch ( "pmsheet/yhlist" );
	}
	
	//要货单详细页面
	public function detail() {
		
		$operDate = date('Y-m-d H:i', $this->_G['time']);
		$orderMan = session('loginname');
		$sheetno = input( "no" );
		$approve = "-1";
		if (!empty ( $sheetno )) {
			$PmSheetMaster=new PmSheetMaster();
			$master =$PmSheetMaster->Get ( $sheetno );
			$orderMan = $master["order_man"];
			$one=$master;
			if ($master["approve_flag"] == 1) {
				$approve = "1";
			} else {
				$approve = "0";
			}
		}
		
		$one['approve'] = $approve;
		$one['order_man'] = $orderMan;
		$this->assign ( "one", $one );
		return $this->fetch ("pmsheet/yhdetail");
	}
	
	//保存要货单
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
		
		$branch_no = ctrim ($sheet["branch_no"]);//调出仓库
		$d_branch_no = ctrim ($sheet["d_branch_no"]);//收货分店
		$oper_date = $sheet["oper_date"];
		$oper_id = $sheet["oper_id"];
		$validDate = $sheet["valid_date"];//有效期限
		$memo = ctrim($sheet["memo"]);
		
		$operFunc = "add";
		$sheetno = input ( "sheetno" );
		if (empty ( $sheetno )) {
			$SysSheetNo=new SysSheetNo();
			$sheetno = $SysSheetNo->CreateSheetNo ( ESheetTrans::YH, $branch_no );
			$master = new PmSheetMaster ();
		} else {
			$PmSheetMaster=new PmSheetMaster();
			$operFunc = "update";
			$master = $PmSheetMaster->GetInstance($sheetno);
			if (empty ( $master )) {
				return [ 
						'code' => false,
						'msg' => lang ( "empty_record" ) 
				];
			}
		}
		
		$amount = 0;
		$details = array ();
		foreach ( $items as $k => $v ) {
			if (! empty ( $v ['item_no'] )) {
				$detail = new PmSheetDetail ();
				$detail->item_no = $v ["item_no"];
				$detail->large_qty = $v ["large_qty"];
				$detail->real_qty = $v ["order_qty"];
				$detail->orgi_price = $v ["sale_price"];
				$detail->sub_amt = $v ["sale_amt"];
				$detail->other1 = isset ( $v ["memo"] ) ? $v ["memo"] : '';
				$amount = $amount + doubleval ( $v ["sale_amt"] );
				array_push ( $details, $detail );
			}
		}
		if (count ( $details ) == 0) {
			return [ 
					'code' => false,
					'msg' => lang ( "po_empty_details" ) 
			];
		}
		
		$orderman = input("order_man")?input("order_man"):session("loginname");
		
		$master->sheet_no = $sheetno;
		$master->sheet_amt = $amount;
		$master->db_no = ESheetTrans::PLUS;
		$master->trans_no = ESheetTrans::YH;
		$master->order_man = $orderman;
		$master->branch_no = $branch_no;
		$master->d_branch_no = $d_branch_no;
		$master->oper_date = $oper_date;
		$master->valid_date = $validDate;
		$master->oper_id = $oper_id;
		$master->memo = $memo;
		
		$PmSheetMaster=new PmSheetMaster();
		$res = $PmSheetMaster->Add ( $master, $details, $operFunc );
		if ($res > 0) {
			return [
					'code' => true,
					'msg' => lang ( "save_success" ),
					'data' => $master->sheet_no
					];
		} else {
			return [ 
						'code' => false,
						'msg' => lang ( "save_error" ) 
					];
		}
		
	}
	
	//审核
	public function approve() {
		if (!IS_AJAX) {
            return ['code'=>false,'msg'=>lang("illegal_operate")];
        }
		
		$sheetno =input ( "no" );
		if (empty ( $sheetno )) {
			return ['code'=>false,'msg'=>lang("invalid_variable")];
		}
		
		$PmSheetMaster=new PmSheetMaster();
		$res = $PmSheetMaster->Approve ( $sheetno );
		if (is_array ( $res )) {
			$message ["workDate"] = $res ["work_date"];
			$message ["confirmMan"] = $res ["confirm_man"];
			$message ["message"] = lang("yh_check_success");
			return [
					'code' => true,
					'msg' => lang ( "yh_check_success" )
			];
		}
		$ress = explode ( ":", $res );
		switch ($ress ['0']) {
			case - 5001 :
				return [
						'code' => false,
						'msg' => $ress ['1']
				];
			case - 4 :
				return [
						'code' => false,
						'msg' => $ress ['1'] . lang("yh_stock_unenough")
				];
			case - 3 :
				return [
						'code' => false,
						'msg' => $ress ['1'] . lang("yh_check_fail")
				];
			case - 2 :
				return [
						'code' => false,
						'msg' => $ress ['1'] . lang("empty_record")
				];
			case - 1 :
				return [
						'code' => false,
						'msg' => lang("delete_error")
				];
			case 0 :
				return [
						'code' => false,
						'msg' => lang("yh_check_not_approve")
				];
			default :
				return [
						'code' => true,
						'msg' => $message
				];
		}

	}
	
	//批量删除
	public function batchDelete() {
		 
		$sheet_no = input("sheet_no");
		 
		if (empty($sheet_no)) {
			return ['code'=>false,'msg'=>lang("invalid_variable")];
		}
		 
		$arr=strToArray($sheet_no);
		 
		if(count($arr)<=0){
			return ['code'=>false,'msg'=>lang("invalid_variable")];
		}
		 
		$error=[];
		foreach($arr as $no){
			$res=$this->delete($no);
			if(!$res['code']){
				$error[]=$no.":".$res['msg'];
			}
		}
		 
		if(count($error)>0){
			$error_no=implode(",", $error);
			return [
					'code' => false,
					'msg' => $error_no
			];
		}else{
			return [
					'code' => true,
					'msg' => lang("yh_delete_success")
			];
		}
	}
	
	//删除记录
	public function delete($sheetno) {
		
		$PmSheetMaster=new PmSheetMaster();
		$res = $PmSheetMaster->Del ( $sheetno );
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
						'msg' => lang ( "delete_error" )
				];
			case - 3 :
				return [
						'code' => false,
						'msg' => lang ( "yh_check_not_approve" )
				];
			default :
				return [
						'code' => true,
						'msg' => lang("yh_delete_success")
				];
				
		}
		
	}
	
	//导出
	public function export() {
		$no = input ( "no" );
		if (empty ( $no )) {
			echo lang("invalid_variable");
			exit();
		}
		
		$objPHPExcel = new \PHPExcel ();
		$PmSheetMaster=new PmSheetMaster();
		$model = $PmSheetMaster->Get ( $no );
		$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( "A1", "要货单" );
		$objPHPExcel->getActiveSheet ()->mergeCells ( 'A1:M2' );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFill ()->setFillType ( \PHPExcel_Style_Fill::FILL_SOLID );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFont ()->setBold ( true );
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A3", "单据编号：" )->setCellValue ( "B3", $no )
		->setCellValue ( "D3", "要货分店：" )->setCellValue ( "E3", $model->d_branch_name )
		->mergeCells ( 'E3:M3' )->setCellValue ( "A4", "发货分店：" )->setCellValue ( "B4", $model->branch_name )
		->mergeCells ( 'B4:C4' )->setCellValue ( "D4", "有效期限：" )->setCellValue ( "E4", $model->valid_date )
		->mergeCells ( 'E4:H4' )->setCellValue ( "I4", "申 请 人：" )->setCellValue ( "J4", $model->oper_name )
		->mergeCells ( 'J4:M4' )->setCellValue ( "A5", "制单人员：" )->setCellValue ( "B5", $model->username )
		->mergeCells ( 'B5:C5' )->setCellValue ( "D5", "制单日期:" )->setCellValue ( "E5", $model->oper_date )
		->mergeCells ( 'E5:H5' )->setCellValue ( "I5", "备    注：" )->setCellValue ( "J5", $model->memo )
		->mergeCells ( 'I5:I6' )->mergeCells ( 'J5:M6' )->setCellValue ( "A6", "审核人员：" )
		->setCellValue ( "B6", $model->confirm_name )->mergeCells ( 'B6:C6' )->setCellValue ( "D6", "审核日期：" )
		->setCellValue ( "E6", $model->work_date )->mergeCells ( 'E6:H6' );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'I5' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'I5' )->getAlignment ()->setVertical (\PHPExcel_Style_Alignment::VERTICAL_CENTER );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'J5' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'J5' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
		$PmSheetDetail=new PmSheetDetail();
		$model1 = $PmSheetDetail->Get ( $no );
		$result = array ();
		if (! empty ( $no )) {
			$i = 0;
			foreach ( $model1 as $k => $v ) {
				$result [$i] ["item_no"] = $v ["item_no"];
				$result [$i] ["item_subno"] = $v ["item_subno"];
				$result [$i] ["item_name"] = $v ["item_name"];
				$result [$i] ["item_unit"] = $v ["item_unit"];
				$result [$i] ["item_size"] = $v ["item_size"];
				$result [$i] ["large_qty"] = sprintf ( "%.2f", $v ["large_qty"] );
				$result [$i] ["order_qty"] = sprintf ( "%.2f", $v ["real_qty"] );
				$result [$i] ["purchase_spec"] = $v ["purchase_spec"];
				$result [$i] ["item_price"] = sprintf ( "%.2f", $v ["item_price"] );
				$result [$i] ["sub_amt"] = sprintf ( "%.2f", $v ["real_qty"] * $v ["item_price"] );
				$result [$i] ["memo"] = $v ["memo"];
				$i ++;
			}
			$objPHPExcel->getActiveSheet ()->setCellValue ( "A7", "行号" )->setCellValue ( "B7", "货号 " )->setCellValue ( "C7", "品名" )
			->mergeCells ( 'C7:D7' )->setCellValue ( "E7", "单位" )->setCellValue ( "F7", "规格" )->setCellValue ( "G7", "箱数" )
			->setCellValue ( "H7", "数量" )->setCellValue ( "I7", "进货规格" )->setCellValue ( "J7", "进价" )->setCellValue ( "K7", "金额" )
			->setCellValue ( "L7", "备注" )->mergeCells ( "L7:M7" );
			$i = 8;
			foreach ( $result as $v ) {
				$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( 'A' . $i, ($i - 7) )->setCellValue ( 'B' . $i, ' ' . $v ["item_no"] )
				->setCellValue ( 'C' . $i, $v ["item_name"] )->mergeCells ( 'C' . $i . ':D' . $i )
				->setCellValue ( 'E' . $i, $v ["item_unit"] )->setCellValue ( 'F' . $i, $v ["item_size"] )->setCellValue ( 'G' . $i, $v ["large_qty"] )
				->setCellValue ( 'H' . $i, $v ["order_qty"] )->setCellValue ( 'I' . $i, $v ["purchase_spec"] )
				->setCellValue ( 'J' . $i, $v ["item_price"] )->setCellValue ( 'K' . $i, $v ["sub_amt"] )
				->setCellValue ( 'L' . $i, $v ["memo"] )->mergeCells ( 'L' . $i . ':M' . $i );
				$objPHPExcel->getActiveSheet ()->getStyle ( 'A' . $i . ':F' . $i )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
				$objPHPExcel->getActiveSheet ()->getStyle ( 'A' . $i . ':M' . $i )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
				$objPHPExcel->getActiveSheet ()->getStyle ( 'F' . $i . ':M' . $i )->getNumberFormat ()->setFormatCode ( '0.00' );
				$objPHPExcel->getActiveSheet ()->getStyle ( 'C' . $i )->getAlignment ()->setWrapText ( true );
				$i ++;
			}
			$last = $i - 1;
			$objPHPExcel->getActiveSheet ()->setCellValue ( "A" . ($i), "总    计：" )->setCellValue ( "G" . ($i), "=SUM(G8:G$last)" )->setCellValue ( "H" . ($i), "=SUM(H8:H$last)" )->setCellValue ( "K" . ($i), "=SUM(K8:K$last)" );
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
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'J' )->setWidth ( 7 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'K' )->setWidth ( 8 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'L' )->setWidth ( 8 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'M' )->setWidth ( 9 );
			
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A3' . ':M' . ($i) )->getFont ()->setName ( '宋体' )->setSize ( 9 );
			
			$styleArray = array (
					'borders' => array (
							'allborders' => array (
									
									'style' => \PHPExcel_Style_Border::BORDER_THIN 
							)
							 
					) 
			);
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1:M' . ($i) )->applyFromArray ( $styleArray );
			
			$objWriter = \PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' );
			$filename = "要货单" . $no . ".xls";
			header ( 'Content-Type: application/vnd.ms-excel' );
			header ( 'Content-Disposition: attachment;filename="' . $filename . '"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter->save ( 'php://output' );
		}
	}
}
