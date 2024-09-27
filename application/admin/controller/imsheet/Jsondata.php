<?php

namespace app\admin\controller\imsheet;

use app\admin\controller\Super;
use app\admin\components\BuildTreeArray;
use model\PosBranch;
use model\ImSheetMaster;
use model\ImSheetDetail;
use model\PmSheetDetail;
use model\Item_info;
use think\Db;

class Jsondata extends Super {
	
	//库存调整单
	public function sheetIndex() {
		$list = M ( "im_sheet_master" )->field ( "sheet_no,(case approve_flag when 1 then '已审核' when 0 then '未审核' end) approve_flag,branch_no,d_branch_no,sheet_amt,order_man,oper_date,confirm_man" )->where ( "confirm_man='' and approve_flag=0" )->select ();
		return $list;
	}
	
	//门店信息
	public function branch() {
		$list = M ( "pos_branch_info" )->field ( "branch_no,branch_name,branch_man,branch_tel" )->select ();
		return $list;
	}
	
	// 获取商品分类
	public function itemClass() {
		$datas = input ( 'data' );
		$data = M ( "bd_item_cls" )->field ( "item_clsno as id, cls_parent as fid,item_clsname as text" )->select ();
		
		$aa = array ();
		foreach ( $data as $k => $v ) {
			$aa [$k] = $v;
			$aa [$k] ['title'] = $data [$k] ['text'] . "(" . $aa [$k] ['id'] . ")";
		}
		$data = $aa;

		$bta = new BuildTreeArray ( $data, 'id', 'fid', 0 );
		$tree = $bta->getTreeArray ();

		$code = 200;
		$message = '';
		return treeJson ( $code, $message, $tree, lang ( "alltypes" ) );
	}
	
	// 商品品牌
	public function itemBrand() {
		$list = M ( "bd_base_code" )->field ( "code_id as id, type_no as fid, code_name as text" )->where ( "type_no='PP'" )->select ();
		return $list;
	}
	
	public function getPmSheetDetail() {
		$sheetNo = input ( "sheetno" );
		
		$where="(d.real_qty-d.order_qty) > 0 and d.sheet_no='$sheetNo'";
		$list=Db::name("pm_sheet_detail")
				->alias("d")
				->join('bd_item_info i','i.item_no=d.item_no',"LEFT")
				->field("d.item_no,d.real_qty,round(((d.real_qty-d.order_qty) / i.purchase_spec),2) as large_qty,
						round((d.real_qty-d.order_qty),2) as order_qty,d.orgi_price as item_price,i.sale_price,
						round((i.sale_price * (d.real_qty-d.order_qty)),2) as sale_price_amt,
						round(((d.real_qty-d.order_qty) * d.orgi_price),2) as sub_amt,d.other1,i.purchase_spec,
						i.item_name,i.item_subno,i.unit_no as item_unit,i.item_size")
				->where($where)
				->select();
		
		$result = array ();
		$rowIndex = 0;
		
		foreach ( $list as $v ) {
			$result [$rowIndex] ["rowIndex"] = $rowIndex + 1;
			$result [$rowIndex] ["item_no"] = $v ["item_no"];
			$result [$rowIndex] ["real_qty"] = $v ["real_qty"];
			$result [$rowIndex] ["large_qty"] = $v ["large_qty"];
			$result [$rowIndex] ["order_qty"] = $v ["order_qty"];
			$result [$rowIndex] ["item_price"] = $v ["item_price"];
			$result [$rowIndex] ["sale_price"] = $v ["sale_price"];
			$result [$rowIndex] ["sale_amt"] = formatMoneyDisplay ( $v ["sale_price"] * $v ["real_qty"] );
			$result [$rowIndex] ["sub_amt"] = formatMoneyDisplay ( $v ["sub_amt"] );
			$result [$rowIndex] ["other1"] = $v ["other1"];
			$result [$rowIndex] ["purchase_spec"] = $v ["purchase_spec"];
			$result [$rowIndex] ["item_name"] = $v ["item_name"];
			$result [$rowIndex] ["item_subno"] = $v ["item_subno"];
			$result [$rowIndex] ["item_unit"] = $v ["item_unit"];
			$result [$rowIndex] ["item_size"] = $v ["item_size"];
			$rowIndex ++;
		}

		return listJson ( 0, lang ( "success_data" ), $rowIndex,$result);
	}
	
	//返回库存调整单详细
	public function getImSheetDetail() {
		$no = input ( "sheetno" );
		
		$ImSheetDetail = new ImSheetDetail ();
		$model = $ImSheetDetail->GetSheetDetails ( $no );
		$result = array ();
		
		$i = 0;
		foreach ( $model as $v ) {
			$result [$i] ["rowIndex"] = $i + 1;
			$result [$i] ["item_no"] = $v ["item_no"];
			$result [$i] ["item_name"] = $v ["item_name"];
			$result [$i] ["item_price"] = sprintf ( "%.2f", $v ["item_price"] );
			$result [$i] ["item_subno"] = $v ["item_subno"];
			$result [$i] ["large_qty"] = $v ["large_qty"];
			$result [$i] ["sale_price"] = sprintf ( "%.2f", $v ["sale_price"] );
			$result [$i] ["item_size"] = $v ["item_size"];
			$result [$i] ["real_qty"] = $v ["real_qty"];
			$result [$i] ["item_stock"] = $v ["stock_qty"];
			$result [$i] ["order_qty"] = $v ["real_qty"];
			$result [$i] ["unit_no"] = $v ["item_unit"];
			$result [$i] ["purchase_spec"] = $v ["purchase_spec"];
			$result [$i] ["purchase_tax"] = $v ["purchase_tax"];
			$result [$i] ["sub_amt"] = sprintf ( "%.2f", $v ["item_price"] ) * $v ["real_qty"];
			$result [$i] ["sale_amt"] = sprintf ( "%.2f", $v ["sale_price"] ) * $v ["real_qty"];
			$result [$i] ["memo"] = $v ["memo"];
			$result [$i] ["other1"] = $v ["memo"];
			
			$i ++;
		}

		return listJson ( 0, lang ( "success_data" ), $i,$result);
	}
	
	public function getImSheetDetail1() {
		$no = input ( "sheetno" );
		$ImSheetDetail=new ImSheetDetail();
		
		$mod = new ImSheetDetail ();
		$mod->item_no = lang("total");
		$mod->large_qty = 0;
		$mod->order_qty = 0;
		$mod->sub_amt = 0;
		
		$model = $ImSheetDetail->GetSheetDetails ( $no );
		$result = array ();

		$i = 0;
		foreach ( $model as $k => $v ) {
			$result [$i] ["rowIndex"] = $i + 1;
			foreach ( $v as $kk => $vv ) {
				$result [$i] [$kk] = $vv;
				switch ($kk) {
					case "large_qty" :
						$result [$i] [$kk] = sprintf ( "%.2f", $vv );
						$mod->large_qty = $mod->large_qty;
						break;
					case "order_qty" :
						$result [$i] [$kk] = intval ( $vv );
						$mod->order_qty = $mod->order_qty + intval ( $vv );
						break;
					case "sub_amt" :
						$result [$i] [$kk] = sprintf ( "%.2f", $vv );
						$mod->sub_amt = $mod->sub_amt + doubleval ( $vv );
						break;
				}
			}
			$result [$i] ["item_name"] = $v ["item_name"];
			$result [$i] ["item_price"] = sprintf ( "%.2f", $v ["item_price"] );
			$result [$i] ["item_subno"] = $v ["item_subno"];
			$result [$i] ["sale_price"] = sprintf ( "%.2f", $v ["sale_price"] );
			$result [$i] ["item_size"] = $v ["item_size"];
			$result [$i] ["order_qty"] = $v ["real_qty"];
			$result [$i] ["item_unit"] = $v ["item_unit"];
			$result [$i] ["purchase_spec"] = $v ["purchase_spec"];
			$result [$i] ["purchase_tax"] = $v ["purchase_tax"];
			$result [$i] ["sale_price_amt"] = sprintf ( "%.2f", $v ["sale_price"] ) * $v ["real_qty"];
			$result [$i] ["sale_amt"] = formatMoneyDisplay ( $v ["sale_price"] * $v ["real_qty"] );
			$result [$i] ["memo"] = $v ["memo"];
			
			$i ++;
		}
		
		return listJson ( 0, lang ( "success_data" ), $i,$result);
		
	}
	
	public function getYhsheet() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		$offset = ($page - 1) * $rows;
		
		$where="i.approve_flag='1' and i.order_status < 2 and i.valid_date > now() and i.trans_no='YH'" ;
		$keyword=input("keyword");
		if(!empty($keyword)){
			$where.=" and i.sheet_no like '%$keyword%'";
		}
		$rowCount=Db::name("pm_sheet_master as i")->where($where)->count();
		
		$list=Db::name("pm_sheet_master")
		->alias("i")
		->join('pos_branch_info a','i.branch_no= a.branch_no',"LEFT")
		->join('pos_branch_info b','i.branch_no= b.branch_no',"LEFT")
		->field("sheet_no,(case order_status when 0 then '未处理' when 1 then '部分出货' end) as order_status," 
				. "i.branch_no as branch_no,d_branch_no,a.branch_name as branch_name,b.branch_name as d_branch_name")
		->where($where)
		->limit($offset,$rows)
		->select();

		$temp = array ();
		$rowIndex = $offset + 1;
		foreach ( $list as $v ) {
			$tt = array ();
			$tt ["rowIndex"] = $rowIndex;
			$rowIndex ++;
			$tt ["sheet_no"] = $v ["sheet_no"];
			$tt ["order_status"] = $v ["order_status"];
			$tt ["branch_no"] = $v ["branch_no"];
			$tt ["d_branch_no"] = $v ["d_branch_no"];
			$tt ["branch_name"] = $v ["branch_name"];
			$tt ["d_branch_name"] = $v ["d_branch_name"];
			array_push ( $temp, $tt );
		}
		
		return listJson ( 0, lang ( "success_data" ), $rowCount,$temp);
	}
	
	//库存调整单列表
	public function getImSheetNoApproveList() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;
		$start = input ( "start" );
		$end = input ( "end" );
		$sheet_no = input ( "no" );
		$approve_flag = input ( "approve_flag" );
		$oper_id = input ( "oper_id" );
		$branch_no = input ( "branch_no" );//仓库（库存调整单仓库）
		$d_branch_no = input ( "d_branch_no" );//调出仓库
		$transno = input ( "transno" );//调整标记OO'JO'MO'MI等
		$ImSheetMaster=new ImSheetMaster();
		$array=$ImSheetMaster->GetPager ( $rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $d_branch_no, $transno,$branch_no ) ;
		return listJson ( 0, lang ( "success_data" ), $array ['total'], $array ['rows'] );
	}
	
}
