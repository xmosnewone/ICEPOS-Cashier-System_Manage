<?php
namespace app\admin\controller;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ESheetTrans;
use model\PmSheetMaster;
use model\PosDaysum;
use model\PosBranch;
use model\Supcust;
use model\PosOperator;
use model\SysManager;
use model\PosSaleFlow;
/**
 * 报表
 */
class Report extends Super {
	
	/* 日结 */
	public function daylist() {
		return $this->fetch ( "report/daylist" );
	}
	
	// 日结搜索结果
	public function search() {
		$page = input ( "page" );
		$rows = input ( "limit" );
		$page = empty ( $page ) ? 1 : intval ( $page );
		$rows = empty ( $rows ) ? 1 : intval ( $rows );
		$start = input ( "start" );
		$branch_no = input ( "branch_no" );
		$PosDaysum = new PosDaysum ();
		$result = $PosDaysum->SearchDaySum ( $start, $branch_no, $page, $rows );
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
	}
	
	//日结
	public function dayDetail(){
		$PosDaysum = new PosDaysum ();
		$start = $PosDaysum->GetStart ();
		$this->assign ( "start_date", $start );
		$this->assign ( "end_date", date ( DATETIME_FORMAT,time() ) );
		return $this->fetch("controls/daysum");
	}
	
	// 执行日结
	public function daysum() {
		$start = input ( "start" );
		$end = input ( "end" );
		if (empty ( $start ) || empty ( $end )) {
			return [ 
					'code' => false,
					'msg' => lang ( "invalid_variable" ) 
			];
		} else {
			$begin_date = strtotime ( $start );
			$end_date = strtotime ( $end );
			if ($begin_date >= $end_date) {
				return [ 
						'code' => false,
						'msg' => lang ( "rep_time_wrong" ) 
				];
			} else {
				$confirm_man = session ( "loginname" );
				$PosDaysum = new PosDaysum ();
				$result = $PosDaysum->AddDaysum ( $start, $end, $confirm_man );
				switch ($result) {
					case 0 :
						return [ 
								'code' => false,
								'msg' => lang ( "rep_empty_data" ) 
						];
					case - 1 :
					case - 2 :
						return [ 
								'code' => false,
								'msg' => lang ( "rep_fail" ) 
						];
					case - 3 :
						return [ 
								'code' => false,
								'msg' => lang ( "rep_repeat_timezone" ) 
						];
					case 1 :
						return [ 
								'code' => true,
								'msg' => lang ( "rep_success" ) 
						];
				}
			}
		}
	}
	
	// 导出日结报表
	public function export() {
		
		$page = 1;
		$rows = 100000;
		$start = input ( "start" );
		$branch_no = input ( "branch_no" );
		$PosDaysum = new PosDaysum ();
		$result = $PosDaysum->SearchDaySum ( $start, $branch_no, $page, $rows );
		
		if($result['total']<=0){
			$this->showmessage(lang("empty_export"), U("Report/daylist"));
		}
		
		$doc['title']=$start.'日结报表';
		$field=['branch_name','pos_id','sale_name','begin_date'
				,'end_date','sale_amt','pay_amt','pay_rmb_amt'
				,'pay_crd_amt','pay_card_amt','sale_sum_amt'
				,'pay_sum_amt','pay_giv_amt','sale_ret_amt'
				,'pay_ret_amt','sale_giv_amt','sale_qty','pay_qty'
				,'ret_qty','giv_qty','oper_date','oper_name'];
		$title=['门店名称','POS机编码','收银员','开始时间','结束时间','应收金额'
				,'实收金额','实收人民币','实收银行卡','实收预付费卡','应收总额'
				,'实收总额','找零总额','应退金额','实退金额','赠送金额','应销售数量'
				,'实际售出数量','退货数量','赠送数量','日结日期','日结人'];
		
		$data=[];
		$rows=$result['rows'];
		foreach($rows as $k=>$row){
			foreach($row as $key=>$value){
				if($key=='pos_id'||$key=='begin_date'||$key=='end_date'||$key=='oper_date'){
					//转换成文本格式
					$row[$key]="\t".$value;
				}
			}
			
			$data[$k]=$row;
		}
		unset($rows);
		//用csv导出报表
		$this->export_csv($data,$field,$title,$doc);
		unset($data);
		exit();
	}
	
	/* 收货明细 */
	public function reveive() {
		return $this->fetch ( "report/receive" );
	}
	
	//收货明细报表
	public function recList() {

		$page = input ( "page" );
		$rows = input ( "limit" );
		$page = empty ( $page ) ? 1 : intval ( $page );
		$rows = empty ( $rows ) ? 1 : intval ( $rows );
		
		$start='';
		$end='';		
		$receive_date=input("receive_date");
		if(!empty($receive_date)){
			$date=explode("~", $receive_date);
			$start = trim($date[0]);
			$end = trim($date[1]);
		}
	
		$sheet_no = input("no");
		$branch_no =input("branch_no");
		
		$PmSheetMaster = new PmSheetMaster ();
		$result = $PmSheetMaster->GetSheetPager ( $rows, $page, $start, $end, $sheet_no, "", "", $supcust_no, "0", 0, ESheetTrans::PI,$branch_no );
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
		
	}
	
	// 导出收货明细报表
	public function recExport() {
	
		$start='';
		$end='';		
		$receive_date=input("receive_date");
		if(!empty($receive_date)){
			$date=explode("~", $receive_date);
			$start = trim($date[0]);
			$end = trim($date[1]);
		}
	
		$sheet_no = input("no");
		$branch_no =input("branch_no");
		
		$PmSheetMaster = new PmSheetMaster ();
		$result = $PmSheetMaster->GetSheetPager ( $rows, $page, $start, $end, $sheet_no, "", "", $supcust_no, "0", 0, ESheetTrans::PI,$branch_no,false);
		
	
		if($result['total']<=0){
			$this->showmessage(lang("empty_export"), U("Report/reveive"));
		}
	
		$doc['title']=$start.'收货明细报表';
		$field=['sheet_no','sheet_amt','branch_name','sp_name'
				,'order_status','oper_date','approve_flag'];
		$title=['单据编号','单据金额','门店仓库','供应商','单据状态','操作时间'
				,'审核状态'];
	
		$data=[];
		$rows=$result['rows'];
		foreach($rows as $k=>$row){
			foreach($row as $key=>$value){
				if($key=='approve_flag'){
					if($value==1){
						$row[$key]=lang("pi_md_is_approve");
					}else{
						$row[$key]=lang("pi_md_not_approve");
					}
				}
				if($key=='oper_date'){
						$row[$key]="\t".$value;
				}
			}
				
			$data[$k]=$row;
		}
		unset($rows);
		//用csv导出报表
		$this->export_csv($data,$field,$title,$doc);
		unset($data);
		exit();
	}
	
	//门店销售报表列表
	public function storeSales() {
		return $this->fetch("report/storesales");
	}
	
	//门店销售报表数据
	public function storeSalesData() {
		$start = input ( "start" );
		$end = input ( "end" );
		$branch_no = input ( "branch_no" );
		$item_no = input ( "item_no" );
		$item_clsno = input ( "item_clsno" );
		$supcust_no = input ( "supcust_no" );
		$item_subno = input ( "item_subno" );
		$item_brand = input ( "item_brand" );
		$summary_type = 1;
	
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		$PosSaleFlow = new PosSaleFlow ();
		$result = $PosSaleFlow->Summary ( $start, $end, $branch_no, $item_no, $item_clsno, $supcust_no, $item_subno, $item_brand, $summary_type, $page, $rows );
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
	}
	
	//单品销售报表列表
	public function goodsSales() {
		return $this->fetch("report/goodssales");
	}
		
	// 单品销售报表数据
	public function goodsSalesData() {
		$PosSaleFlow = new PosSaleFlow ();
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		
		$where="1=1";
		$start=input("start");
		$end=input("end");
		$item_no=input("item_no");
		
		$result=$PosSaleFlow->GetPager($page, $rows, $start, $end, $item_no,true);
		return listJson ( 0, lang ( "success_data" ), $result['total'], $result['rows']);
	}
	
	// 单品销售报表数据
	public function goodsSalesExport() {
		$PosSaleFlow = new PosSaleFlow ();

		$where="1=1";
		$start=input("start");
		$end=input("end");
		$item_no=input("item_no");
	
		$result=$PosSaleFlow->GetPager($page, $rows, $start, $end, $item_no,false);
		
		if($result['total']<=0){
			$this->showmessage(lang("empty_export"), U("Report/goodsSales"));
		}
		
		$doc['title']=$start.'单品销售报表';
		$field=['item_no','item_name','branch_no','unit_price','sale_price','discount_rate','sale_qnty','sale_money'];
		$title=['商品货号','商品名称','门店编码','原价','售价'
				,'折扣率','销售数量','销售金额'];
		
		$data=[];
		$rows=$result['rows'];
		foreach($rows as $k=>$row){
			foreach($row as $key=>$value){
				$row[$key]="\t".$value;
			}
		
			$data[$k]=$row;
		}
		unset($rows);
		//用csv导出报表
		$this->export_csv($data,$field,$title,$doc);
		unset($data);
		exit();
	}
	
}
