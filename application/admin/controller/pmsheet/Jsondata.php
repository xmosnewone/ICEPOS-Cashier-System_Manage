<?php
namespace app\admin\controller\pmsheet;
use app\admin\controller\Super;
use model\PmSheetDetail;
use model\PmSheetMaster;

class Jsondata extends Super {

    public function getPmSheetDetail() {
        $no = input("no");
        if (empty($no)) {
        	return ['code'=>false,'msg'=>lang("invalid_variable")];
        }
		
		$PD=new PmSheetDetail();
		$model = $PD->Get ( $no );
		$result = array ();

		$i = 0;
		
		foreach ( $model as $k => $v ) {
			foreach ( $v as $kk => $vv ) {
				$result [$i] [$kk] = $vv;
				switch ($kk) {
					case "large_qty" :
						$result [$i] [$kk] = sprintf ( "%.2f", $vv );
						break;
					case "order_qty" :
						$result [$i] [$kk] = intval ( $vv );
						break;
					case "sub_amt" :
						$result [$i] [$kk] = sprintf ( "%.2f", $vv );
						break;
				}
			}
			$result [$i] ["rowIndex"] = $i + 1;
			$result [$i] ["item_name"] = $v ["item_name"];
			$result [$i] ["item_price"] = sprintf ( "%.2f", $v ["item_price"] );
			$result [$i] ["item_subno"] = $v ["item_subno"];
			$result [$i] ["sale_price"] = sprintf ( "%.2f", $v ["sale_price"] );
			$result [$i] ["sale_amt"] = sprintf ( "%.2f", $v ["sale_price"] * $v ["real_qty"] );
			$result [$i] ["item_size"] = $v ["item_size"];
			$result [$i] ["order_qty"] = sprintf ( "%.2f", $v ["real_qty"] );
			$result [$i] ["item_unit"] = $v ["item_unit"];
			$result [$i] ["purchase_spec"] = $v ["purchase_spec"];
			$result [$i] ["purchase_tax"] = $v ["purchase_tax"];
			$result [$i] ["sale_price_amt"] = sprintf ( "%.2f", $v ["sale_price"] ) * $v ["real_qty"];
			$result [$i] ["memo"] = $v ["memo"];
			
			$i ++;
		}

		return listJson(0,lang("success_data"),$i, $result);
    }


    public function getPmSheetNoApproveList() {
        $page =input('page') ? intval(input('page')) : 1;
        $rows =input('limit') ? intval(input('limit')) : 10;
        
        $start = input("start");
        $end = input("end");
        $sheet_no = input("no");
        $approve_flag = input("approve_flag");
        $oper_id = input("oper_id");
        $branch_no = input("branch_no");
        $transno = input("transno");
        $PmSheetMaster=new PmSheetMaster();
        $result=$PmSheetMaster->GetPager($rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $branch_no, $transno);
        return listJson(0,lang("success_data"),$result['total'], $result['rows']);
    }

}
