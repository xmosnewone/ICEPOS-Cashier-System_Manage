<?php
namespace app\admin\controller\wmsheet;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ESheetTrans;
use model\WmSheetDetail;
use model\WmSheetMaster;
use model\WholesaleClients;
use think\Db;
use model\BaseModel;
/**
 * 批发客户订单数据
 */
class Jsondata extends Super {
	
	/**
	 * 订单详细
	 */
	public function getWmSheetDetail() {
		$no = input ( "sheetno" );
		if (empty ( $no )) {
			return [ 
					'code' => false,
					'msg' => lang ( "invalid_variable" ) 
			];
		}
		
		$WmSheetDetail = new WmSheetDetail ();
		$model = $WmSheetDetail->GetSheetDetail ( $no );
		$result = array ();
		
		$i = 0;
		foreach ( $model as $k => $v ) {
			$result [$i] = $v;
			foreach ( $v as $kk => $vv ) {
				
				switch ($kk) {
					case "large_qty" :
						$result [$i] [$kk] = sprintf ( "%.2f", $vv );
						break;
					case "order_qty" :
						$result [$i] [$kk] = intval ( $vv );
						break;
					case "sub_amt" :
						$result [$i] [$kk] = sprintf ( "%.2f", $vv );
						break;
				}
			}
			
			$result [$i] ["rowIndex"] = $i + 1;
			$result [$i] ["item_name"] = $v ["item_name"];
			$result [$i] ["item_price"] = sprintf ( "%.2f", $v ["item_price"] );
			$result [$i] ["item_subno"] = $v ["item_subno"];
			$result [$i] ["sale_price"] = sprintf ( "%.2f", $v ["sale_price"] );
			$result [$i] ["item_size"] = $v ["item_size"];
			$result [$i] ["order_qty"] = $v ["real_qty"];
			$result [$i] ["unit_no"] = $v ["item_unit"];
			$result [$i] ["purchase_spec"] = $v ["purchase_spec"];
			$result [$i] ["purchase_tax"] = $v ["purchase_tax"];
			$result [$i] ["sale_amt"] = sprintf ( "%.2f", $v ["sale_price"] ) * $v ["real_qty"];
			$result [$i] ["memo"] = $v ["memo"];
			$i ++;
		}
		
		return listJson ( 0, lang ( "success_data" ), $i, $result );
	}
	
	/**
	 * 批发订单列表
	 */
	public function getWmSheetNoApproveList() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		
		$start = input ( "start" );
		$end = input ( "end" );
		$sheet_no = input ( "no" );
		$approve_flag = input ( "approve_flag" );
		$oper_id = input ( "oper_id" );
		$transno = input ( "transno" );
		$transno = input ( "transno" );
		$clients_no = input ( "clients_no" );
		$amount = input ( "amount" );
		$WmSheetMaster = new WmSheetMaster ();
		$result = $WmSheetMaster->GetPager ( $rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $transno,$clients_no,$amount);
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
	}
	
	public function getSheet() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		$transno = input ( "transno" );
		$keyword = input ( "keyword" );
		$where = ' AND w.approve_flag=1 AND w.reverse_flag=0 AND w.reverse_sheet is null';
		if ($transno == ESheetTrans::SS) {
			$where = " AND w.order_status=0";
		} else if ($transno == ESheetTrans::SO) {
			$transno = ESheetTrans::SO;
		} else {
			return [ 
					'code' => false,
					'msg' => lang ( "invalid_variable" ) 
			];
		}
		
		
		if (!empty($keyword)) {
			$where = " AND w.sheet_no like '%$keyword%'";
		}
		
		$WmSheetMaster = new WmSheetMaster ();
		$WholesaleClients = new WholesaleClients();
		
		$fieldSql = "SELECT w.sheet_no, case w.order_status when '2' then '全部调入' when '1' then '部分调入' else '未处理' end as order_status, p.linkname";
		$countSql = "select count(*) as total";
		$from = " FROM " . $WmSheetMaster->tableName () . " AS w" 
				. " LEFT JOIN " . $WholesaleClients->tableName () 
				. " as p ON p.clients_no=w.supcust_no where trans_no='" . $transno . "'";
		
		$res = Db::query ( $countSql . $from . $where );
		
		$result = array ();
		$result ["total"] = $res [0] ['total'];
		$offset = ($page - 1) * $rows;
		$model = Db::query ( $fieldSql . $from . $where . " order by w.oper_date desc limit $offset,$rows" );
		$ary = array ();
		$rowIndex = 1;
		foreach ( $model as $k => $v ) {
			$v ["rowIndex"] = $rowIndex;
			array_push ( $ary, $v );
			$rowIndex ++;
		}
		$result ["rows"] = $ary;
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
	}
	
	// 导出
	public function export() {
		$sheet_no = input ( "sheet_no" );
		if (empty ( $sheet_no )) {
			return [ 
					'code' => false,
					'msg' => lang ( "invalid_variable" ) 
			];
		}
		$WmSheetMaster = new WmSheetMaster ();
		$master = $WmSheetMaster->Get ( $sheet_no );
		$params ["sheetNo"] = $master ["sheet_no"];
		$params ["branchNo"] = $master ["branch_no"];
		$params ["branchName"] = $master ["branch_name"];
		$operDate = date ( "Y-m-d", strtotime ( $master ["oper_date"] ) );
		$params ["operId"] = $master ["oper_id"];
		$params ["consumerNo"] = $master ["supcust_no"];
		$params ["consumerName"] = $master ["linkname"];
		$params ["operName"] = $master ["oper_name"];
		$params ["confirmMan"] = $master ["confirm_man"];
		$params ["workDate"] = $master ["work_date"] == null ? "" : date ( "Y-m-d", strtotime ( $master ["work_date"] ) );
		$params ["txtValidDate"] = date ( "Y-m-d", strtotime ( $master ["valid_date"] ) );
		$orderMan = $master ["order_man"];
		if ($master ["approve_flag"] == 1) {
			$approve = "approve";
		} else {
			$approve = "update";
		}
		$params ["memo"] = $master ["memo"];
		$objPHPExcel = new \PHPExcel ();
		$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( "A1", "批发订单" );
		$objPHPExcel->getActiveSheet ()->mergeCells ( 'A1:M2' );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFill ()->setFillType ( \PHPExcel_Style_Fill::FILL_SOLID );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFont ()->setBold ( true );
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A3", "客     户：" )
										->setCellValue ( "B3", $params ["consumerName"] )
										->setCellValue ( "D3", "仓     库:" )
										->setCellValue ( "E3", $params ["branchName"] )
										->mergeCells ( 'E3:M3' )
										->setCellValue ( "A4", "业 务 员：" )
										->setCellValue ( "B4", '  ' . $params ["operName"] )
										->mergeCells ( 'B4:C4' )
										->setCellValue ( "D4", "有 效 期：" )
										->setCellValue ( "E4", $params ["txtValidDate"] )
										->mergeCells ( 'E4:H4' )
										->setCellValue ( "I4", "" )
										->setCellValue ( "J4", "" )
										->mergeCells ( 'J4:M4' )
										->setCellValue ( "A5", "制单人员：" )
										->setCellValue ( "B5", $orderMan )
										->mergeCells ( 'B5:C5' )
										->setCellValue ( "D5", "制单日期:" )
										->setCellValue ( "E5", $operDate )
										->mergeCells ( 'E5:H5' )
										->setCellValue ( "I5", "备    注：" )
										->setCellValue ( "J5", $params ["memo"] )
										->mergeCells ( 'I5:I6' )->mergeCells ( 'J5:M6' )
										->setCellValue ( "A6", "审核人员：" )
										->setCellValue ( "B6", $params ["confirmMan"] )
										->mergeCells ( 'B6:C6' )
										->setCellValue ( "D6", "审核日期：" )
										->setCellValue ( "E6", $params ["workDate"] )
										->mergeCells ( 'E6:H6' );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'I5' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'I5' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'J5' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'J5' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
		$WmSheetDetail=new WmSheetDetail();
		$model = $WmSheetDetail->GetSheetDetail ( $sheet_no );
		$result = array ();
		if (! empty ( $model )) {
			$i = 0;
			$objPHPExcel->getActiveSheet ()
						->setCellValue ( "A7", "行号" )
						->setCellValue ( "B7", "货号 " )
						->setCellValue ( "C7", "品名" )
						->mergeCells ( 'C7:D7' )
						->setCellValue ( "E7", "单位" )
						->setCellValue ( "F7", "规格" )
						->setCellValue ( "G7", "箱数" )
						->setCellValue ( "H7", "数量" )
						->setCellValue ( "I7", "进货规格" )
						->setCellValue ( "J7", "单价" )
						->setCellValue ( "K7", "金额" )
						->setCellValue ( "L7", "备注" )
						->mergeCells ( "L7:M7" );
			$i = 8;
			foreach ( $model as $v ) {
				$objPHPExcel->setActiveSheetIndex ( 0 )
							->setCellValue ( 'A' . $i, ($i - 7) )
							->setCellValue ( 'B' . $i, ' ' . $v ["item_no"] )
							->setCellValue ( 'C' . $i, $v ["item_name"] )
							->mergeCells ( 'C' . $i . ':D' . $i )
							->setCellValue ( 'E' . $i, $v ["item_unit"] )
							->setCellValue ( 'F' . $i, $v ["item_size"] )
							->setCellValue ( 'G' . $i, $v ["large_qty"] )
							->setCellValue ( 'H' . $i, $v ["real_qty"] )
							->setCellValue ( 'I' . $i, $v ["purchase_spec"] )
							->setCellValue ( 'J' . $i, $v ["item_price"] )
							->setCellValue ( 'K' . $i, $v ["sub_amt"] )
							->setCellValue ( 'L' . $i, $v ["memo"] )
							->mergeCells ( 'L' . $i . ':M' . $i );
				$objPHPExcel->getActiveSheet ()->getStyle ( 'A' . $i . ':F' . $i )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
				$objPHPExcel->getActiveSheet ()->getStyle ( 'A' . $i . ':M' . $i )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
				$objPHPExcel->getActiveSheet ()->getStyle ( 'F' . $i . ':M' . $i )->getNumberFormat ()->setFormatCode ( '0.00' );
				$objPHPExcel->getActiveSheet ()->getStyle ( 'C' . $i )->getAlignment ()->setWrapText ( true );
				$i ++;
			}
			$last = $i - 1;
			$objPHPExcel->getActiveSheet ()
						->setCellValue ( "A" . ($i), "总    计：" )
						->setCellValue ( "G" . ($i), "=SUM(G8:G$last)" )
						->setCellValue ( "H" . ($i), "=SUM(H8:H$last)" )
						->setCellValue ( "K" . ($i), "=SUM(K8:K$last)" );
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
			$filename = "批发订单" . $no . ".xls";
			header ( 'Content-Type: application/vnd.ms-excel' );
			header ( 'Content-Disposition: attachment;filename="' . $filename . '"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter->save ( 'php://output' );
		} else {
			return [ 
					'code' => false,
					'msg' => lang ( "invalid_variable" ) 
			];
		}
	}
}
