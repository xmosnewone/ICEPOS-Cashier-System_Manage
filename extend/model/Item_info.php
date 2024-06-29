<?php
//bd_item_info表
namespace model;
use think\Db;
use model\ItemBarCode;
use model\BdItemInfoBreakpoint;
use app\admin\components\Enumerable\EItemCombSplit;

class Item_info extends BaseModel {

    public $code_name;
    public $item_barcode;
    public $sp_name;
    public $brand_name;
    public $cls_name;
    public $item_qty;
    public $sale_amt;
    public $amount;
    public $rtype;
    public $rid;
    public $updatetime;
    
    protected $pk='item_no';
    protected $name="bd_item_info";


    public function contentedit($item) {
        if ($item->save()) {
            return "OK";
        } else {
            return "ERROR";
        }
    }

    public function edit($item) {
        if ($item->save()) {
            return "OK";
        } else {
            return "ERROR";
        }
    }

    public function add($item_cls) {
        $count1 = $this->where("item_no='{$item_cls->item_no}'")->count();
        $barcode = new ItemBarCode();
        $count2 = $barcode->where("item_barcode='{$item_cls->item_no}'")->count();
        if ($count1 > 0 || $count2> 0) {
            return "REPEAT_NO";
        }
        if ($item_cls->save()) {
            return "OK";
        } else {
            return "ERROR";
        }
    }

    public $mlv;

    public function getall($type, $item_no, $item_name) {
        $where="1=1";
        if ($type != '') {
            $where.=" and item_clsno like ''%$type%";
        }
        if ($item_no != '') {
            $where.=" and item_no ='$item_no'";
        }
        if ($item_name != '') {
            $where.=" and item_name like ''%$item_name%";
        }

        $temp=Db::name($this->name)->where($where)->paginate(10);
        $page=$temp->render();
        
        $return['result'] = $temp;
        $return['pages'] = $page;
        return $return;
    }


    public function GetSingleItem($itemno = "") {
        
        $one=Db::name($this->name)
        ->alias('i')
        ->field("i.item_no,i.item_subno,i.item_name,i.item_stock,c.code_name,i.price,i.unit_no,i.item_clsno,i.item_size,i.sale_price,i.product_area,i.modify_date,i.img_src,i.item_size")
        ->join('bd_base_code c','c.code_id=i.item_brand',"LEFT")
        ->where("i.item_no= '$itemno'")
        ->find();
        return $one;
    }

    public function getoneimages($item_no) {
        $model = $this->where("item_no='{$item_no}'")->select();
        return $model;
    }

    public function GetOne($item_no = "") {
        $model = $this->where("item_no='" . $item_no . "' or item_no in (select item_no from ".$this->prefix."bd_item_barcode where item_barcode='" . $item_no . "')")->find();
        return $model;
    }

    public function GetAllByClsno($cls_no = "") {
        $model = $this->where("item_clsno='$cls_no'")->select();
        return $model;
    }


    public function GetInstance($item_no, $supcust_no) {
        if (empty($supcust_no)) {
            
            $one=$this
	            ->alias('i')
	            ->field("*")
	            ->join('bd_item_barcode b','i.item_no=b.item_no',"LEFT")
	            ->where("i.item_no= '$item_no' or b.item_barcode='$item_no'")
	            ->find();
            return $one;
            
        } else {
            return $this->where("item_no='$item_no' and main_supcust='$supcust_no'")->find();
        }
    }
    
    public function GetItem($item_no) {
    	$model = $this->where("item_no='$item_no'")->find();
    	return $model;
    }

    public $min_qty;
    public $max_qty;
    public $acc_qty;

    //获取符合条件的所有商品信息，返回POS端
    public function GetAllModelsForPos($start,$limit, $condition='',$returnNum) {
        
        $where="1=1";
        if(!empty($condition)){
            $where.=" and ".$condition;
        }

        if($returnNum){
            $total=Db::name($this->name)->where($where)->count();
            return $total;
        }

        $list=Db::name($this->name)
        ->field("item_no,item_subno,item_name,item_subname,item_clsno," .
                "item_brand,item_brandname,unit_no,item_size,product_area," .
                "price,sale_price,build_date,main_supcust,num2," .
                "status,num3,change_price,is_focus,vip_acc_flag," .
                "vip_acc_num,modify_date")
        ->where($where)
        ->limit($start,$limit)
        ->select();

        $BdItemInfoBreakpoint=new BdItemInfoBreakpoint();
        $r_id = $BdItemInfoBreakpoint->GetMaxRidForUpdate();

        $result = array();
        foreach ($list as $v) {
            $tt = array();
            $tt["rid"] = $r_id;
            $tt["rtype"] = "I";
            $tt["item_no"] = $v["item_no"];
            $tt["item_subno"] = $v["item_subno"];
            $tt["item_name"] = $v["item_name"];
            $tt["item_subname"] = $v["item_subname"];
            $tt["item_clsno"] = $v["item_clsno"];
            $tt["item_brand"] = $v["item_brand"];
            $tt["item_brandname"] = $v["item_brandname"];
            $tt["unit_no"] = $v["unit_no"];
            $tt["item_size"] = $v["item_size"];
            $tt["product_area"] = $v["product_area"];
            $tt["price"] = $v["price"];
            $tt["sale_price"] = $v["sale_price"];
            $tt["build_date"] = $v["build_date"];
            $tt["main_supcust"] = $v["main_supcust"];
            $tt["num2"] = $v["num2"];
            $tt["status"] = $v["status"];
            $tt["num3"] = $v["num3"];
            $tt["change_price"] = $v["change_price"];
            $tt["is_focus"] = $v["is_focus"];
            $tt["vip_acc_flag"] = $v["vip_acc_flag"];
            $tt["vip_acc_num"] = $v["vip_acc_num"];
            $tt["modify_date"] = $v["modify_date"];
            $tt["min_qty"] = 0;
            $tt["max_qty"] = 0;

            array_push($result, $tt);
        }
        return $result;
    }


    public function GetModel($item_no) {
        
        $where="";
        if (!empty($item_no)) {
        	$where.=" and s.item_no='$item_no'";
        }
        
        $one=Db::name($this->name)
        ->alias('s')
        ->field("s.item_no,s.item_name,s.item_subno,s.item_subname," .
                "s.item_brand,a.item_clsname as cls_name,s.unit_no,s.item_size," .
                "s.product_area,s.price,s.sale_price,case s.combine_sta when '1' then '捆绑商品' when '2' then '制单拆分' when '3' then '制单组合' when '6' then  '自动转货' when '7' then '自动加工' else '' end as combine_sta")
        ->join('bd_item_cls a','s.item_clsno=a.item_clsno',"LEFT")
        ->join('bd_base_code b','s.item_brand= b.code_id',"LEFT")
        ->where("b.type_no='PP'".$where)
        ->find();
        return $one;
        
    }


    public function IsItemBundle($itemno) {
        $model = $this->where("item_no='$itemno' and combine_sta = ".EItemCombSplit::BUNDLE)->find();
        if (empty($model)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }


    public function GetItemInfoByBarCode($barcode) {
        
        $temp=Db::name($this->name)
        ->alias('s')
        ->field("a.item_barcode as item_barcode,s.item_no,s.item_subno,s.item_name,s.item_subname,s.item_clsno" .
                "s.item_brand,s.unit_no,s.item_size,b.item_clsname as cls_name,c.code_name as brand_name," .
                "s.main_supcust,s.sp_company as sp_name,price,sale_price,combine_sta")
        ->join('bd_item_barcode a','s.item_no=a.item_no',"LEFT")
        ->join('bd_item_cls b','s.item_clsno=b.item_clsno',"LEFT")
        ->join('bd_base_code c','s.item_brand=c.code_id',"LEFT")
        ->join('sp_infos d','s.main_supcust=d.sp_no',"LEFT")
        ->where("c.type_no='PP' and a.item_barcode='$barcode'")
        ->find();
        
        if (!empty($temp)) {
            $model = new Item_info();
            $model->attributes = $temp;
            $model->sp_name = $temp["sp_name"];
            $model->cls_name = $temp["cls_name"];
            $model->brand_name = $temp["brand_name"];
            $model->item_barcode = $temp["item_barcode"];
            return $model;
        }
        return NULL;
    }


    public $item_clsname;
    public $branch_no;
    public $item_stock;
    public $item_price;
    public $order_qty;
    public $large_qty;
    public $sub_amt;
    public $send_qty;
    public $vip_price1;
    public $sup_ly_rate1;
    public $trans_price1;
    public $rowIndex;

    public function SearchModelsForComSelectBySup($supcust_no, $keyword, $branch_no = "", $item_clsno = "", $combine_sta = "", $stock = "", $page = 1, $rows = 10) {
        
        $where="c.type_no='PP'";
        
        if (!empty($branch_no)) {
        	$where.=" and a.branch_no='$branch_no'";
        }
        if ($stock == "1") {
        	$where.=" and a.stock_qty > 0 ";
        }
        if (!empty($item_clsno)) {
        	if ($item_clsno != "top") {
        		$where.=" and s.item_clsno like '%$item_clsno%'";
        	}
        }
        if (!empty($supcust_no)) {
        	$where.=" and g.supcust_no='".trim($supcust_no)."'";
        }
        if (!empty($combine_sta)) {
        	if ($combine_sta == "9999") {
        		$combine_sta = "0";
        		$equal="<>";
        	} else {
        		if ($combine_sta == "999") {
        			$combine_sta = "0";
        		}
        		$equal="=";
        	}
        	$where.=" and s.combine_sta $equal '$combine_sta'";
        }
        if (!empty($keyword)) {
        	$where.=" and (s.item_name like '%$keyword%' or s.item_no like '%$keyword%' or s.item_clsno like '%$keyword%' or s.item_no in (SELECT item_no from " . $this->prefix."bd_item_barcode" . " WHERE item_barcode like '%$keyword%') )";
        }
        
        $offset = ($page - 1) * $rows;
        
        $list=Db::name($this->name)
        ->alias('s')
        ->field("case when a.branch_no is not null then a.branch_no else '.$branch_no.' end as branch_no,s.item_no,a.stock_qty as item_stock," .
                "s.item_subno,s.item_name,s.item_clsno,b.item_clsname," .
                "s.item_brand,s.unit_no,s.item_size,c.code_name as item_brandname," .
                "s.product_area,s.purchase_spec,s.price as item_price,s.sale_price," .
                "d.sp_company as sp_name,s.purchase_spec,s.vip_price,s.sup_ly_rate,s.trans_price,s.status" .
                ",s.item_size1,s.num2,s.price")
        ->join('pos_branch_stock a','s.item_no = a.item_no',"LEFT")
        ->join('bd_item_cls b','s.item_clsno= b.item_clsno',"LEFT")
        ->join('bd_base_code c','s.item_brand = c.code_id',"LEFT")
        ->join('pc_branch_price f','s.branch_no=f.branch_no and s.item_no=f.item_no',"LEFT")
        ->join('bd_supcust_item g','s.item_no=g.item_no',"LEFT")
        ->join('sp_infos d','g.supcust_no= d.sp_no',"LEFT")
        ->limit($offset,$rows)
        ->where($where)
        ->select();
        
        $count=Db::name($this->name)
        ->alias('s')
        ->join('pos_branch_stock a','s.item_no = a.item_no',"LEFT")
        ->join('bd_item_cls b','s.item_clsno= b.item_clsno',"LEFT")
        ->join('bd_base_code c','s.item_brand = c.code_id',"LEFT")
        ->join('pc_branch_price f','s.branch_no=f.branch_no and s.item_no=f.item_no',"LEFT")
        ->join('bd_supcust_item g','s.item_no=g.item_no',"LEFT")
        ->join('sp_infos d','g.supcust_no= d.sp_no',"LEFT")
        ->where($where)
        ->count();
       
        $result = array();
        $result["total"] = $count;
        $temp = array();
        $rowIndex = $offset + 1;
        foreach ($list as $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["branch_no"] = $v["branch_no"];
            $tt["item_no"] = $v["item_no"];
            $tt["item_stock"] = $v["item_stock"];
            $tt["item_subno"] = $v["item_subno"];
            $tt["item_name"] = $v["item_name"];
            $tt["item_clsno"] = $v["item_clsno"];
            $tt["item_clsname"] = $v["item_clsname"];
            $tt["item_brand"] = $v["item_brand"];
            $tt["item_brandname"] = $v["item_brandname"];
            $tt["unit_no"] = $v["unit_no"];
            $tt["item_unit"] = $v["unit_no"];
            $tt["item_size"] = $v["item_size"];
            $tt["product_area"] = $v["product_area"];
            $tt["purchase_spec"] = $v["purchase_spec"];
            $tt["num2"] = $v["num2"];
            $tt["price"] = formatMoneyDisplay($v["price"]);
            $tt["item_price"] = formatMoneyDisplay($v["item_price"]);
            $tt["sale_price"] = formatMoneyDisplay($v["sale_price"]);
            $tt["sp_name"] = $v["sp_name"];
            $tt["order_qty"] = formatMoneyDisplay($v["purchase_spec"]);
            $tt["large_qty"] = 1;
            $tt["sub_amt"] = sprintf("%.2f", formatMoneyDisplay($v["item_price"] * $tt["purchase_spec"]));
            $tt["sale_amt"] = sprintf("%.2f", formatMoneyDisplay($v["sale_price"] * $tt["purchase_spec"]));
            $tt["send_qty"] = 0;
            $tt["large_qty"] = sprintf("%.2f", 1);
            $tt["vip_price"] = sprintf("%.2f", $v["vip_price"]);
            $tt["vip_price1"] = sprintf("%.2f", $v["vip_price"]);
            $tt["sup_ly_rate1"] = sprintf("%.2f", $v["sup_ly_rate"]);
            $tt["trans_price1"] = sprintf("%.2f", $v["trans_price"]);
            $tt["item_size1"] = sprintf("%.2f", $v["item_size1"]);
            $tt["status"] = $v["status"];
            if (!empty($tt["sale_price"]) && doubleval($tt["sale_price"]) != 0) {
                $t_mlx = doubleval(sprintf("%.4f", ($tt["sale_price"] - $tt["price"]) / $tt["sale_price"])) * 100;
                $tt["mlv"] = strval($t_mlx) . "%";
            } else {
                $tt["mlv"] = "";
            }
            $rowIndex++;
            array_push($temp, $tt);
        }
        $result["rows"] = $temp;
        return $result;
    }

    public $d_stock_qty;
	
    //返回当前门店商品中库存数量少于最少存量指标的商品
    //1.商品仓库->商品维护->商品存量指标下新增记录
    //2.库存管理->库存调整单添加商品库存记录
    public function GetCompareStock($branch_no, $d_branch_no) {
        
        $where="a.stock_qty < c.min_qty";
        $where.=" and a.branch_no='$branch_no'";
        $where.=" and b.branch_no='$d_branch_no'";
        $list=Db::name($this->name)
        ->alias('s')
        ->field("s.item_no,s.item_name,s.unit_no,s.item_size,a.stock_qty as item_stock,b.stock_qty as d_stock_qty")
        ->join('pos_branch_stock a','s.item_no=a.item_no',"LEFT")
        ->join('im_stock_target c','a.item_no=c.item_no and a.branch_no=c.branch_no',"LEFT")
        ->join('pos_branch_stock b','s.item_no=b.item_no',"LEFT")
        ->where($where)
       	->select();

        $result = array();
        foreach ($list as $v) {
            $tt = array();
            $tt["item_no"] = $v["item_no"];
            $tt["item_name"] = $v["item_name"];
            $tt["item_size"] = $v["item_size"];
            $tt["unit_no"] = $v["unit_no"];
            $tt["item_stock"] = $v["item_stock"];
            $tt["d_stock_qty"] = $v["d_stock_qty"];
            array_push($result, $tt);
        }
        return $result;
    }

    //对商品有更新记录都返回到POS端
    public function GetUpdateDataForPos($branch_no, $rid = "", $updatetime = "") {

            if (empty($rid)) {
            	$rid = 0;
            }
            
            $where="a.rid > $rid";
            
            if (!empty($updatetime)) {
            	$where.=" and a.updatetime > '$updatetime'";
            }
            if(!empty($branch_no)){
                //$where.=" and d.branch_no='$branch_no'";
            }
            /*$result=Db::name($this->name)
            ->alias('s')
            ->field("a.rid, a.rtype, a.updatetime, a.item_no, s.item_name, " .
                    "s.item_subno, s.item_subname, s.item_clsno, " .
                    "s.item_brand, s.item_brandname, s.unit_no, s.item_size, s.product_area, " .
                    "s.price, s.sale_price, s.build_date, s.main_supcust, s.num2, " .
                    "s.status, s.num3, s.change_price, s.is_focus, s.vip_acc_flag, " .
                    "s.vip_acc_num, s.modify_date, d.min_qty, d.max_qty, d.acc_qty")
            ->join("bd_item_info_breakpoint a","s.item_no = a.item_no","RIGHT")
            ->join("pos_branch_stock d","s.item_no = d.item_no","LEFT")
            ->where($where)
            ->select();*/
            $result=Db::name($this->name)
            ->alias('s')
            ->field("a.rid, a.rtype, a.updatetime, a.item_no, s.item_name, " .
                "s.item_subno, s.item_subname, s.item_clsno, " .
                "s.item_brand, s.item_brandname, s.unit_no, s.item_size, s.product_area, " .
                "s.price, s.sale_price, s.build_date, s.main_supcust, s.num2, " .
                "s.status, s.num3, s.change_price, s.is_focus, s.vip_acc_flag, " .
                "s.vip_acc_num, s.modify_date")
            ->join("bd_item_info_breakpoint a","s.item_no = a.item_no","RIGHT")
            ->where($where)
            ->select();

        $list = array();
        if ($rid == "-1") {
        	$BdItemInfoBreakpoint=new BdItemInfoBreakpoint();
            $r_id = $BdItemInfoBreakpoint->GetMaxRidForUpdate();
        }
        foreach ($result as $v) {
            $tt = array();
            $tt["rid"] = $rid == "-1" ? $r_id : $v["rid"];
            $tt["rtype"] = $v["rtype"];
            $tt["updatetime"] = $v["updatetime"];
            $tt["item_no"] = $v["item_no"];
            $tt["item_name"] = $v["item_name"];
            $tt["item_subno"] = $v["item_subno"];
            $tt["item_subname"] = $v["item_subname"];
            $tt["item_clsno"] = $v["item_clsno"];
            $tt["item_brand"] = $v["item_brand"];
            $tt["item_brandname"] = $v["item_brandname"];
            $tt["unit_no"] = $v["unit_no"];
            $tt["item_size"] = $v["item_size"];
            $tt["product_area"] = $v["product_area"];
            $tt["price"] = $v["price"];
            $tt["sale_price"] = $v["sale_price"];
            $tt["build_date"] = $v["build_date"];
            $tt["main_supcust"] = $v["main_supcust"];
            $tt["num2"] = $v["num2"];
            $tt["status"] = $v["status"];
            $tt["num3"] = $v["num3"];
            $tt["change_price"] = $v["change_price"];
            $tt["is_focus"] = $v["is_focus"];
            $tt["vip_acc_flag"] = $v["vip_acc_flag"];
            $tt["vip_acc_num"] = $v["vip_acc_num"];
            $tt["modify_date"] = $v["modify_date"];
            $tt["min_qty"] =0;
            $tt["max_qty"] = 0;
            $tt["acc_qty"] = 0;
            array_push($list, $tt);
        }
        return $list;
    }

    public function GetDataForPos($branch_no, $rid = "", $updatetime = "") {

    }


    public function GetItemInfoForComSelect($keyword) {
        
        $where="1=1";
        if (!empty($keyword)) {
        	$where.=" and (i.item_name like '%$keyword%' or i.item_no like '%$keyword%' or i.item_clsno like '%$keyword%' or i.item_no in (select item_no from " . $this->prefix."bd_item_barcode" . " where item_barcode like '%$keyword%') )";
        }
        
        $list=Db::name($this->name)
        ->alias('i')
        ->field("i.item_no,i.item_subno,i.item_name,i.item_clsno,i.item_brandname,i.unit_no,i.item_size,i.product_area,i.price,i.sale_price,i.item_size1,i.num2,i.item_stock,i.purchase_spec,b.sp_company as sp_name,i.vip_price,i.sup_ly_rate,i.trans_price")
        ->join('sp_infos b','i.main_supcust=b.sp_no',"LEFT")
        ->where($where)
        ->find();
        
        $count=Db::name($this->name)
        ->alias('i')
       	->join('sp_infos b','i.main_supcust=b.sp_no',"LEFT")
        ->where($where)
        ->count();
        
        if (!empty($list)) {
            $result = array();
            $result["item_no"] = $list["item_no"];
            $result["item_subno"] = $list["item_subno"];
            $result["item_name"] = $list["item_name"];
            $result["item_clsno"] = $list["item_clsno"];
            $result["item_brandname"] = $list["item_brandname"];
            $result["unit_no"] = $list["unit_no"];
            $result["item_size"] = $list["item_size"];
            $result["product_area"] = $list["product_area"];
            $result["price"] = $list["price"];
            $result["sale_price"] = $list["sale_price"];
            $result["item_size1"] = $list["item_size1"];
            $result["num2"] = $list["num2"];
            $result["item_stock"] = $list["item_stock"];
            $result["purchase_spec"] = $list["purchase_spec"];
            $result["sp_name"] = $list["sp_name"];
            $result["vip_price"] = $list["vip_price"];
            $result["sup_ly_rate"] = $list["sup_ly_rate"];
            $result["trans_price"] = $list["trans_price"];
            $result["item_unit"] = $list["unit_no"];
            $result["order_qty"] = $list["purchase_spec"];
            $result["item_price"] = sprintf("%.2f", $list["price"]);
            $result["item_price"] = sprintf("%.2f", $list["price"]);
            $result["send_qty"] = 0;
            $result["large_qty"] = sprintf("%.2f", 1);
            $result["sub_amt"] = sprintf("%.2f", $list["price"] * $result["order_qty"]);
            $result["sale_amt"] = sprintf("%.2f", $list["sale_price"] * $result["order_qty"]);
            $result["vip_price1"] = sprintf("%.2f", $list["vip_price"]);
            $result["sup_ly_rate1"] = sprintf("%.2f", $list["sup_ly_rate"]);
            $result["trans_price1"] = sprintf("%.2f", $list["trans_price"]);
            if (!empty($list["sale_price"]) && doubleval($list["sale_price"]) != 0) {
                $t_mlx = doubleval(sprintf("%.4f", ($list["sale_price"] - $list["price"]) / $list["sale_price"])) * 100;
                $result["mlv"] = strval($t_mlx) . "%";
            } else {
                $result["mlv"] = "";
            }
            //返回符合条件的记录数量
            $result['ItemCount']=$count;
            return $result;
        } else {
            return "";
        }
    }


    public function GetItemInfoBySupcust($supcust_no, $page = 1, $rows = 10) {
        
        $where="1=1";
        if(!empty($supcust_no)){
        	$where.=" and a.supcust_no='$supcust_no'";
        }
        
        $offset = ($page - 1) * $rows;
        $list=Db::name($this->name)
        ->alias('s')
        ->field("s.item_no,s.item_name,s.item_subname,s.product_area,s.unit_no,s.item_size,s.price,s.sale_price")
        ->join('bd_supcust_item a','s.item_no=a.item_no',"LEFT")
        ->where($where)
        ->limit($offset,$rows)
        ->select();
        
        $count=Db::name($this->name)
        ->alias('s')
        ->join('bd_supcust_item a','s.item_no=a.item_no',"LEFT")
        ->where($where)
        ->count();
        
        $result = array();
        $result["total"] = $count;
        $result["rows"] = $list;
        return $result;
    }
    
    public function del($item_no) {
    	return $this->where("item_no='$item_no'")->delete();
    }

}
