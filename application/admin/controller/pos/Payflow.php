<?php
namespace app\admin\controller\pos;
use app\admin\controller\Super;
use model\PosPayFlow;
use model\PaymentInfo;
use model\PosAccount;
/**
 * 收银流水
 *
 */
class Payflow extends Super {
	
	public function index() {
		$PaymentInfo=new PaymentInfo();
		$payment=$PaymentInfo->GetTradeModelsForPayflow("-1");
		$payment1=$PaymentInfo->GetTradeModelsForPayflow("1");
		
		$this->assign("payment", $payment);
		$this->assign("payment1", $payment1);
		return $this->fetch('pos/payflow');
	}
	
	//搜索并返回数据
    public function search() {
    	$page = input ( "page" );
    	$rows = input ( "limit" );
    	$page = empty ( $page ) ? 1 : intval ( $page );
    	$rows = empty ( $rows ) ? 1 : intval ( $rows );
    	
        $start = input("start");
        $end = input("end");
        $branch_no = input("branch_no");
        $posid = input("pos_id");
        $flowno = input("flow_no");
        $vipno = input("vip_no");
        $sale_way = input("saleway");
        $operid = input("oper_id");
        $posflag = input("posflag");
        if($posflag==1){
        	$payflag = input("payflag1");//非交易收入
        }else{
        	$payflag = input("payflag");//交易收入
        }
        
        $PosSaleFlow=new PosPayFlow();
        $res=$PosSaleFlow->SearchModels($start, $end, $branch_no, $posid, $flowno, $vipno, $sale_way, $payflag, $posflag, $page, $rows, $operid);
        return listJson(0,'',$res['total'],$res['rows']);
    }

	//收银员对账
    public function reconciliation() {
    	$PaymentInfo=new PaymentInfo();
        $this->assign("payment", $PaymentInfo->GetModelsForPos());
        return $this->fetch("pos/reconciliation");
    }

	//收银员对账 数据记录
    public function reconData() {
    	$page = input ( "page" );
    	$rows = input ( "limit" );
    	$page = empty ( $page ) ? 1 : intval ( $page );
    	$rows = empty ( $rows ) ? 1 : intval ( $rows );

        $start = input("start")?input("start"):date('Y-m-d');
        $end = input("end")?input("end"):date('Y-m-d');
        $branch_no = input("branch_no");
        $oper_id = input("oper_id");
        $pay_way = input("payflag");
        $PosSaleFlow=new PosPayFlow();
        $res=$PosSaleFlow->AccountSumarryForOperator($start, $end, $branch_no, $oper_id, $pay_way, $page, $rows, "1");
        
        //重新遍历成树状模型
        $list=[];
        $store=[];//记录门店一级
        $classList=[];//用门店编码分组的数据
        foreach($res as $k=>$v){
        	$no=trim($v['branch_no']);
        	
        	if($no=='null'||empty($no)){
        		$no='unknow';
        		$v['branch_no']='unknow';
        	}
        	//记录到门店
        	if(!isset($store[$no])){
        		$node=[];
        		$node['branch_no']=$v['branch_no'].$v['branch_name'];
        		$node['powerId']=$v['rowIndex'];
        		$node['openType']='null';
        		$node['parentId']=0;
        		$node['checkArr']='0';
        		$store[$no]=$node;
        	}
        	
        	//记录每一个节点
        	$node=[];
        	$node=$v;
        	$node['powerId']=$no.$v['rowIndex'];
        	$node['openType']='null';
        	$node['parentId']=$store[$no]['powerId'];
        	$node['checkArr']='0';
        	
        	$classList[$no][]=$node;

        }
        
        $pay_amount_t=0;
        $convert_amt_t=0;

        //合计每个门店的金额和折人民币
        foreach($classList as $no=>$v){
        	
        	$pay_amount=0;
        	$convert_amt=0;
        	$parentId='';
        	foreach($v as $cv){
        		$parentId=$cv['parentId'];
        		$pay_amount+=floatval($cv['pay_amount']);
        		$convert_amt+=floatval($cv['convert_amt']);
        		$pay_amount_t+=floatval($cv['pay_amount']);
        		$convert_amt_t+=floatval($cv['convert_amt']);
        	}
        	
        	//添加一行空行+合计数据
        	$temp['branch_no']='';
        	$temp['parentId']=$parentId;
        	$temp['pay_amount']=lang("tot").':'.$pay_amount;
        	$temp['convert_amt']=lang("tot").':'.$convert_amt;
        	
        	$classList[$no][]=$temp;
        }
  
        //按顺序组合排数组
        foreach($classList as $no=>$v){
        	
        	$list[]=$store[$no];
        	foreach($v as $cv){
        		$list[]=$cv;
        	}
        }
        
        //增加一行合计行
        $temp['branch_no']='';
        $temp['parentId']=0;
        $temp['pay_amount']=lang("tot").':'.$pay_amount_t;
        $temp['convert_amt']=lang("tot").':'.$convert_amt_t;
        $list[]=$temp;
        
        unset($classList);
        $result=array_values($list);
        
        return listJson(0,'',count($result),$result);
    }

	//前台收银员对账记录
    public function reconSaleData() {
        $page = input ( "page" );
    	$rows = input ( "limit" );
    	$page = empty ( $page ) ? 1 : intval ( $page );
    	$rows = empty ( $rows ) ? 1 : intval ( $rows );
        $start = input("start");
        $end = input("end");
        $branch_no = input("branch_no");
        $oper_id = input("oper_id");
        $PosAccount=new PosAccount();
        $res=$PosAccount->GetModelsForList($start, $end, $branch_no, $oper_id, $page, $rows, "1");
        return listJson(0,'',$res['total'],$res['rows']);
    }

	//导出记录
    public function export() {
        $start = input("start");
        $end = input("end");
        $branch_no = input("branch_no");
        $oper_id = input("oper_id");
        $pay_way = input("payflag");
        $acc = input("recflag");
        if ($acc == "1") {
			$PosPayFlow=new PosPayFlow();
            $result = $PosPayFlow->AccountSumarryForOperator($start, $end, $branch_no, $oper_id, $pay_way, 1, 10, "1");

            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue("A1", "收银员对账");
            $objPHPExcel->getActiveSheet()->mergeCells('A1:I2');
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()
                    ->setCellValue("A3", "对账时间：")
                    ->setCellValue("B3", $start . "~" . $end)
                    ->mergeCells("B3:C3")
                    ->setCellValue("D3", "对账门店:")
                    ->setCellValue("E3", $branch_no)
                    ->mergeCells('E3:F3');
            $objPHPExcel->getActiveSheet()
                    ->setCellValue("A4", "行号")
                    ->setCellValue("B4", "分店编号")
                    ->setCellValue("C4", "分店名称 ")
                    ->setCellValue("D4", "收银员编码")
                    ->setCellValue("E4", "收银员姓名 ")
                    ->setCellValue("F4", "销售方式")
                    ->setCellValue("G4", "收款方式")
                    ->setCellValue("H4", "金额")
                    ->setCellValue("I4", "折人民币");
            $i = 5;
            $sumPayAmt = 0;
            $sumConvertAmt = 0;
            $branchSumAmt = array();
            $operSumAmt = array();
            foreach ($result as $v) {
                if ($v["sale_name"] !== "赠送") {
                    if (!array_key_exists($v["branch_name"], $branchSumAmt)) {
                        $branchSumAmt[$v["branch_name"]] = $v;
                        $branchSumAmt[$v["branch_name"]]["oper_id"] = "";
                        $branchSumAmt[$v["branch_name"]]["oper_name"] = "";
                        $branchSumAmt[$v["branch_name"]]["pay_name"] = "";
                        $branchSumAmt[$v["branch_name"]]["sale_name"] = "";
                    } else {
                        $branchSumAmt[$v["branch_name"]]["pay_amount"]+=doubleval($v["pay_amount"]);
                        $branchSumAmt[$v["branch_name"]]["convert_amt"]+=doubleval($v["convert_amt"]);
                    }
                    $key = $v["branch_name"] . $v["oper_name"];
                    if (!array_key_exists($key, $operSumAmt)) {
                        $operSumAmt[$key] = $v;
                        $operSumAmt[$key]["pay_name"] = "";
                        $operSumAmt[$key]["sale_name"] = "";
                    } else {
                        $operSumAmt[$key]["pay_amount"]+=doubleval($v["pay_amount"]);
                        $operSumAmt[$key]["convert_amt"]+=doubleval($v["convert_amt"]);
                    }
                    $sumPayAmt+=$v["pay_amount"];
                    $sumConvertAmt+=$v["convert_amt"];
                }
            }
            foreach ($operSumAmt as $v) {
                array_push($result, $v);
            }
            foreach ($branchSumAmt as $v) {
                array_push($result, $v);
            }
            $dataReal = array();
            foreach ($result as $k => $v) {
                $oper_id = $v["oper_id"];
                if (empty($oper_id)) {
                    $oper_id = "0000";
                }
                $key = $v["branch_no"] . $oper_id . str_pad($k, 5, '0', STR_PAD_LEFT);
                $dataReal[$key] = $v;
            }
            ksort($dataReal);
            foreach ($dataReal as $v) {
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $i, ($i - 4))
                        ->setCellValue('B' . $i, ' ' . $v["branch_no"])
                        ->setCellValue('C' . $i, $v["branch_name"])
                        ->setCellValue('D' . $i, '  ' . $v["oper_id"])
                        ->setCellValue('E' . $i, $v["oper_name"])
                        ->setCellValue('F' . $i, $v["pay_name"])
                        ->setCellValue('G' . $i, $v["sale_name"])
                        ->setCellValue('H' . $i, $v["pay_amount"])
                        ->setCellValue('I' . $i, $v["convert_amt"]);
                $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':G' . $i)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':G' . $i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('H' . $i . ':I' . $i)->getNumberFormat()->setFormatCode('0.00');
                $i++;
            }

            $row_num = $objPHPExcel->getActiveSheet()->getHighestRow();
            $data1 = $this->GetMergeCells('B', 5, 'G', $row_num, $objPHPExcel->getActiveSheet());
            foreach ($data1 as $v) {
                $len = count($v);
                if ($len > 1) {
                    $objPHPExcel->getActiveSheet()->mergeCells($v[0] . ':' . $v[$len - 1]);
                }
            }
            $objPHPExcel->getActiveSheet()->getStyle('A3' . ':I' . $i)->getFont()->setName('宋体')
                    ->setSize(9);
            $objPHPExcel->getActiveSheet()
                    ->setCellValue("B" . $i, "总计:")
                    ->setCellValue("H" . $i, $sumPayAmt)
                    ->setCellValue("I" . $i, $sumConvertAmt)
                    ->getStyle('H' . $i . ':I' . $i)->getNumberFormat()->setFormatCode('0.00');

            $styleArray = array(
                'borders' => array(
                    'allborders' => array(

                        'style' => \PHPExcel_Style_Border::BORDER_THIN,

                    ),
                ),
            );
            $objPHPExcel->getActiveSheet()->getStyle('A1:I' . $i)->applyFromArray($styleArray);

            $filename = "收银员对账" . ".xls";
        } else {
			$PosAccount=new PosAccount();
            $result = $PosAccount->GetModelsForList($start, $end, $branch_no, $oper_id, 1, 10000, "2");
            $data = $result["rows"];
            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue("A1", "收银员前台对账");
            $objPHPExcel->getActiveSheet()->mergeCells('A1:J2');
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()
                    ->setCellValue("A3", "对账时间：")
                    ->setCellValue("B3", $start . "~" . $end)
                    ->mergeCells("B3:C3")
                    ->setCellValue("D3", "对账门店:")
                    ->setCellValue("E3", $branch_no)
                    ->mergeCells('E3:F3');
            $objPHPExcel->getActiveSheet()
                    ->setCellValue("A4", "行号")
                    ->setCellValue("B4", "分店编号")
                    ->setCellValue("C4", "分店名称")
                    ->setCellValue("D4", "POS机编码 ")
                    ->setCellValue("E4", "收银员编码")
                    ->setCellValue("F4", "收银员姓名 ")
                    ->setCellValue("G4", "对账日期 ")
                    ->setCellValue("H4", "首笔交易 ")
                    ->setCellValue("I4", "末笔交易")
                    ->setCellValue("J4", "交易金额");
            $i = 5;
            foreach ($data as $v) {
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $i, ($i - 4))
                        ->setCellValue('B' . $i, ' ' . $v["branch_no"])
                        ->setCellValue('C' . $i, $v["branch_name"])
                        ->setCellValue('D' . $i, '  ' . $v["pos_id"])
                        ->setCellValue('E' . $i, '  ' . $v["oper_id"])
                        ->setCellValue('F' . $i, $v["oper_name"])
                        ->setCellValue('G' . $i, $v["oper_date"])
                        ->setCellValue('H' . $i, $v["start_time"])
                        ->setCellValue('I' . $i, $v["end_time"])
                        ->setCellValue('J' . $i, $v["sale_amt"]);
                $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':H' . $i)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':H' . $i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('I' . $i . ':J' . $i)->getNumberFormat()->setFormatCode('0.00');
                $i++;
            }
            $row_num = $objPHPExcel->getActiveSheet()->getHighestRow();
            $objPHPExcel->getActiveSheet()->getStyle('A3' . ':J' . $i)->getFont()->setName('宋体')
                    ->setSize(9);

            $objPHPExcel->getActiveSheet()
                    ->setCellValue("B" . $i, "总计:")
                    ->setCellValue("J" . $i, "=SUM(J5:J" . ($i - 1) . ")")
                    ->getStyle('J' . $i)->getNumberFormat()->setFormatCode('0.00');


            $styleArray = array(
                'borders' => array(
                    'allborders' => array(

                        'style' => \PHPExcel_Style_Border::BORDER_THIN,

                    ),
                ),
            );
            $objPHPExcel->getActiveSheet()->getStyle('A1:J' . $i)->applyFromArray($styleArray);

            $filename = "收银员前台对账记录" . ".xls";
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }


    private function GetMergeCells($start_col, $start_row, $col_max, $row_num, $currentSheet) {
        $result = array();
        $k = 0;
        for ($j = $start_col; $j < $col_max; $j++) {
            for ($i = $start_row; $i <= $row_num; $i++) {
                $address = $j . $i;
                $val = $currentSheet->getCell($address)->getValue();
                $pre_val = "";
                if ($j != $start_col) {
                    $s = ord($j);
                    for ($m = $s; $m >= ord($start_col); $m--) {
                        $t = chr($m);
                        $preaddress = $t . $i;
                        $temp_val = $currentSheet->getCell($preaddress)->getValue();
                        $pre_val = $pre_val . $temp_val;
                    }
                } else {
                    $k = $start_col;
                    $preaddress = $k . $i;
                    $pre_val = $currentSheet->getCell($preaddress)->getValue();
                }
                $key = $pre_val . $val;
                if (!in_array($key, $result)) {
                    array_push($result, $key);
                    $result[$key] = array();
                }
                if (!in_array($address, $result[$key])) {
                    $len = count($result[$key]);
                    if ($len == 0) {
                        array_push($result[$key], $address);
                    } else {
                        $last = $result[$key][$len - 1];
                        $last_row = substr($last, 1);
                        if ($i - intval($last_row) == 1) {
                            array_push($result[$key], $address);
                        }
                    }
                }
            }
        }
        return $result;
    }

	//收银日报
    public function dayreport() {
        return $this->fetch("pos/dayreport");
    }

	//收银日报数据
    public function dayreportData() {
        $page = input ( "page" );
        $rows = input ( "limit" );
        $page = empty ( $page ) ? 1 : intval ( $page );
        $rows = empty ( $rows ) ? 1 : intval ( $rows );
        
        $start = input("start");
        $end = input("end");
        $branch_no = input("branch_no");
        $oper_id = input("oper_id");
        $PosPayFlow=new PosPayFlow();
        $result=$PosPayFlow->SearchModelsForDayReport($branch_no, $oper_id, $start, $end, $page, $rows);
        
        $res=$result['rows'];

        //重新遍历成树状模型
        $list=[];
        $store=[];//记录门店一级
        $classList=[];//用门店编码分组的数据
        foreach($res as $k=>$v){
        	$no=trim($v['branch_no']);
        	 
        	if($no=='null'||empty($no)){
        		$no='unknow';
        		$v['branch_no']='unknow';
        	}
        	//记录到门店
        	if(!isset($store[$no])){
        		$node=[];
        		$node['branch_no']=$v['branch_no'].$v['branch_name'];
        		$node['powerId']=$v['rowIndex'];
        		$node['openType']='null';
        		$node['parentId']=0;
        		$node['checkArr']='0';
        		$store[$no]=$node;
        	}
        	 
        	//记录每一个节点
        	$node=[];
        	$node=$v;
        	$node['powerId']=$no.$v['rowIndex'];
        	$node['openType']='null';
        	$node['parentId']=$store[$no]['powerId'];
        	$node['checkArr']='0';
        	 
        	$classList[$no][]=$node;
        
        }
        
        
        $pay_rmb_amt_t=$pay_crd_amt_t=$pay_card_amt_t=$pay_cha_amt_t=$pay_zfb_amt_t=$pay_wx_amt_t=$pay_sum_amt_t=0;
        //合计每个门店的金额和折人民币
        foreach($classList as $no=>$v){
        	 
        	$pay_rmb_amt=$pay_crd_amt=$pay_card_amt=$pay_cha_amt=$pay_zfb_amt=$pay_wx_amt=$pay_sum_amt=0;
        	
        	$parentId='';
        	foreach($v as $cv){
        		$parentId=$cv['parentId'];
        		$pay_rmb_amt+=floatval($cv['pay_rmb_amt']);
        		$pay_crd_amt+=floatval($cv['pay_crd_amt']);
        		$pay_card_amt+=floatval($cv['pay_card_amt']);
        		$pay_cha_amt+=floatval($cv['pay_cha_amt']);
        		$pay_zfb_amt+=floatval($cv['pay_zfb_amt']);
        		$pay_wx_amt+=floatval($cv['pay_wx_amt']);
        		$pay_sum_amt+=floatval($cv['pay_sum_amt']);
        		
        		$pay_rmb_amt_t+=floatval($cv['pay_rmb_amt']);
        		$pay_crd_amt_t+=floatval($cv['pay_crd_amt']);
        		$pay_card_amt_t+=floatval($cv['pay_card_amt']);
        		$pay_cha_amt_t+=floatval($cv['pay_cha_amt']);
        		$pay_zfb_amt_t+=floatval($cv['pay_zfb_amt']);
        		$pay_wx_amt_t+=floatval($cv['pay_wx_amt']);
        		$pay_sum_amt_t+=floatval($cv['pay_sum_amt']);
        	}
        	 
        	//添加一行空行+合计数据
        	$temp['branch_no']='';
        	$temp['parentId']=$parentId;
        	$temp['pay_rmb_amt']=lang("tot").':'.$pay_rmb_amt;
        	$temp['pay_crd_amt']=lang("tot").':'.$pay_crd_amt;
        	$temp['pay_card_amt']=lang("tot").':'.$pay_card_amt;
        	$temp['pay_cha_amt']=lang("tot").':'.$pay_cha_amt;
        	$temp['pay_zfb_amt']=lang("tot").':'.$pay_zfb_amt;
        	$temp['pay_wx_amt']=lang("tot").':'.$pay_wx_amt;
        	$temp['pay_sum_amt']=lang("tot").':'.$pay_sum_amt;
        	 
        	$classList[$no][]=$temp;
        }
        
        //按顺序组合排数组
        foreach($classList as $no=>$v){
        	 
        	$list[]=$store[$no];
        	foreach($v as $cv){
        		$list[]=$cv;
        	}
        }
        
        //增加一行合计行
        $temp['branch_no']='';
		$temp ['parentId'] = 0;
		$temp ['pay_rmb_amt'] = lang ( "tot" ) . ':' . $pay_rmb_amt_t;
		$temp ['pay_crd_amt'] = lang ( "tot" ) . ':' . $pay_crd_amt_t;
		$temp ['pay_card_amt'] = lang ( "tot" ) . ':' . $pay_card_amt_t;
		$temp ['pay_cha_amt'] = lang ( "tot" ) . ':' . $pay_cha_amt_t;
		$temp ['pay_zfb_amt'] = lang ( "tot" ) . ':' . $pay_zfb_amt_t;
		$temp ['pay_wx_amt'] = lang ( "tot" ) . ':' . $pay_wx_amt_t;
		$temp ['pay_sum_amt'] = lang ( "tot" ) . ':' . $pay_sum_amt_t;
		$list [] = $temp;
        
        unset($classList);
        $result=array_values($list);
        
        return listJson(0,'',count($result),$result);
    }

	//销售日报导出
    public function exportday() {

        $page = input ( "page" );
        $rows = input ( "limit" );
        $page = empty ( $page ) ? 1 : intval ( $page );
        $rows = empty ( $rows ) ? 1 : intval ( $rows );
        
        $start = input("start");
        $end = input("end");
        $branch_no = input("branch_no");
        $oper_id = input("oper_id");
        $PosPayFlow=new PosPayFlow();
        $data = $PosPayFlow->SearchModelsForDayReport($branch_no, $oper_id, $start, $end, $page, $rows);
        $result = $data["rows"];
        if (!empty($result)) {
            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue("A1", "收银日报");
            $objPHPExcel->getActiveSheet()->mergeCells('A1:K2');
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()
                    ->setCellValue("A3", "时间：")
                    ->setCellValue("B3", $start . "~" . $end)
                    ->mergeCells("B3:C3")
                    ->setCellValue("D3", "门店:")
                    ->setCellValue("E3", $branch_no)
                    ->mergeCells('E3:F3');
            $objPHPExcel->getActiveSheet()
                    ->setCellValue("A4", "行号")
                    ->setCellValue("B4", "分店编号")
                    ->setCellValue("C4", "分店名称 ")
                    ->setCellValue("D4", "POS编码")
                    ->setCellValue("E4", "POS编码")
                    ->setCellValue("F4", "收银员姓名")
                    ->setCellValue("G4", "人民币现金")
                    ->setCellValue("H4", "信用卡")
                    ->setCellValue("I4", "预付费卡")
                    ->setCellValue("J4", "合计")
                    ->setCellValue("K4", "合计[折合人民币]");
            $i = 5;
            $payRmbAmt = 0;
            $payCrdAmt = 0;
            $payCardAmt = 0;
            $paySumAmt = 0;
            $branchSumAmt = array();

            foreach ($result as $v) {
                if (!array_key_exists($v["branch_name"], $branchSumAmt)) {
                    $branchSumAmt[$v["branch_name"]] = array();
                    $branchSumAmt[$v["branch_name"]]["pos_id"] = "";
                    $branchSumAmt[$v["branch_name"]]["oper_id"] = "";
                    $branchSumAmt[$v["branch_name"]]["oper_name"] = "";
                    $branchSumAmt[$v["branch_name"]]["branch_no"] = $v["branch_no"];
                    $branchSumAmt[$v["branch_name"]]["branch_name"] = $v["branch_name"];
                    $branchSumAmt[$v["branch_name"]]["pay_rmb_amt"] = $v["pay_rmb_amt"];
                    $branchSumAmt[$v["branch_name"]]["pay_crd_amt"] = $v["pay_crd_amt"];
                    $branchSumAmt[$v["branch_name"]]["pay_card_amt"] = $v["pay_card_amt"];
                    $branchSumAmt[$v["branch_name"]]["pay_sum_amt"] = $v["pay_sum_amt"];
                } else {
                    $branchSumAmt[$v["branch_name"]]["pay_rmb_amt"]+=doubleval($v["pay_rmb_amt"]);
                    $branchSumAmt[$v["branch_name"]]["pay_crd_amt"]+=doubleval($v["pay_crd_amt"]);
                    $branchSumAmt[$v["branch_name"]]["pay_card_amt"]+=doubleval($v["pay_card_amt"]);
                    $branchSumAmt[$v["branch_name"]]["pay_sum_amt"]+=doubleval($v["pay_sum_amt"]);
                }
                $payRmbAmt+=$v["pay_rmb_amt"];
                $payCrdAmt+=$v["pay_crd_amt"];
                $payCardAmt+=$v["pay_card_amt"];
                $paySumAmt+=$v["pay_sum_amt"];
            }
            foreach ($branchSumAmt as $v) {
                array_push($result, $v);
            }
            $dataReal = array();
            foreach ($result as $k => $v) {
                $pos_id = $v["pos_id"];
                $oper_id = $v["oper_id"];
                if (empty($pos_id)) {
                    $pos_id = "0000";
                }
                if (empty($oper_id)) {
                    $oper_id = "0000";
                }
                $key = $v["branch_no"] . $pos_id . $oper_id . str_pad($k, 5, '0', STR_PAD_LEFT);
                $dataReal[$key] = $v;
            }
            ksort($dataReal);
            foreach ($dataReal as $v) {
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $i, ($i - 4))
                        ->setCellValue('B' . $i, ' ' . $v["branch_no"])
                        ->setCellValue('C' . $i, $v["branch_name"])
                        ->setCellValue('D' . $i, '  ' . $v["pos_id"])
                        ->setCellValue('E' . $i, '  ' . $v["oper_id"])
                        ->setCellValue('F' . $i, $v["oper_name"])
                        ->setCellValue('G' . $i, $v["pay_rmb_amt"])
                        ->setCellValue('H' . $i, $v["pay_crd_amt"])
                        ->setCellValue('I' . $i, $v["pay_card_amt"])
                        ->setCellValue('J' . $i, $v["pay_sum_amt"])
                        ->setCellValue('K' . $i, $v["pay_sum_amt"]);
                $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':F' . $i)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':F' . $i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('G' . $i . ':K' . $i)->getNumberFormat()->setFormatCode('0.00');
                $i++;
            }
            $row_num = $objPHPExcel->getActiveSheet()->getHighestRow();
            $data = $this->GetMergeCells('B', 5, 'D', $row_num, $objPHPExcel->getActiveSheet());
            foreach ($data as $v) {
                $len = count($v);
                if ($len > 1) {
                    $objPHPExcel->getActiveSheet()->mergeCells($v[0] . ':' . $v[$len - 1]);
                }
            }
            $objPHPExcel->getActiveSheet()->getStyle('A3' . ':K' . $i)->getFont()->setName('宋体')
                    ->setSize(9);

            $objPHPExcel->getActiveSheet()
                    ->setCellValue("B" . $i, "总计:")
                    ->setCellValue("G" . $i, $payRmbAmt)
                    ->setCellValue("H" . $i, $payCrdAmt)
                    ->setCellValue("I" . $i, $payCardAmt)
                    ->setCellValue("J" . $i, $paySumAmt)
                    ->setCellValue("K" . $i, $paySumAmt)
                    ->getStyle('G' . $i . ':K' . $i)->getNumberFormat()
            		->setFormatCode('0.00');


            $styleArray = array(
                'borders' => array(
                    'allborders' => array(

                        'style' => \PHPExcel_Style_Border::BORDER_THIN,

                    ),
                ),
            );
            $objPHPExcel->getActiveSheet()->getStyle('A1:K' . $i)->applyFromArray($styleArray);

            $filename = "收银日报" . ".xls";
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output');
        }else{
            $this->showmessage("没有可导出数据",$_SERVER['HTTP_REFERER']);
        }
    }

}
