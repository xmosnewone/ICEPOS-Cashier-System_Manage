<?php
namespace app\admin\controller\stock;
use app\admin\controller\Super;
use model\Item_info;
use model\Item_cls;
use model\PosBranchStock;
use model\PosBranch;
use model\Supcust;
use model\BaseCode;
use think\Db;
use think\Controller;
/**
 * 库存报表
 */
class Singlestock extends Super {
	/* 单品库存 */
	public function index() {
		return $this->fetch ( 'stock/goodsstock' );
	}
	/* 零库存 */
	public function zerostock() {
		return $this->fetch ( 'stock/zerostock' );
	}
	/* 库存查询 */
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
		$is_zero = input ( "is_zero" )?input ( "is_zero" ):0;
		$array ["branch_no"] = $branch_no;
		$array ["brand_no"] = $brand_no;
		$array ["class_no"] = $class_no;
		$array ["sp_no"] = $sp_no;
		$array ["item_name"] = $item_name;
		$array ["item_no"] = $item_no;
		$array ["stock_big"] = $stock_big;
		$array ["stock_small"] = $stock_small;
		$array ["chkstock_notnil"] = $chkstock_notnil;
		$array ["is_zero"] = $is_zero;
		
		$result = $this->GetSeachPager ( $rows, $page, $array );
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );
	}
	
	private function GetSeachPager($rows, $page, $array) {
		$Item_info = new Item_info ();
		$Item_cls = new Item_cls ();
		$PosBranchStock = new PosBranchStock ();
		$PosBranch = new PosBranch ();
		$Supcust = new Supcust ();
		$BaseCode=new Basecode();
		
		$fieldSql = "select f.branch_no, f.branch_name,i.item_no,i.item_name,i.item_brand,i.purchase_spec,i.item_rem,i.item_brandname
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
			if (! empty ( $array ["is_zero"] )&&$array ["is_zero"]==1) {
				$sql .= " and b.stock_qty =0";
			}
		}
		
		$cres = Db::query ( $countSql . $from . $sql );
		$result ["total"] = $cres [0] ['total'];
		
		$offset = ($page - 1) * $rows;
		$rowIndex = ($page - 1) * $rows + 1;
		
		$model = Db::query ( $fieldSql . $from . $sql . " limit $offset,$rows" );
		
		$colmns = "branch_no,branch_name,item_no,item_name,item_brand,item_brandname,purchase_spec,item_rem,price
					,item_clsno,item_clsname,sale_price,unit_no,item_size,stock_qty,sp_company";
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
	
	/* 库存查询 */
	public function export() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = 100000;//导出10万商品
	
		$branch_no = input ( "branch_no" );
		$brand_no = input ( "brand_no" );
		$class_no = input ( "class_no" );
		$sp_no = trim ( input ( "sp_no" ) );
		$item_name = input ( "item_name" );
		$item_no = input ( "item_no" );
		$stock_big = input ( "stock_big" );
		$stock_small = input ( "stock_small" );
		$chkstock_notnil = input ( "chkstock_notnil" );
		$is_zero = input ( "is_zero" )?input ( "is_zero" ):0;
		$where ["branch_no"] = $branch_no;
		$where ["brand_no"] = $brand_no;
		$where ["class_no"] = $class_no;
		$where ["sp_no"] = $sp_no;
		$where ["item_name"] = $item_name;
		$where ["item_no"] = $item_no;
		$where ["stock_big"] = $stock_big;
		$where ["stock_small"] = $stock_small;
		$where ["chkstock_notnil"] = $chkstock_notnil;
		$where ["is_zero"] = $is_zero;
	
		$result = $this->GetSeachPager ( $rows, $page, $where );
		
		$doc['title']=$start.'单品库存报表';
		$field=['item_no','item_name','unit_no','item_size'
				,'sale_price','stock_qty','purchase_spec','item_brandname','sp_company'
				];
		$title=['商品货号','商品名称','单位','规格','售价'
				,'库存数量','箱装数','品牌','供应商'];
		
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