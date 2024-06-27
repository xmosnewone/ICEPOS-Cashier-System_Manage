<?php
namespace app\admin\controller\pmsheet;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ESheetTrans;
use model\PmSheetMaster;
use model\PmSheetDetail;
use model\SysSheetNo;
use model\SysManager;
use model\PosSaleFlow;
use model\PosBranch;
/**
 * 采购订单
 */
class Po extends Super{

	//列表首页模板
    public function index() {
        return $this->fetch("pmsheet/polist");
    }
    
    //列表数据
    public function getlist() {
        
        $page =input('page') ? intval(input('page')) : 1;
        $rows =input('limit') ? intval(input('limit')) : 10;
        
        $start = input("start");
        $end = input("end");
        $sheet_no = input("no");
        $approve_flag = input("approve_flag");
        $oper_id = input("oper_id");
        $supcust_no = input("supcust_no");
        $order_status = input("order_status");
        $PmSheetMaster=new PmSheetMaster();
        $result=$PmSheetMaster->GetSheetPager($rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $supcust_no, $order_status, "0", ESheetTrans::PO);
        return listJson(0,lang("success_data"),$result['total'], $result['rows']);
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
    				'msg' => lang("po_delete_success")
    		];
    	}
    }
    
	//删除
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
        if (!IS_AJAX) {
            return ['code'=>false,'msg'=>lang("illegal_operate")];
        }

        $no = input("no");
        
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

	//明细
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
        return $this->fetch("pmsheet/podetail");
    }

	//获取采购单明细
    public function getdetail() {
        $no = input("no");
        if (empty($no)) {
        	return ['code'=>false,'msg'=>lang("invalid_variable")];
        }
        $PmSheetDetail=new PmSheetDetail();
        $result = $PmSheetDetail->GetDetail($no);
        return listJson(0,lang("success_data"),$result['total'], $result['rows']);
    }

	//保存采购单明细
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
		$no = $sheet ["sheetno"];
		$flag = 0;
		$master = new PmSheetMaster ();
		$master->supcust_no=$sheet['supplier'];//供应商
		$master->branch_no=$sheet['branch_no'];//仓库
		$master->oper_id=$sheet['oper_id'];//采购员
		$master->valid_date=$sheet['valid_date'];//交货期限
		$master->order_man = $sheet['order_man'];//制单人员
		$master->oper_date = $sheet['oper_date'];//制单日期
		$master->memo = $sheet['memo'];//备注
		
		if (empty ( $no )) {
			$master->db_no = ESheetTrans::PLUS;
			$master->trans_no = ESheetTrans::PO;
			$SysSheetNo = new SysSheetNo ();
			$master->sheet_no = $SysSheetNo->CreateSheetNo ( $master->trans_no, $sheet ["branch_no"] );
		} else {
			$master = $master->GetSheet ( $no );
			if (empty ( $master )) {
				return [ 
						'code' => false,
						'msg' => lang ( "empty_record" ) 
				];
			}
			$master->sheet_no=$no;
			$master->db_no = ESheetTrans::PLUS;
			$master->trans_no = ESheetTrans::PO;
			$flag = 1;
		}
		//$master->address = $sheet ['address'];
		//$master->linkman = $sheet ['linkman'];
		//$master->telephone = $sheet ['telephone'];
		$amount = 0;
		$details = array ();
		foreach ( $items as $k => $v ) {
			if (! empty ( $v ["item_no"] )) {
				$detail = new PmSheetDetail ();
				$detail->item_no = $v ["item_no"];
				$detail->large_qty = $v ["large_qty"];
				$detail->order_qty = $v ["order_qty"];
				$detail->valid_price = $v ["item_price"];
				$detail->tax = $v ["purchase_tax"];
				$detail->sub_amt = $v ["sub_amt"];
				$detail->other1 = $v ["memo"];
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
		
		$master->sheet_amt = $amount;
		$addflag = $flag == 1 ? "edit" : "add";
		$PmSheetMaster = new PmSheetMaster ();
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

    
    //批量终止采购单
    public function batchStop() {
    	 
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
    		$res=$this->stopOrder($no);
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
    				'msg' => lang("po_stop_success")
    		];
    	}
    }
    
	//中止采购单
    public function stopOrder($no) {
        
		$PmSheetMaster = new PmSheetMaster ();
		$res = $PmSheetMaster->Zhongzhi ( $no );
		switch ($res) {
			case - 2 :
				return [
						'code' => false,
						'msg' => lang ( "empty_record" )
				];
			case - 1 :
				return [
						'code' => false,
						'msg' => lang ( "po_stop_fail" )
				];
			case 0 :
				return [
						'code' => false,
						'msg' => lang ( "po_stop_isapprove" )
				];
			default :
				return [
						'code' => true,
						'msg' => lang ( "po_stop_success" )
				];
		}
       
    }

	//导出采购单
    public function export() {
        $no = input("no");
        if (empty($no)) {
            echo "参数错误";
            exit();
        }
		$objPHPExcel = new \PHPExcel ();
		$PmSheetMaster=new PmSheetMaster();
		$PmSheetDetail=new PmSheetDetail();
		$model = $PmSheetMaster->GetSheet ( $no );
		$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( "A1", "采购订单" );
		$objPHPExcel->getActiveSheet ()->mergeCells ( 'A1:M2' );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_CENTER );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFill ()->setFillType ( \PHPExcel_Style_Fill::FILL_SOLID );
		$objPHPExcel->getActiveSheet ()->getStyle ( 'A1' )->getFont ()->setBold ( true );
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A3", "单据编号：" )->setCellValue ( "B3", $no )->mergeCells ( 'B3:C3' )->setCellValue ( "D3", "供 应 商：" )->setCellValue ( "E3", $model->sp_name )->mergeCells ( 'E3:M3' )->

		setCellValue ( "A4", "采 购 员：" )->setCellValue ( "B4", $model->oper_name )->mergeCells ( 'B4:C4' )->setCellValue ( "D4", "仓    库：" )->setCellValue ( "E4", $model->branch_name )->mergeCells ( 'E4:H4' )->setCellValue ( "A5", "制单人员：" )->setCellValue ( "B5", $model->username )->mergeCells ( 'B5:C5' )->setCellValue ( "D5", "制单日期:" )->setCellValue ( "E5", $model->oper_date )->mergeCells ( 'E5:H5' )->setCellValue ( "I5", "交货期限：" )->setCellValue ( "J5", $model->valid_date )->mergeCells ( 'J5:M5' )->setCellValue ( "A6", "审核人员：" )->setCellValue ( "B6", $model->confirm_name )->mergeCells ( 'B6:C6' )->setCellValue ( "D6", "审核日期：" )->setCellValue ( "E6", $model->work_date )->mergeCells ( 'E6:H6' )->setCellValue ( "I6", "备    注：" )->setCellValue ( "J6", $model->memo )->mergeCells ( 'J6:M6' );
		$models = $PmSheetDetail->GetDetail ( $no );
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A7", "行号" )->setCellValue ( "B7", "货号 " )->setCellValue ( "C7", "品名" )->mergeCells ( 'C7:D7' )->setCellValue ( "E7", "规格" )->mergeCells ( 'E7:F7' )->setCellValue ( "G7", "箱数" )->setCellValue ( "H7", "进货规格" )->setCellValue ( "I7", "数量" )->setCellValue ( "J7", "进价" )->mergeCells ( 'J7:K7' )->setCellValue ( "L7", "金额" )->mergeCells ( 'L7:M7' );
		$i = 8;
		foreach ( $models ["rows"] as $v ) {
			$objPHPExcel->setActiveSheetIndex ( 0 )->setCellValue ( 'A' . $i, ($i - 7) )->setCellValue ( 'B' . $i, ' ' . $v ["item_no"] )->setCellValue ( 'C' . $i, $v ["item_name"] )->mergeCells ( 'C' . $i . ":D" . $i )->setCellValue ( 'E' . $i, $v ["item_size"] )->mergeCells ( 'E' . $i . ":F" . $i )->setCellValue ( 'G' . $i, $v ["large_qty"] )->setCellValue ( 'H' . $i, $v ["purchase_spec"] )->setCellValue ( 'I' . $i, $v ["order_qty"] )->setCellValue ( 'J' . $i, $v ["item_price"] )->mergeCells ( 'J' . $i . ":K" . $i )->setCellValue ( 'L' . $i, $v ["sub_amt"] )->mergeCells ( 'L' . $i . ":M" . $i );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A' . $i . ':E' . $i )->getAlignment ()->setHorizontal ( \PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'A' . $i . ':M' . $i )->getAlignment ()->setVertical ( \PHPExcel_Style_Alignment::VERTICAL_CENTER );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'C' . $i )->getAlignment ()->setWrapText ( true );
			$objPHPExcel->getActiveSheet ()->getStyle ( 'F' . $i . ':M' . $i )->getNumberFormat ()->setFormatCode ( '0.00' );
			$i ++;
		}
		$last = $i - 1;
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A" . ($i), "总    计：" )->setCellValue ( "G" . ($i), "=SUM(G8:G$last)" )->setCellValue ( "I" . ($i), "=SUM(I8:I$last)" )->setCellValue ( "J" . ($i), "=SUM(J8:J$last)" )->mergeCells ( 'J' . $i . ":K" . $i )->setCellValue ( "L" . ($i), "=SUM(L8:L$last)" )->mergeCells ( 'L' . $i . ":M" . $i );
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
		$objPHPExcel->getActiveSheet ()->getColumnDimension ( 'M' )->setWidth ( 8 );
		
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A" . ($i + 2), "经手：" )->setCellValue ( "C" . ($i + 2), "审核：" )->setCellValue ( "E" . ($i + 2), "签收：" )->setCellValue ( "G" . ($i + 2), "财务：" )->setCellValue ( "I" . ($i + 2), "仓库：" );
		
		$objPHPExcel->getActiveSheet ()->setCellValue ( "A" . ($i + 3), "公司名称：" )->setCellValue ( "B" . ($i + 3), "总部" )->setCellValue ( "E" . ($i + 3), "公司地址：" )->setCellValue ( "F" . ($i + 3), "您的详细地址" )->mergeCells ( "F" . ($i + 3) . ":M" . ($i + 3) );
		
		$loginname = session( "loginname" );
		$SysManager=new SysManager();
		$user = $SysManager->GetByName ( $loginname );
		
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
		$filename = "采购订单" . $no . ".xls";
		header ( 'Content-Type: application/vnd.ms-excel' );
		header ( 'Content-Disposition: attachment;filename="' . $filename . '"' );
		header ( 'Cache-Control: max-age=0' );
		$objWriter->save ( 'php://output' );
        
    }

}
