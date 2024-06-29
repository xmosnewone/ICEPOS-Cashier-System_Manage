<?php
//front_saleflow表
namespace model;
use think\Db;
use model\FrontFlow;
use model\PosBranchStock;

class FrontSaleflow extends BaseModel {

	protected $pk='sheet_no';
	protected $name="front_saleflow";

    public function search($condition=[]) {

    	$list=Db::table($this->table)
    	->where($condition)
    	->select();
    }

    public function AddDetails($details, $branch_no, $isstock) {
        $result = 0;
        foreach ($details as $detail) {
            $result = $this->Add($detail, $branch_no, $isstock);
            if ($result !== 1) {
                if ($result === -1) {
                    $result = "-1," . $detail->item_no;
                }
                break;
            }
        }
        return $result;
    }


    public function Add($detail, $branch_no, $isstock) {
    	$frontFlow=new FrontFlow();
    	$posBrandStock=new PosBranchStock();
        $result = $this->Check($detail);
        if ($result === 1) {
            try {
                if ($detail->save()) {

                    if ($isstock===true) {
                        if ($detail->sell_way== "A") {
                            $result =$posBrandStock->UpdateStockForFlow($detail['sheet_no'], "-", $branch_no, $detail['item_no'], $detail['real_qty'], $detail['sell_way']);
                        } else {
                            $result =$posBrandStock->UpdateStockForFlow($detail['sheet_no'], "+", $branch_no, $detail['item_no'], $detail['real_qty'], $detail['sell_way']);
                        }
                    }
                }
            } catch (\Exception $ex) {
            	write_log("订单商品表新增订单商品(AddDetail)异常:" . $ex,"FrontSaleFlow");
                $result = -2;
            }
        }
        return $result;
    }


    private function Check($detail) {
        $result = 0;
        try {
            if (empty($detail)) {
                $result = 0;
            } else if (empty($detail['sheet_no'])) {
                $result = "订单号不能为空";
            } else if (empty($detail['item_no'])) {
                $result = "商品编号不能为空";
            } else if (!is_numeric($detail['real_qty'])) {
                $result = "商品数量格式不正确";
            } else if (doubleval($detail['real_qty']) <= 0) {
                $result = "商品数量必须大于零";
            } else if (!is_numeric($detail['price'])) {
                $result = "商品售价格式不正确";
            } else {
                $result = 1;
            }
        } catch (\Exception $ex) {
        	write_log("订单商品表检查订单商品(Check)异常:" . $ex,"FrontSaleFlow");
            $result = -2;
        }
        return $result;
    }


    public $item_name;
    public $sale_price;
    public $img_src;
    public $item_clsno;
    public $item_name;
    public $item_unit;
    public $item_size;
    public $sub_amt;
    public $rowIndex;

    public function GetSaleFlow($sheet_no) { 
        $list=Db::table($this->table)
        ->alias('s')
        ->field("s.item_no,s.price,s.real_qty,a.item_name,a.sale_price,a.item_size,a.img_src,a.item_clsno")
        ->join('bd_item_info a','s.item_no=a.item_no',"LEFT")
        ->where("s.sheet_no='$sheet_no'")
        ->select();
        
        return $list;
        
    }
    
    public function GetSaleDetails($sheet_no) {
    	
    	$list=Db::name($this->name)
    	->alias('s')
    	->field("s.item_no,round(s.real_qty,2) as real_qty,round(s.price,2) as price,a.item_name,a.unit_no as item_unit,a.item_size" .
    			",round(s.real_qty*s.price,2) as sub_amt")
    	->join('bd_item_info a','s.item_no=a.item_no',"LEFT")
    	->where("s.sheet_no='$sheet_no'")
    	->select();
    	
    	$result = array();
    	$rowIndex=1;
    	foreach ($list as $v) {
    		$tt=array();
    		$tt["rowIndex"]=$rowIndex;
    		$tt["item_no"]=$v["item_no"];
    		$tt["real_qty"]=$v["real_qty"];
    		$tt["price"]=$v["price"];
    		$tt["item_name"]=$v["item_name"];
    		$tt["item_unit"]=$v["item_unit"];
    		$tt["item_size"]=$v["item_size"];
    		$tt["sub_amt"]=$v["sub_amt"];
    		array_push($result, $tt);
    		$rowIndex++;
    	}
    	return $result;
    }

}
