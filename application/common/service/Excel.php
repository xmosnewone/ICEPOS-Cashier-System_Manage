<?php
namespace app\common\service;

use think\Controller;
use think\Session;
use think\Url;
use think\Db;
use think\facade\Request;

class Excel
{
	/**
	 * 读取Excel文件
	 */
	public function read_excel($file,$isUnlink=true) {
		if (! file_exists ( $file )) { //如果没有上传文件或者文件错误
			return array ("error" => 1 );
		}
		//spl_autoload_register ( array ('Think', 'autoload' ) ); //必须的，不然ThinkPHP和PHPExcel会冲突
		$PHPExcel = new \PHPExcel ();
		$PHPReader = new \PHPExcel_Reader_Excel2007 (); //测试能不能读取2007excel文件
		if (! $PHPReader->canRead ( $file )) {
			$PHPReader = new \PHPExcel_Reader_Excel5 (); //测试能不能读取2003excel文件
			if (! $PHPReader->canRead ( $file )) {
				return array ("error" => 2 );
			}
		
		}
		$PHPReader->setReadDataOnly ( true );
		$PHPExcel = $PHPReader->load ( $file );
		$SheetCount = $PHPExcel->getSheetCount ();
		for($i = 0; $i < $SheetCount; $i ++) { // 可以导入一个excel文件的多个工作区
			$currentSheet = $PHPExcel->getSheet ( $i ); //当前工作表
			$allColumn = $this->ExcelChange ( $currentSheet->getHighestColumn () ); //当前工作表总共有多少列
			$allRow = $currentSheet->getHighestRow (); //当前工作表的行数
			$array [$i] ["Title"] = $currentSheet->getTitle ();
			$array [$i] ["Cols"] = $allColumn;
			$array [$i] ["Rows"] = $allRow;
			$arr = array ();
			for($currentRow = 1; $currentRow <= $allRow; $currentRow ++) {
				$row = array ();
				for($currentColumn = 0; $currentColumn < $allColumn; $currentColumn ++) {
						
					if ($currentSheet->getCellByColumnAndRow ( $currentColumn, $currentRow )->getValue () instanceof PHPExcel_RichText) {
						$row [$currentColumn] = $currentSheet->getCellByColumnAndRow ( $currentColumn, $currentRow )->getValue ()->getRichTextElements ();
					} else {
						$row [$currentColumn] = $currentSheet->getCellByColumnAndRow ( $currentColumn, $currentRow )->getValue ();
					}
		
				}
				$arr [$currentRow] = $row;
			}
			$array [$i] ["Content"] = $arr;
		}
		
		unset ( $currentSheet );
		unset ( $PHPReader );
		unset ( $PHPExcel );
		if($isUnlink==true){
			unlink ( $file );
		}
		return array ("error" => 0, "data" => $array ); //返回数据
	}
	
	//导入的辅助函数
	public function ExcelChange($str) { //配合Execl批量导入的函数
		$len = strlen ( $str ) - 1;
		$num = 0;
		for($i = $len; $i >= 0; $i --) {
			$num += (ord ( $str [$i] ) - 64) * pow ( 26, $len - $i );
		}
		return $num;
	}
	
	//导出单个工作表数据
	//$doc 要都出的文件基本信息数组
	//$field是要导出的字段名称的数组
	//$line_title是导出的excel的第一行标题数组，不是数据
	//$ex 要导出的excel 文件的版本默认是2007,可以使用2003版本的
	//$jumpurl 是默认跳转的页面
	//$un_need 不需要导出的字段名
	//$field 字段数目应该要与$line_title的长度是一样的
	//$merge_line 是要合并一起的行的数组 ['A2:A10','A11:A25'...]
	public function export_excel($list, $field, $line_title,$doc,$un_need = array(), $ex = '2007',$merge_line=array()) {
	
		if ($list === false||count($list)<=0) {
			return false;
		}
	
		//最多导出60个字段，可以继续增加
		$Excel_letter = array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
				'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
				'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF',
				'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP',
				'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
				'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ',
				'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT',
				'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CB', 'CC', 'CD' );
		//spl_autoload_register ( array ('Think', 'autoload' ) ); //必须的，不然ThinkPHP和PHPExcel会冲突
		$objExcel = new \PHPExcel ();
	
		//设置导出文档的文件基本属性
		$objExcel->getProperties ()->setCreator ( $doc ['creator'] );
		$objExcel->getProperties ()->setLastModifiedBy ( $doc ['creator'] );
		$objExcel->getProperties ()->setTitle ( $doc ['title'] );
		$objExcel->getProperties ()->setSubject ( $doc ['subject'] );
		$objExcel->getProperties ()->setDescription ( $doc ['description'] );
		$objExcel->getProperties ()->setKeywords ( $doc ['keywords'] );
		$objExcel->getProperties ()->setCategory ( $doc ['category'] );
		$objExcel->setActiveSheetIndex ( 0 ); //第一个工作表
	
	
		//设置表头--即Excel的第一行数据
		foreach ( $line_title as $key => $value ) {
			$objExcel->getActiveSheet ()->setCellValue ( $Excel_letter [$key] . "1", $value ); //$key 格式是:A1 $value是字段的中文名称
		}
	
	
		$start_line = 0; //从第几行开始写入数据，一般是从第二行开始
			
	
		/*----------写入内容-------------*/
		foreach ( $list as $key => $value ) {
			$line = $start_line + 2;
			for($k = 0; $k < count ( $field ); $k ++) {
				if (! in_array ( $field [$k], $un_need )) {
					//不输出指定的字符串
					$objExcel->getActiveSheet ()->getStyle ( $Excel_letter [$k] )->getNumberFormat ()->setFormatCode ( \PHPExcel_Style_NumberFormat::FORMAT_TEXT );
					$objExcel->getActiveSheet ()->setCellValueExplicit ( $Excel_letter [$k] . $line, $value [$field [$k]], \PHPExcel_Cell_DataType::TYPE_STRING );
				}
			}
			$start_line ++; //移动到下一行
		}
	
		//合并某列的某些行 A2:A7 A8:A20...
		if(count($merge_line)>0){
			foreach($merge_line as $group){
				$objExcel->getActiveSheet()->mergeCells($group);
			}
		}
		
		// 高置列的宽度  $Excel_letter[$i] 代表的该列的名称 例如 A B C ...
		for($i = 0; $i < count ( $line_title ); $i ++) {				
			$objExcel->getActiveSheet ()->getColumnDimension ( $Excel_letter [$i] )->setWidth ( 20 ); //默认宽度是15
		}
	
		$objExcel->getActiveSheet ()->getHeaderFooter ()->setOddHeader ( '&L&BPersonal cash register&RPrinted on &D' );
		$objExcel->getActiveSheet ()->getHeaderFooter ()->setOddFooter ( '&L&B' . $objExcel->getProperties ()->getTitle () . '&RPage &P of &N' );
	
		// 设置页方向和规模
		$objExcel->getActiveSheet ()->getPageSetup ()->setOrientation ( \PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT );
		$objExcel->getActiveSheet ()->getPageSetup ()->setPaperSize ( \PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4 );
		$objExcel->setActiveSheetIndex ( 0 );
		$timestamp = "_" . date ( "YmdHis", time() );
		if ($ex == '2007') { //导出excel2007文档
			header ( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
			header ( 'Content-Disposition: attachment;filename="' . $doc ['title'] . $timestamp . '.xlsx"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter = \PHPExcel_IOFactory::createWriter ( $objExcel, 'Excel2007' );
			$objWriter->save ( 'php://output' );
			exit ();
		} else { //导出excel2003文档
			header ( 'Content-Type: application/vnd.ms-excel' );
			header ( 'Content-Disposition: attachment;filename="' . $doc ['title'] . $timestamp . '.xls"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter = \PHPExcel_IOFactory::createWriter ( $objExcel, 'Excel5' );
			$objWriter->save ( 'php://output' );
			exit ();
		}
	
	}
	
	
	//导出多个工作表数据
	//$tabList 是array(工作表名=>二维数组) 的三维数组
	//$doc 要都出的文件基本信息数组
	//$field是要导出的字段名称的数组 array(工作表名=>二维数组)
	//$line_title是导出的excel的第一行标题数组，不是数据 array(工作表名=>二维数组)
	//$ex 要导出的excel 文件的版本默认是2007,可以使用2003版本的
	//$jumpurl 是默认跳转的页面
	//$un_need 不需要导出的字段名
	//$field 字段数目应该要与$line_title的长度是一样的
	public function export_excel_multiple($tabList, $field, $line_title,$doc,$un_need = array(), $ex = '2007') {
	
		if ($tabList === false||count($tabList)<=0) {
			return false;
		}
	
		//最多导出60个字段，可以继续增加
		$Excel_letter = array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
				'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
				'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF',
				'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP',
				'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
				'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ',
				'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT',
				'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CB', 'CC', 'CD' );
		//spl_autoload_register ( array ('Think', 'autoload' ) ); //必须的，不然ThinkPHP和PHPExcel会冲突
		$objExcel = new \PHPExcel ();
	
		//设置导出文档的文件基本属性
		$objExcel->getProperties ()->setCreator ( $doc ['creator'] );
		$objExcel->getProperties ()->setLastModifiedBy ( $doc ['creator'] );
		$objExcel->getProperties ()->setTitle ( $doc ['title'] );
		$objExcel->getProperties ()->setSubject ( $doc ['subject'] );
		$objExcel->getProperties ()->setDescription ( $doc ['description'] );
		$objExcel->getProperties ()->setKeywords ( $doc ['keywords'] );
		$objExcel->getProperties ()->setCategory ( $doc ['category'] );
	
		$tabIndex=0;
		foreach($tabList as $title=>$list){
	
			if($tabIndex>0){
				$objExcel->createSheet();
			}
	
			$objExcel->setActiveSheetIndex ($tabIndex); //第一个工作表
				
			//设置表头--即Excel的第一行数据
			foreach ( $line_title[$title] as $key => $value ) {
				$objExcel->getActiveSheet ()->setCellValue ( $Excel_letter [$key] . "1", $value ); //$key 格式是:A1 $value是字段的中文名称
			}
	
	
			$start_line = 0; //从第几行开始写入数据，一般是从第二行开始
				
	
			/*----------写入内容-------------*/
			foreach ( $list as $key => $value ) {
				$line = $start_line + 2;
				for($k = 0; $k < count ( $field[$title] ); $k ++) {
					if (! in_array ( $field[$title] [$k], $un_need )) {
						//不输出指定的字符串
						$objExcel->getActiveSheet ()->getStyle ( $Excel_letter [$k] )->getNumberFormat ()->setFormatCode ( \PHPExcel_Style_NumberFormat::FORMAT_TEXT );
						$objExcel->getActiveSheet ()->setCellValueExplicit ( $Excel_letter [$k] . $line, $value [$field[$title] [$k]], \PHPExcel_Cell_DataType::TYPE_STRING );
					}
				}
				$start_line ++; //移动到下一行
			}
	
			// 高置列的宽度  $Excel_letter[$i] 代表的该列的名称 例如 A B C ...
			for($i = 0; $i < count ( $line_title[$title] ); $i ++) {
	
				$objExcel->getActiveSheet ()->getColumnDimension ( $Excel_letter [$i] )->setWidth ( 20 ); //默认宽度是15
			}
	
			$objExcel->getActiveSheet ()->getHeaderFooter ()->setOddHeader ( '&L&BPersonal cash register&RPrinted on &D' );
			$objExcel->getActiveSheet ()->getHeaderFooter ()->setOddFooter ( '&L&B' . $objExcel->getProperties ()->getTitle () . '&RPage &P of &N' );
	
			// 设置页方向和规模
			$objExcel->getActiveSheet ()->getPageSetup ()->setOrientation ( \PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT );
			$objExcel->getActiveSheet ()->getPageSetup ()->setPaperSize ( \PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4 );
			$objExcel->setActiveSheetIndex ( $tabIndex );
			$objExcel->getActiveSheet()->setTitle($title);
				
			$tabIndex++;
		}
	
		$timestamp = "_" . date ( "YmdHis", time() );
		if ($ex == '2007') { //导出excel2007文档
			header ( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
			header ( 'Content-Disposition: attachment;filename="' . $doc ['title'] . $timestamp . '.xlsx"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter = \PHPExcel_IOFactory::createWriter ( $objExcel, 'Excel2007' );
			$objWriter->save ( 'php://output' );
			exit ();
		} else { //导出excel2003文档
			header ( 'Content-Type: application/vnd.ms-excel' );
			header ( 'Content-Disposition: attachment;filename="' . $doc ['title'] . $timestamp . '.xls"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter = \PHPExcel_IOFactory::createWriter ( $objExcel, 'Excel5' );
			$objWriter->save ( 'php://output' );
			exit ();
		}
	
	}
	
	//导出带图片的Excel
	//$doc 要都出的文件基本信息数组
	//$field是要导出的字段名称的数组
	//$line_title是导出的excel的第一行标题数组，不是数据
	//$ex 要导出的excel 文件的版本默认是2007,可以使用2003版本的
	//$jumpurl 是默认跳转的页面
	//$un_need 不需要导出的字段名
	//$field 字段数目应该要与$line_title的长度是一样的
	public function export_excel_img($list, $field, $line_title,$doc,$un_need = array(), $ex = '2007') {
	
		if($list===false||count($list)<=0){
			return false;
		}
		
		$cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
		$cacheSettings = array( 'memoryCacheSize' => '11512MB');
		\PHPExcel_Settings::setCacheStorageMethod($cacheMethod,$cacheSettings);
		//最多导出60个字段，可以继续增加
		$Excel_letter = array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CB', 'CC', 'CD' );
		//spl_autoload_register ( array ('Think', 'autoload' ) ); //必须的，不然ThinkPHP和PHPExcel会冲突
		$objExcel = new \PHPExcel ();
	
		//设置导出文档的文件基本属性
		$objExcel->getProperties ()->setCreator ( $doc ['creator'] );
		$objExcel->getProperties ()->setLastModifiedBy ( $doc ['creator'] );
		$objExcel->getProperties ()->setTitle ( $doc ['title'] );
		$objExcel->getProperties ()->setSubject ( $doc ['subject'] );
		$objExcel->getProperties ()->setDescription ( $doc ['description'] );
		$objExcel->getProperties ()->setKeywords ( $doc ['keywords'] );
		$objExcel->getProperties ()->setCategory ( $doc ['category'] );
		$objExcel->setActiveSheetIndex ( 0 ); //第一个工作表
	
	
		//设置表头--即Excel的第一行数据
		foreach ( $line_title as $key => $value ) {
			$objExcel->getActiveSheet ()->setCellValue ( $Excel_letter [$key] . "1", $value ); //$key 格式是:A1 $value是字段的中文名称
		}
	
		if ($list !== false) {
			$start_line = 0; //从第几行开始写入数据，一般是从第二行开始
	
	
			/*----------写入内容-------------*/
			foreach ( $list as $key => $value ) {
				$line = $start_line + 2;
				for($k = 0; $k < count ( $field ); $k ++) {
					if (! in_array ( $field [$k], $un_need )) {
						if($field [$k] == 'img'&&$value [$field [$k]]!=''){
							//echo  $value [$field [$k]];exit();
							if(file_exists( '.'.$value [$field [$k]])){
								// 图片生成
								$objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
								$objDrawing[$k]->setPath( '.'.$value [$field [$k]]);
								// 设置宽度高度
								$objDrawing[$k]->setHeight(80);//照片高度
								$objDrawing[$k]->setWidth(80); //照片宽度
								/*设置图片要插入的单元格*/
								$objDrawing[$k]->setCoordinates($Excel_letter [$k] . $line);
								// 图片偏移距离
								$objDrawing[$k]->setOffsetX(12);
								$objDrawing[$k]->setOffsetY(12);
								$objExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(70);
								$objDrawing[$k]->setWorksheet($objExcel->getActiveSheet());
							}
						}else {
							//不输出指定的字符串
							//$objExcel->getActiveSheet ()->getStyle ( $Excel_letter [$k] )->getNumberFormat ()->setFormatCode ( \PHPExcel_Style_NumberFormat::FORMAT_TEXT );
							$objExcel->setActiveSheetIndex()->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
							$objExcel->getActiveSheet ()->setCellValueExplicit ( $Excel_letter [$k] . $line, $value [$field [$k]], \PHPExcel_Cell_DataType::TYPE_STRING );
						}
	
					}
				}
				$start_line ++; //移动到下一行
			}
	
		}
	
		// 高置列的宽度  $Excel_letter[$i] 代表的该列的名称 例如 A B C ...
		for($i = 0; $i < count ( $line_title ); $i ++) {
	
			$objExcel->getActiveSheet ()->getColumnDimension ( $Excel_letter [$i] )->setWidth ( 30 ); //默认宽度是15
				
		}
	
		$objExcel->getActiveSheet ()->getHeaderFooter ()->setOddHeader ( '&L&BPersonal cash register&RPrinted on &D' );
		$objExcel->getActiveSheet ()->getHeaderFooter ()->setOddFooter ( '&L&B' . $objExcel->getProperties ()->getTitle () . '&RPage &P of &N' );
	
		// 设置页方向和规模
		$objExcel->getActiveSheet ()->getPageSetup ()->setOrientation ( \PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT );
		$objExcel->getActiveSheet ()->getPageSetup ()->setPaperSize ( \PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4 );
		$objExcel->setActiveSheetIndex ( 0 );
		$timestamp = "_" . date ( "Y_m_d_H_i_s",time() );
		if ($ex == '2007') { //导出excel2007文档
			header ( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
			header ( 'Content-Disposition: attachment;filename="' . $doc ['title'] . $timestamp . '.xlsx"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter = \PHPExcel_IOFactory::createWriter ( $objExcel, 'Excel2007' );
			$objWriter->save ( 'php://output' );
			exit ();
		} else { //导出excel2003文档
			header ( 'Content-Type: application/vnd.ms-excel' );
			header ( 'Content-Disposition: attachment;filename="' . $doc ['title'] . $timestamp . '.xls"' );
			header ( 'Cache-Control: max-age=0' );
			$objWriter = \PHPExcel_IOFactory::createWriter ( $objExcel, 'Excel5' );
			$objWriter->save ( 'php://output' );
			exit ();
		}
	
	}
	
	/**导出CSV**************************************************************************************************************/
	//$list  是数据
	//$field 是英文字段名称-用来对应$list的数据
	//$title 是列的标题数组
	public function export_csv($list, $field,$title,$doc){
		set_time_limit(0);
		ini_set('memory_limit', '1024M');
	
		$fileName = date('YmdHis', time());
		ob_end_clean();
		header('Content-Encoding: UTF-8');
		header("Content-type:application/vnd.ms-excel;charset=UTF-8");
		header('Content-Disposition: attachment;filename="' . $doc['title'].$fileName . '.csv"');
	
		$fp = fopen('php://output', 'a');
		fwrite($fp,chr(0xEF).chr(0xBB).chr(0xBF));
		fputcsv($fp, $title);
	
		foreach($list as $key=>$value){
			$row=array();
			foreach ($field as $k=> $name){
				$row[$k] = $value[$name];
			}
			fputcsv($fp, $row);
		}
		
		ob_flush();  //刷新缓冲区
		flush();
	}
	//导出多个csv，并用zip打包
	//$title 是列的标题数组 array('表格名1'=>array('id'=>'ID','name'=>'姓名'..))
	//$tabList 是array('表格名1'=>$list数据1,'表格名2'=>$list数据2...)
	//$doc文档标识
	public function export_csv_zip($tabList,$title,$doc){
		set_time_limit(0);
		ini_set('memory_limit', '1024M');
		
		$dir=C ( 'save_path' )."csv/";
		if(!file_exists($dir)){
			mkdir($dir);
		}
		
		if ($tabList === false||count($tabList)<=0) {
			exit("数据为空");
		}
		
		$files=[];
		foreach($tabList as $fileName=>$list){
			
			$name=$fileName.".csv";
			$path =$dir.$name;
			$files[$name]=$path;
			ob_end_clean();
			header('Content-Encoding: UTF-8');
			header("Content-type:application/vnd.ms-excel;charset=UTF-8");
			header('Content-Disposition: attachment;filename="' . $doc['headTitle'].$name.'"');
			
			$fp = fopen($path, 'w');
			fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
			//写入标题行
			$sheetTitle=[];
			foreach ($title[$fileName] as $field => $item){
				$sheetTitle[$field] =  $item;
			}
			
			if(isset($doc['headTitle'])){
				//居中
				$head=[];
				$length=count($sheetTitle);
				$middle=ceil($length/2);
				for($i=0;$i<$length;$i++){
					if($i==$middle){
						$head[$i]=$doc['headTitle'];
					}else{
						$head[$i]='';
					}
				}
				fputcsv($fp,$head);
			}
			
			fputcsv($fp, $sheetTitle);
			
			//写入每一行值
			foreach($tabList[$fileName] as $value){
				$row=array();
				foreach($title[$fileName] as $field=>$name){
					$row[$field] =  $value[$field];
				}
				fputcsv($fp, $row);
			}
			
			fclose($fp);
			
		}
		
		$zip = new \ZipArchive();
		$docName=$doc['title'].time().'.zip';
		$zipName = $dir. $docName;
		$result=$zip->open($zipName, \ZipArchive::CREATE);
		foreach ($files as $name=>$file) {
			$zip->addFile($file, $name);
		}
		$zip->close();
	
		foreach ($files as $file) {
			@unlink($file);
		}
		
		header('Content-disposition: attachment; filename=' . $docName);
		header("Content-Type: application/zip");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . filesize($zipName));
		readfile($zipName);
		@unlink($zipName);
		
		ob_flush();  //刷新缓冲区
		flush();
	}
	/**导出CSV**************************************************************************************************************/
	
	/**
	 * HTML转EXCEL 输出Excel 头部声明
	 * $title 是输出的Excel文件名
	 * $html 是html代码
	 */
	public function html2Excel($title,$html){
		ob_end_clean();
		header("Cache-Control:public");
		header("Pragma:public");
		header( "Content-Type: application/vnd.ms-excel; charset=utf-8" );//表示输出的类型为excel文件类型
		header( "Content-type: application/octet-stream" );//表示二进制数据流，常用于文件下载
		header( "Content-Disposition: attachment; filename=".$title);//弹框下载文件
		echo $html;
		//以下三行代码使浏览器每次打开这个页面的时候不会使用缓存从而下载上一个文件
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header( "Pragma: no-cache" );
		header( "Expires: 0" );
		
		exit();
	}
	
}