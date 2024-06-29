<?php
namespace app\admin\controller\pos;
use app\admin\controller\Super;
use app\admin\controller\common\Index as CommonIndex;
use model\PosSaleFlow;

/**
 * 销售流水
 */
class Saleflow extends Super {
	
	// 销售流水列表
	public function index() {
		return $this->fetch ( 'pos/saleflow' );
	}
	
	//数据执行搜索
	public function search() {
		$page = input ( "page" );
		$rows = input ( "limit" );
		$page = empty ( $page ) ? 1 : intval ( $page );
		$rows = empty ( $rows ) ? 1 : intval ( $rows );
		
		$start = input ( "start" );
		$end = input ( "end" );
		$supcust_no = input ( "supcust_no" );
		$branch_no = input ( "branch_no" );
		$pos_id = input ( "pos_id" );
		$oper_id = input ( "oper_id" );
		$item_no = input ( "item_no" );
		$item_name = input ( "item_name" );
		$item_clsno = input ( "item_clsno" );
		$item_brand = input ( "item_brand" );
		$flow_no = input ( "flow_no" );
		$vip_no = input ( "vip_no" );
		$sale_way = input ( "sale_way" );
		$PosSaleFlow=new PosSaleFlow();
		$res=$PosSaleFlow->SearchModelsForList ( $start, $end, $supcust_no, $branch_no, $pos_id, $oper_id, $item_no, $item_name, $item_clsno, $item_brand, $flow_no, $vip_no, $sale_way, $page, $rows ) ;
		return listJson(0,'',$res['total'],$res['rows']);
	}
	
	//查看销售流水详细
	public function saleDetail() {
		$index=new CommonIndex();
		$data=$index->getvoucher();
		$this->assign("data",$data);
		return $this->fetch("pos/saledetail");
	}
	
	//销售汇总
	public function summaryList() {
		return $this->fetch("pos/summarylist");
	}
	
	//销售汇总数据
	public function summary() {

		$page = input ( "page" );
		$rows = input ( "limit" );
		$page = empty ( $page ) ? 1 : intval ( $page );
		$rows = empty ( $rows ) ? 1 : intval ( $rows );
		$start = input ( "start" );
		$end = input ( "end" );
		$branch_no = input ( "branch_no" );
		$item_no = input ( "item_no" );
		$item_clsno = input ( "item_clsno" );
		$supcust_no = input ( "supcust_no" );
		$item_subno = input ( "item_subno" );
		$item_brand = input ( "item_brand" );
		$summary_type = input ( "summary_type" );

		$PosSaleFlow=new PosSaleFlow();
		$res=$PosSaleFlow->Summary ( $start, $end, $branch_no, $item_no, $item_clsno, $supcust_no, $item_subno, $item_brand, $summary_type, $page, $rows);
		return listJson(0,'',$res['total'],$res['rows']);
		
	}
}
