<?php
namespace app\admin\controller\stock;
use app\admin\controller\Super;
use model\Item_info;
use model\Item_cls;
use model\PosBranchStock;
use model\PosBranch;
use model\Supcust;
use think\Db;

/**
 * 库存查询
 */
class Search extends Super {
	public function index() {
		return $this->fetch ( "stock/search" );
	}
	public function search() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		
		$branch_no = input ( "branch_no" );
		$brand_no = input ( "brand_no" );
		$class_no = input ( "class_no" );
		$sp_no = trim ( input ( "sp_no" ) );
		$item_name = input ( "item_name" );
		$item_no = input ( "item_no" );
		$stock_big = input ( "stock_big" );
		$stock_small = input ( "stock_small" );
		$chkstock_notnil = input ( "chkstock_notnil" );
		$array ["branch_no"] = $branch_no;
		$array ["brand_no"] = $brand_no;
		$array ["class_no"] = $class_no;
		$array ["sp_no"] = $sp_no;
		$array ["item_name"] = $item_name;
		$array ["item_no"] = $item_no;
		$array ["stock_big"] = $stock_big;
		$array ["stock_small"] = $stock_small;
		$array ["chkstock_notnil"] = $chkstock_notnil;
		
		$result = $this->GetSeachPager ( $rows, $page, $array );
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
	}
	private function GetSeachPager($rows, $page, $array) {
		$Item_info = new Item_info ();
		$Item_cls = new Item_cls ();
		$PosBranchStock = new PosBranchStock ();
		$PosBranch = new PosBranch ();
		$Supcust = new Supcust ();
		
		$fieldSql = "select f.branch_no, f.branch_name,i.item_no,i.item_name,i.item_brand,i.purchase_spec,item_rem,i.item_brandname
				,i.price,i.item_clsno,c.item_clsname,i.sale_price,i.unit_no,i.item_size,b.stock_qty,p.sp_company";
		
		$countSql = "select count(*) as total";
		
		$from = " from " . $Item_info->tableName () . " as i " 
				. "left join " . $Item_cls->tableName () . " as c on i.item_clsno=c.item_clsno " 
				. "right join " . $PosBranchStock->tableName () . " as b on i.item_no=b.item_no " 
				. "left join " . $PosBranch->tableName () . " as f on f.branch_no=b.branch_no " 
				. "left join " . $Supcust->tableName () . " as p on i.main_supcust=p.sp_no";
		
		if (! empty ( $array )) {
			$prefix=$this->dbprefix;
			if ($array ["chkstock_notnil"] == "on") {
				$sql .= " where b.stock_qty > 0";
			} else {
				$sql .= " where b.item_no != ''";
			}
			if (! empty ( $array ["branch_no"] )) {
				$sql .= " and b.branch_no='" . $array ["branch_no"] . "'";
			}
			if (! empty ( $array ["brand_no"] )) {
				$sql .= " and i.item_brand='" . $array ["brand_no"] . "'";
			}
			if (! empty ( $array ["class_no"] )) {
				$sql .= " and i.item_clsno='" . $array ["class_no"] . "'";
			}
			if (! empty ( $array ["sp_no"] )) {
				$sql .= " and i.main_supcust='" . $array ["sp_no"] . "'";
			}
			if (! empty ( $array ["item_name"] )) {
				$sql .= " and i.item_name like '%" . $array ["item_name"] . "%'";
			}
			if (! empty ( $array ["item_no"] )) {
				$sql .= " and (i.item_no like '%" . $array ["item_no"] . "%' or i.item_no in 
						(select item_no from ".$prefix."bd_item_barcode where item_barcode like '%" . $array ["item_no"] . "%')) ";
			}
			if (! empty ( $array ["stock_big"] )) {
				$sql .= " and b.stock_qty >= " . $array ["stock_big"];
			}
			if (! empty ( $array ["stock_small"] )) {
				$sql .= " and b.stock_qty <=" . $array ["stock_small"];
			}
		} else {
			
			$sql .= " where b.stock_qty > 0 ";
		}
		
		$cres = Db::query ( $countSql . $from . $sql );
		$result ["total"] = $cres [0] ['total'];
		
		$offset = ($page - 1) * $rows;
		$rowIndex = ($page - 1) * $rows + 1;
		
		$model = Db::query ( $fieldSql . $from . $sql . " limit $offset,$rows" );
		
		$colmns = "branch_no,branch_name,item_no,item_name,item_brand,item_brandname,purchase_spec,item_rem
					,price,item_clsno,item_clsname,sale_price,unit_no,item_size,stock_qty,sp_company";
		$temp = array ();
		foreach ( $model as $k => $v ) {
			$tt = array ();
			$tt ["rowIndex"] = $rowIndex;
			foreach ( $v as $kk => $vv ) {
				if (in_array ( $kk, explode ( ',', $colmns ) )) {
					$tt [$kk] = $vv;
				}
			}
			array_push ( $temp, $tt );
			$rowIndex ++;
		}
		$result ["rows"] = $temp;
		return $result;
	}
	
	// 导出库存
	public function export() {
		$branch_no = input ( "branch_no" );
		$brand_no = input ( "brand_no" );
		$class_no = input ( "class_no" );
		$sp_no = input ( "sp_no" );
		$item_name = input ( "item_name" );
		$item_no = input ( "item_no" );
		$stock_big = input ( "stock_big" );
		$stock_small = input ( "stock_small" );
		$chkstock_notnil = input ( "chkstock_notnil" );
		$array ["branch_no"] = $branch_no;
		$array ["brand_no"] = $brand_no;
		$array ["class_no"] = $class_no;
		$array ["sp_no"] = $sp_no;
		$array ["item_name"] = $item_name;
		$array ["item_no"] = $item_no;
		$array ["stock_big"] = $stock_big;
		$array ["stock_small"] = $stock_small;
		$array ["chkstock_notnil"] = $chkstock_notnil;
		
		$Item_info = new Item_info ();
		$Item_cls = new Item_cls ();
		$PosBranchStock = new PosBranchStock ();
		$PosBranch = new PosBranch ();
		$Supcust = new Supcust ();
		
		$sql = "select f.branch_no, f.branch_name,i.item_no,i.item_name,i.item_brand,i.purchase_spec,item_rem
				,i.item_brandname,i.price,i.item_clsno,c.item_clsname,i.sale_price,i.unit_no,i.item_size,b.stock_qty,p.sp_company 
				from " . $Item_info->tableName () . " as i " 
				. "left join " . $Item_cls->tableName () . " as c on i.item_clsno=c.item_clsno " 
				. "right join " . $PosBranchStock->tableName () . " as b on i.item_no=b.item_no " 
				. "left join " . $PosBranch->tableName () . " as f on f.branch_no=b.branch_no " 
				. "left join " . $Supcust->tableName () . " as p on i.main_supcust=p.sp_no";
		
		if (! empty ( $array )) {
			$prefix=$this->dbprefix;
			if ($array ["chkstock_notnil"] == "on") {
				$sql .= " where b.stock_qty > 0";
			} else {
				$sql .= " where b.item_no != ''";
			}
			if (! empty ( $array ["branch_no"] )) {
				$sql .= " and b.branch_no='" . $array ["branch_no"] . "'";
			}
			if (! empty ( $array ["brand_no"] )) {
				$sql .= " and i.item_brand='" . $array ["brand_no"] . "'";
			}
			if (! empty ( $array ["class_no"] )) {
				$sql .= " and i.item_clsno='" . $array ["class_no"] . "'";
			}
			if (! empty ( $array ["sp_no"] )) {
				$sql .= " and i.main_supcust='" . $array ["sp_no"] . "'";
			}
			if (! empty ( $array ["item_name"] )) {
				$sql .= " and i.item_name like '%" . $array ["item_name"] . "%'";
			}
			if (! empty ( $array ["item_no"] )) {
				$sql .= " and (i.item_no like '%" . $array ["item_no"] . "%' or i.item_no in 
						(select item_no from ".$prefix."bd_item_barcode where item_barcode like '%" . $array ["item_no"] . "%')) ";
			}
			if (! empty ( $array ["stock_big"] )) {
				$sql .= " and b.stock_qty >= " . $array ["stock_big"];
			}
			if (! empty ( $array ["stock_small"] )) {
				$sql .= " and b.stock_qty <=" . $array ["stock_small"];
			}
		} else {
			
			$sql .= " where b.stock_qty > 0 ";
		}
		
		$model = Db::query ( $sql );
		$colmns = "branch_no,branch_name,item_no,item_name,item_brand,item_brandname,purchase_spec,item_rem
					,price,item_clsno,item_clsname,sale_price,unit_no,item_size,stock_qty,sp_company";
		$temp = array ();
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
			$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( "A1", "库存查询" );
			$objPHPExcel->getActiveSheet ()->mergeCells ( 'A1:M2' );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFill ()->setFillType ( \PHPExcel_Style_Fill::FILL_SOLID );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFont ()->setBold ( true );
			$objPHPExcel->getActiveSheet ()
						->setCellValue ( "A3", "分店名称 " )
						->setCellValue ( "B3", "货号" )
						->setCellValue ( "C3", "品名" )
						->setCellValue ( "D3", "单位" )
						->setCellValue ( "E3", "规格" )
						->setCellValue ( "F3", "库存" )
						->setCellValue ( "G3", "箱装数" )
						->setCellValue ( "H3", "成本价" )
						->setCellValue ( "I3", "售价" )
						->setCellValue ( "J3", "类别" )
						->setCellValue ( "K3", "品牌名称" )
						->setCellValue ( "L3", "供应商名称" )->mergeCells ( "L3:M3" );
			$i = 4;
			$priceSum = 0;
			$salePriceSum = 0;
			foreach ( $temp as $v ) {
				$objPHPExcel->setActiveSheetIndex ( 0 )
							->setCellValue ( 'A' . $i, $v ["branch_name"] )
							->setCellValue ( 'B' . $i, ' ' . $v ["item_no"] )
							->setCellValue ( 'C' . $i, $v ["item_name"] )
							->setCellValue ( 'D' . $i, $v ["unit_no"] )
							->setCellValue ( 'E' . $i, $v ["item_size"] )
							->setCellValue ( 'F' . $i, $v ["stock_qty"] )
							->setCellValue ( 'G' . $i, $v ["purchase_spec"] )
							->setCellValue ( 'H' . $i, $v ["price"] )
							->setCellValue ( 'I' . $i, $v ["sale_price"] )
							->setCellValue ( 'J' . $i, $v ["item_clsname"] )
							->setCellValue ( 'K' . $i, $v ["item_brandname"] )
							->setCellValue ( 'L' . $i, $v ["sp_company"] )
							->mergeCells ( "L" . $i . ":M" . $i );
				$priceSum = $priceSum + $v ["price"] * $v ["stock_qty"];
				$salePriceSum = $salePriceSum + $v ["sale_price"] * $v ["stock_qty"];
				$i ++;
			}
			$last = $i - 1;
			$objPHPExcel->getActiveSheet ()
						->setCellValue ( "A" . ($i), "总    计：" )
						->setCellValue ( "F" . ($i), "=SUM(F4:F$last)" )
						->setCellValue ( "H" . ($i), $priceSum )->setCellValue ( "I" . ($i), $salePriceSum );
			
			$objPHPExcel->getActiveSheet ()->getStyle ( 'F' . $i . ':I' . $i )->getNumberFormat ()->setFormatCode ( '0.00' );
			
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
			
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A3' . ':M' . ($i) )->getFont ()->setName ( '宋体' )->setSize ( 9 );
			
			$styleArray = array (
					'borders' => array (
							'allborders' => array (
									'style' => \PHPExcel_Style_Border::BORDER_THIN 
							) 
					)
					 
			);
			
			$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
			$cacheSettings = array (
					' memoryCacheSize ' => '8MB' 
			);
			\PHPExcel_Settings::setCacheStorageMethod ( $cacheMethod, $cacheSettings );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1:M' . ($i) )->applyFromArray ( $styleArray );
			
			$objWriter = \PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' );
			$filename = "库存查询.xls";
			header ( 'Content-Type: application/vnd.ms-excel' );
			header ( 'Content-Disposition: attachment;filename="' . $filename . '"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter->save ( 'php://output' );
		}
	}
	
	// 按大类汇总库存
	public function classStock() {
		return $this->fetch ( 'stock/classstock' );
	}
	
	// 数据
	public function classStockData() {
		$clsno = input ( 'clsno' );
		$branchno = input ( 'branchno' );
		$clsAllSql = '';
		if (empty ( $clsno ) && empty ( $branchno )) {
			$clsAllSql = 'SELECT * FROM v_cls_stock';
		} else {
			
			$clsAllSql = 'SELECT * FROM v_cls_stock WHERE 1=1';
			if ($branchno != '') {
				
				$clsAllSql .= ' AND branch_no="' . $branchno . '"';
			}
			if ($clsno != '') {
				
				$clsAllSql .= ' AND item_clsno="' . $clsno . '"';
			}
		}
		
		if ($clsAllSql == '') {
			return listJson ( 0, lang ( "success_data" ), 0, [ ] );
		}
		
		$result = Db::query ( $clsAllSql );
		$rowIndex = 1;
		foreach ( $result as $v ) {
			$tt = array ();
			$tt ["rowIndex"] = $rowIndex;
			$tt ["branchno"] = $v ["branch_no"];
			$tt ["branchName"] = $v ["branch_name"];
			$tt ["itemClsno"] = $v ["item_clsno"];
			$tt ["itemClsname"] = $v ["item_clsname"];
			$tt ["cost"] = sprintf ( "%.2f", doubleval ( $v ["cost"] ) );
			$tt ["salePrice"] = sprintf ( "%.2f", doubleval ( $v ["salePrice"] ) );
			$tt ["allStockQty"] = sprintf ( "%.2f", doubleval ( $v ["allStockQty"] ) );
			$rowIndex ++;
			array_push ( $res, $tt );
		}
		
		return listJson ( 0, lang ( "success_data" ), count ( $result ), $res );
	}
	public function exportClassItemStock() {
		$clsno = input ( 'clsno' );
		$branchno = input ( 'branchno' );
		$sql = 'SELECT * FROM v_cls_stock_detail WHERE 1=1';
		if ($branchno != '') {
			$sql .= ' AND branch_no="' . $branchno . '"';
		}
		if ($clsno != '') {
			$sql .= ' AND item_clsno="' . $clsno . '"';
		}
		
		$model = Db::query ( $sql );
		$colmns = "branch_no,branch_name,item_clsno,item_clsname,item_no,item_name,code_name,item_size,stock_qty";
		$temp = array ();
		$branchname = '';
		$classname = '';
		
		foreach ( $model as $k => $v ) {
			$tt = array ();
			$branchname = $v ['branch_name'];
			$classname = $v ['item_clsname'];
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
			$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( "A1", $branchname . '【' . $classname . "】当前库存数量" );
			$objPHPExcel->getActiveSheet ()->mergeCells ( 'A1:G2' );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFill ()->setFillType ( \PHPExcel_Style_Fill::FILL_SOLID );
			
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFont ()->setBold ( true );
			$objPHPExcel->getActiveSheet ()
						->setCellValue ( "A3", "序号 " )
						->setCellValue ( "B3", "分店名称 " )
						->setCellValue ( "C3", "分类名称" )
						->setCellValue ( "D3", "货号" )
						->setCellValue ( "E3", "商品名称" )
						->setCellValue ( "F3", "规格" )
						->setCellValue ( "G3", "库存数量" );
			$i = 4;
			foreach ( $temp as $v ) {
				$objPHPExcel->setActiveSheetIndex ( 0 )
							->setCellValue ( 'A' . $i, $i - 3 )
							->setCellValue ( 'B' . $i, $v ["branch_name"] )
							->setCellValue ( 'C' . $i, $v ["item_clsname"] )
							->setCellValueExplicit ( 'D' . $i, $v ["item_no"], \PHPExcel_Cell_DataType::TYPE_STRING )
							->setCellValue ( 'E' . $i, $v ["item_name"] )->setCellValue ( 'F' . $i, $v ["item_size"] )
							->setCellValue ( 'G' . $i, $v ["stock_qty"] );
				$i ++;
			}
			$last = $i - 1;
			$objPHPExcel->getActiveSheet ()->setCellValue ( "A" . ($i), "总    计：" )->setCellValue ( "G" . ($i), "=SUM(G4:G$last)" );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'G' . $i . ':G' . $i )->getNumberFormat ()->setFormatCode ( '0.00' );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'A' )->setWidth ();
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'B' )->setWidth ( 10 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'C' )->setWidth ( 15 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'D' )->setWidth ( 20 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'E' )->setWidth ( 30 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'F' )->setWidth ( 10 );
			$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'G' )->setWidth ( 8 );
			
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A3' . ':G' . ($i) )->getFont ()->setName ( '宋体' )->setSize ( 9 );
			
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
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A1:G' . ($i) )->applyFromArray ( $styleArray );
			
			$objWriter = \PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' );
			$filename = date ( DATETIME_FORMAT, time () ) . ' ' . $branchname . '【' . $classname . "】库存.xls";
			header ( 'Content-Type: application/vnd.ms-excel' );
			header ( 'Content-Disposition: attachment;filename="' . $filename . '"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter->save ( 'php://output' );
		}
	}
}
