<?php
namespace app\admin\controller\check;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ECheckRange;
use model\CheckMaster;
use model\CheckSum;
use model\CheckInit;
use model\CheckDetail;
use think\Db;

/**
 * 盘点单列表等
 */
class Jsondata extends Super {
	
	// 库存盘点单列表
	public function getCrsheet() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		$start = input ( "start" );
		$end = input ( "end" );
		$sheet_no = input ( "no" );
		$approve_flag = input ( "approve_flag" );
		$oper_id = input ( "oper_id" );
		$transno = "CR";
		$branch_no = input ( "branch_no" );
		$CheckMaster = new CheckMaster ();
		$result = $CheckMaster->GetPager ( $rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $transno, $branch_no );
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
	}
	
	//获取未被审核的盘点批号
	public function getNotUsedPdSheet() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		$offset = ($page - 1) * $rows;
		
		$where="i.approve_flag='0'";
		$keyword=input("keyword");
		if (!empty($keyword)) {
			$where.=" and i.sheet_no like '%$keyword%' or b.branch_name like '%$keyword%'";
		}
		
		$list=Db::name("im_check_init")
		->alias('i')
		->field("i.sheet_no, i.branch_no, b.branch_name,i.oper_id,i.start_time,i.oper_range, i.check_cls, i.memo")
		->join('pos_branch_info b','b.branch_no=i.branch_no',"LEFT")
        ->order("i.oper_date desc")
		->where($where)
		->limit($offset,$rows)
		->select();
		
		$rowCount=Db::name("im_check_init")
		->alias('i')
		->join('pos_branch_info b','b.branch_no=i.branch_no',"LEFT")
		->where($where)
		->count();
		
		$model =new CheckInit ();
		
		$temp = array ();
		$colmns = "sheet_no,branch_no,branch_name,oper_range,check_cls,oper_id,start_time,memo";
		$rowIndex = 1;
		foreach ( $list as $k => $v ) {
			$tt = array ();
			$tt ["rowIndex"] = $rowIndex;
			foreach ( $v as $kk => $vv ) {
				if (in_array ( $kk, explode ( ',', $colmns ) )) {
					if ($kk == "oper_range") {
						$vv = $model->GetChectRange ( $vv );
					}
					if ($kk == "start_time") {
						
						$vv = date ( "Y-m-d", strtotime ( $vv ) );
					}
					$tt [$kk] = $vv;
					$tt ["branch_name"] = $v ["branch_name"];
				}
			}
			
			array_push ( $temp, $tt );
			$rowIndex ++;
		}

		return listJson ( 0, lang ( "success_data" ), $rowCount,$temp );
	}
	
	//返回盘点单内详细商品信息
	public function getCrsheetDetailList() {
		$sheetno = input ( "no" );
		if (empty ( $sheetno )) {
			return [
					'code' => false,
					'msg' => lang ( "pd_sheetno_empty" )
			];
		}
		
		$array = array ();
		$CheckDetail=new CheckDetail();
		$array ["rows"] =$CheckDetail->GetCheckDetailBySheetno ( $sheetno );
		return listJson ( 0, lang ( "success_data" ), count ( $array ['rows'] ), $array ['rows'] );
	}
	
	//差异处理列表
	public function getPdsheet() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		$start = input ( "start" );
		$end = input ( "end" );
		$sheet_no = input ( "no" );
		$approve_flag = input ( "approve_flag" );
		$oper_id = input ( "oper_id" );
		$transno = input ( "transno" );
		$branch_no = input ( "branch_no" );
		$CheckInit = new CheckInit ();
		$result = $CheckInit->GetPager ( $rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $transno, $branch_no);
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
	}
	
	//差异处理盘点单详细商品信息
	public function getPdsheetDetailList() {
		$sheetno = input ( "no" );
		$rangeno = input ( "rangeno" );
		$branchno = input ( "branchno" );
		if (empty ( $sheetno )) {
			return [ 
					'code' => 2,
					'msg' => lang ( "pd_sheetno_empty" ) 
			];
		}
		
		$array = array ();
		$CheckSumDb = new CheckSum ();
		if ($rangeno == ECheckRange::ALL) {
			$prefix=$this->dbprefix;
			$sql = "SELECT s.item_no,i.item_subno,i.price as item_price,i.item_name,i.unit_no,i.item_size
					,s.stock_qty as item_stock,i.purchase_spec,i.sale_price" . " FROM ".$prefix."pos_branch_stock AS s " 
					. " INNER JOIN ".$prefix."bd_item_info AS i ON s.item_no=i.item_no" . " WHERE s.branch_no='" . $branchno . "' ";
			$stockNum = Db::query ( $sql );

			$checkSum = $CheckSumDb->GetCheckDetailBySheetno ( $sheetno );
			$result = array ();
			$rowIndex = 1;
			$tt = array ();
			foreach ( $stockNum as $key => $value ) {
				$temp = array ();
				foreach ( $checkSum as $k => $v ) {
					if ($value ['item_no'] == $v ['item_no']) {
						$temp ['item_no'] = $v ['item_no'];
						$temp ['item_subno'] = $value ['item_subno'];
						$temp ['item_name'] = $value ['item_name'];
						$temp ['unit_no'] = $value ['unit_no'];
						$temp ['item_size'] = $value ['item_size'];
						$temp ['check_qty'] = sprintf ( "%.2f", $v ['check_qty'] );
						$temp ['item_stock'] = sprintf ( "%.2f", $v ['item_stock'] );
						$temp ['sale_price'] = sprintf ( "%.2f", $v ['sale_price'] );
						$temp ['item_price'] = sprintf ( "%.2f", $v ['item_price'] );
						$temp ['purchase_spec'] = sprintf ( "%.2f", $value ['purchase_spec'] );
						$temp ["sale_amt"] = sprintf ( "%.2f", $temp ["check_qty"] * $temp ["sale_price"] );
						$temp ["balance_qty"] = sprintf ( "%.2f", $temp ["check_qty"] - $temp ['item_stock'] );
                        $temp ['memo'] = $v ['memo'];
					}
				}
				if (empty ( $temp )) {
					$temp ['item_no'] = $value ['item_no'];
					$temp ['item_subno'] = $value ['item_subno'];
					$temp ['item_name'] = $value ['item_name'];
					$temp ['unit_no'] = $value ['unit_no'];
					$temp ['item_size'] = $value ['item_size'];
					$temp ['check_qty'] = sprintf ( "%.2f", 0 );
					$temp ['item_stock'] = sprintf ( "%.2f", $value ['item_stock'] );
					$temp ['sale_price'] = sprintf ( "%.2f", $value ['sale_price'] );
					$temp ['item_price'] = sprintf ( "%.2f", $value ['item_price'] );
					$temp ['purchase_spec'] = sprintf ( "%.2f", $value ['purchase_spec'] );
					$temp ["sale_amt"] = sprintf ( "%.2f", $value ["check_qty"] * $value ["sale_price"] );
					$temp ["balance_qty"] = sprintf ( "%.2f", $temp ["check_qty"] - $temp ['item_stock'] );
				}
				$temp ["rowIndex"] = $rowIndex;
				$tt = $temp;
				array_push ( $result, $tt );
				$rowIndex ++;
			}
			$array ["rows"] = $result;
		} else {
			$array ["rows"] = $CheckSumDb->GetCheckDetailBySheetno ( $sheetno );
		}
		
		return listJson ( 0, lang ( "success_data" ), count ( $array ['rows'] ), $array ['rows'] );
	}
	
	/**
	 * 导出盘点单
	 */
	public function exportPdsheet() {
		$sheetno = input ( "no" );
		$CheckSum = new CheckSum ();
		$CheckInit = new CheckInit ();
		$model = $CheckSum->GetCheckDetailBySheetno ( $sheetno );
		$checkMaster = $CheckInit->GetArraySheet ( trim ( $sheetno ) );
		$colmns = "item_no,item_name,unit_no,item_size,item_stock,item_stock,check_qty,balance_qty";
		$temp = array ();
		$branchname = $checkMaster ["branch_name"];
		
		foreach ( $model as $k => $v ) {
			$tt = array ();
			foreach ( $v as $kk => $vv ) {
				if (in_array ( $kk, explode ( ',', $colmns ) )) {
					$tt [$kk] = $vv;
				}
			}
			array_push ( $temp, $tt );
		}
		if (empty ( $temp )) {
			exit ( "暂无数据" );
		} else {
			@ini_set ( 'memory_limit', '128M' );
			$objPHPExcel = new \PHPExcel ();
			$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( "A1", $branchname . '盘点单号【' . $sheetno . "】" );
			$objPHPExcel->getActiveSheet ()->mergeCells ( 'A1:H2' );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFill ()->setFillType ( \PHPExcel_Style_Fill::FILL_SOLID );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFont ()->setBold ( true );
			$objPHPExcel->getActiveSheet ()->setCellValue ( "A3", "序号 " )->setCellValue ( "B3", "货号 " )->setCellValue ( "C3", "商品名称" )->setCellValue ( "D3", "单位" )->setCellValue ( "E3", "规格" )->setCellValue ( "F3", "库存数量" )->setCellValue ( "G3", "实际盘点数量" )->setCellValue ( "H3", "盈亏数量" );
			$i = 4;
			foreach ( $temp as $v ) {
				$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( 'A' . $i, $i - 3 )->setCellValueExplicit ( 'B' . $i, $v ["item_no"], \PHPExcel_Cell_DataType::TYPE_STRING )->setCellValue ( 'C' . $i, $v ["item_name"] )->setCellValue ( 'D' . $i, $v ["unit_no"] )->setCellValue ( 'E' . $i, $v ["item_size"] )->setCellValue ( 'F' . $i, $v ["item_stock"] )->setCellValue ( 'G' . $i, $v ["check_qty"] )->setCellValue ( 'H' . $i, $v ["balance_qty"] );
				$i ++;
			}
			$last = $i - 1;
			$objPHPExcel->getActiveSheet ()->setCellValue ( "A" . ($i), "总    计：" )->setCellValue ( "F" . ($i), "=SUM(F4:F$last)" )->setCellValue ( "G" . ($i), "=SUM(G4:G$last)" )->setCellValue ( "H" . ($i), "=SUM(H4:H$last)" );
			$objPHPExcel->getActiveSheet ()->setCellValue ( "E" . ($i + 1), "制单人：" . $checkMaster ['oper_id'] )->setCellValue ( "G" . ($i + 1), "制单时间：" . $checkMaster ['oper_date'] );
			$objPHPExcel->getActiveSheet ()->mergeCells ( 'E' . ($i + 1) . ':F' . ($i + 1) );
			$objPHPExcel->getActiveSheet ()->mergeCells ( 'G' . ($i + 1) . ':H' . ($i + 1) );
			$objPHPExcel->getActiveSheet ()->setCellValue ( "E" . ($i + 2), "审核人：" . $checkMaster ['confirm_man'] )->setCellValue ( "G" . ($i + 2), "审核时间：" . $checkMaster ['work_date'] );
			$objPHPExcel->getActiveSheet ()->mergeCells ( 'E' . ($i + 2) . ':F' . ($i + 2) );
			$objPHPExcel->getActiveSheet ()->mergeCells ( 'G' . ($i + 2) . ':H' . ($i + 2) );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'F' . $i . ':H' . $i )->getNumberFormat ()->setFormatCode ( '0.00' );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'A' )->setWidth ();
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'B' )->setWidth ( 20 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'C' )->setWidth ( 30 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'D' )->setWidth ( 8 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'E' )->setWidth ( 10 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'F' )->setWidth ( 8 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'G' )->setWidth ( 8 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'H' )->setWidth ( 8 );
			
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A3' . ':H' . ($i + 2) )->getFont ()->setName ( '宋体' )->setSize ( 9 );
			
			$styleArray = array (
					'borders' => array (
							'allborders' => array (
									'style' => \PHPExcel_Style_Border::BORDER_THIN 
							) 
					) 
			);
			
			$cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
			$cacheSettings = array (
					' memoryCacheSize ' => '8MB' 
			);
			\PHPExcel_Settings::setCacheStorageMethod ( $cacheMethod, $cacheSettings );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1:H' . ($i) )->applyFromArray ( $styleArray );
			
			$objWriter = \PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' );
			$filename = date ( DATETIME_FORMAT, time () ) . $branchname . "盘点号【" . $sheetno . "】盘点明细.xls";
			header ( 'Content-Type: application/vnd.ms-excel' );
			header ( 'Content-Disposition: attachment;filename="' . $filename . '"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter->save ( 'php://output' );
		}
	}
}
