<?php
namespace app\admin\controller\stock;
use app\admin\controller\Super;
use model\Item_info;
use model\Item_cls;
use model\PosBranchStock;
use model\PosBranch;
use model\Supcust;
use model\StockTarget;
use think\Db;
/**
 * 库存异常告警
 */
class Warning extends Super {
	
	//首页
	public function index() {
		return $this->fetch( "stock/warning" );
	}
	//执行搜索数据
	public function search() {
		$page = input ( 'page' ) ? intval ( input ( 'page' ) ) : 1;
		$rows = input ( 'limit' ) ? intval ( input ( 'limit' ) ) : 10;

		$branch_no = input ("branch_no");
		$brand_no = input ("brand_no") ;
		$class_no = input ("class_no") ;
		$sp_no = input ("sp_no");
		$item_no =input ("item_no");
		$sub_no = input ("sub_no");
		if (empty ( $branch_no )) {
			return listJson ( 0, lang ( "success_data" ), 0, [] );
		}
		$array=[];
		$array ["branch_no"] = $branch_no;
		$array ["brand_no"] = $brand_no;
		$array ["class_no"] = $class_no;
		$array ["sp_no"] = $sp_no;
		$array ["item_no"] = $item_no;
		$array ["sub_no"] = $sub_no;
		
		$result = $this->GetQueryPager ( $rows, $page, $array );
		return listJson ( 0, lang ( "success_data" ), $result ['total'], $result ['rows'] );	
	}
	
	private function GetQueryPager($rows, $page, $array) {
		$StockTarget=new StockTarget();
		$Item_info = new Item_info ();
		$Item_cls = new Item_cls ();
		$PosBranchStock = new PosBranchStock ();
		$PosBranch = new PosBranch ();
		$Supcust = new Supcust ();
		
		$fieldSql = "select f.branch_no, f.branch_name,s.item_no,i.item_name,i.item_brand,i.purchase_spec
				,item_rem,i.item_brandname,i.price,i.item_clsno,c.item_clsname,i.sale_price
				,i.unit_no,i.item_size,b.stock_qty,p.sp_company,b.max_qty,b.min_qty";
		
		$countSql = "select count(*) as total";
		
		$from = " from " . $StockTarget->tableName () . " as s " 
				. "left join " . $Item_info->tableName () . " as i on s.item_no=i.item_no " 
				. "left join " . $Item_cls->tableName () . " as c on i.item_clsno=c.item_clsno " 
				. "left join " . $PosBranchStock->tableName () . " as b on b.item_no=s.item_no and b.branch_no=s.branch_no " 
				. "left join " . $PosBranch->tableName () . " as f on f.branch_no=b.branch_no " 
				. "left join " . $Supcust->tableName () . " as p on i.main_supcust=p.sp_no where b.stock_qty <= b.min_qty";
		
		$sql="";
		if (! empty ( $array )) {
			$prefix=$this->dbprefix;
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
			if (! empty ( $array ["sub_no"] )) {
				$sql .= " and i.item_subno like '%" . $array ["sub_no"] . "%'";
			}
			if (! empty ( $array ["item_no"] )) {
				$sql .= " and (i.item_no like '%" . $array ["item_no"] . "%' or i.item_no 
						in (select item_no from ".$prefix."bd_item_barcode where item_barcode like '%" . $array ["item_no"] . "%')) ";
			}
		} else {
			return ['total'=>0,"rows"=>[]];
		}
		
		$cres = Db::query ( $countSql . $from . $sql );
		$result ["total"] = $cres [0] ['total'];
		
		$offset = ($page - 1) * $rows;
		$rowIndex = ($page - 1) * $rows + 1;
		
		$model = Db::query ( $fieldSql . $from . $sql . " limit $offset,$rows" );
		
		$colmns = "branch_no,branch_name,item_no,item_name,item_brand,item_brandname,purchase_spec
				,item_rem,price,item_clsno,item_clsname,sale_price,unit_no,item_size,stock_qty,max_qty,min_qty,sp_company";
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
}
