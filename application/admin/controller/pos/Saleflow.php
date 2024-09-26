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

    //销售流水导出
    public function sfexport() {
        $page = 1;
        $rows = 100000;//默认导出十万条，再多数据自己改

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

        $dataList=$res['rows'];//所有结果行数据
        $field=array();
        $title=array();
        $line_title = array (
            'rowIndex' => '序号','branch_no' => '门店编号','branch_name' => '门店名称','flow_no' => 'POS机编号','flow_no' => '单据编号','oper_date' => '操作日期'
        ,'item_name' => '商品名称','item_no'=>'商品货号','unit_no'=>'单位','item_size'=>'规格','sell_way'=>'销售方式','sale_qnty'=>'数量','sale_price'=>'售价'
        ,'sale_money'=>'售价金额','unit_price'=>'原价','unit_money'=>'原价金额','in_price'=>'进价','in_money'=>'进价金额','item_subno'=>'自编码'
        ,'item_clsno'=>'分类编码','item_clsname'=>'分类名称','item_brand'=>'品牌','item_brandname'=>'品牌名称','pos_id'=>'POS机编码','oper_name'=>'收银员'
        ,'nickname'=>'会员名称','vip_no'=>'会员编号','mobile'=>'会员电话','memo'=>'备注','discount_rate'=>'折扣值'
        );
        $doc = array ('creator' => 'icepos', 'title' => '销售流水', 'subject' => '销售流水', 'description' => '销售流水', 'keywords' => '销售流水', 'category' => '销售流水' );
        //存储英文字段和中文字段
        foreach($line_title as $key=>$value){
            $field[]=$key;
            $title[]=$value;
        }
        $this->export_csv($dataList,$field,$title,$doc);
        exit();
    }
}
