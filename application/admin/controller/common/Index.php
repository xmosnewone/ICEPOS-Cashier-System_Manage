<?php
namespace app\admin\controller\common;
use app\admin\controller\Super;
use app\admin\components\BuildTreeArray;
use model\Item_cls;
use model\Item_info;
use model\TreeNode;
use model\PosBranch;
use model\PosBranchStock;
use model\BaseCode;
use model\SalePurchaser;
use model\PosSaleFlow;
use model\PosPayFlow;
use model\PaymentInfo;
use model\WholesaleClients;
use model\WholesaleType;
use model\PosOperator;
use model\ImSheetMaster;
use model\Supcust;
use model\ItemBarCode;
use model\PosStatus;
use think\Db;

class Index extends Super {

    //获取商品分类树
    public function itemClass() {
        $treeNode = new TreeNode();
        $result = $treeNode->GetItemClsForControls();
        $code=200;
        $message='';
        return treeJson($code, $msg, $result[0]->children);
    }

    //获取商品信息
    public function getItemInfo() {

        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
		
        $item_clsno=input("item_clsno")?input("item_clsno"):'top';
        $keyword=input("keyword")?input("keyword"):'';
        $combine_sta=input("combine_sta")?input("combine_sta"):'';
        $item_stock=input("item_stock")?input("item_stock"):'';
        $branch_no = input("branch_no");
        $supcust_no = input("supcust_no");
        
        $Item_info=new Item_info();
        $PosBranchStock=new PosBranchStock();
        $BaseCode=new BaseCode();
        $Supcust=new Supcust();
        $ItemBarCode=new ItemBarCode();
        
        if (!empty($branch_no)) {
            if (!empty($supcust_no)) {
                $result = $Item_info->SearchModelsForComSelectBySup($supcust_no, $keyword, $branch_no, $item_clsno, $combine_sta, $item_stock, $page, $rows);
                return listJson(0,'',$result["total"],$result["rows"]);
            } else {
                $result = $PosBranchStock->GetItemInfoForComControl($branch_no,$item_clsno, $supcust_no, $combine_sta, $keyword, $item_stock, $page, $rows);
                return listJson(0,'',$result["total"],$result["rows"]);
            }
        } else {

            $startMlv = input("startMlv");
            $endMlv = input("endMlv");
            
            $field="i.item_no,i.item_subno,i.item_name,i.item_clsno,bc.code_name as item_brandname," .
                    "i.unit_no,i.item_size,i.product_area,i.price,i.sale_price," .
                    "i.item_size1,i.num2,i.item_stock,i.purchase_spec,b.sp_company as sp_name," .
                    "i.vip_price,i.sup_ly_rate,i.trans_price,i.status," .
                    "case i.sale_price when 0 then 0  when null then 0 when '' then 0 else round( (i.sale_price-i.price)*100 /i.sale_price,2) end as mlv";
            
            $condition="bc.type_no='PP'";

            if ($startMlv != "") {
            	$condition.=" and i.sale_price>0 and round( (i.sale_price-i.price)*100 /i.sale_price,2 ) >= $startMlv ";
            }
            if ($endMlv != "") {
                $condition.=" and i.sale_price>0 and round( (i.sale_price-i.price)*100 /i.sale_price,2 ) <= $endMlv ";
            }
            $keyword=input("keyword");
            if (!empty($keyword)) {
            	$keyword="'%".$keyword."%'";
            	$condition.=" and (i.item_name like $keyword or i.item_no like $keyword or i.item_clsno like $keyword 
            				or i.item_no in ( select item_no from " . $ItemBarCode->tableName() . " where item_barcode like $keyword )) ";
            }
            $item_clsno=input("item_clsno");
            if (!empty($item_clsno) && $item_clsno != "top") {
            	$item_clsno="'".$item_clsno."'";
            	$condition.=" and i.item_clsno  like $item_clsno";
            }
            //	$supcust_no=input("supcust_no");
            //if (!empty($supcust_no)) {
            //		$condition.=" and i.main_supcust = '$supcust_no'";
            //}
            $combine_sta=input("combine_sta");
            if (!empty($combine_sta)) {
                if ($combine_sta == "9999") {
                    $condition.=" and i.combine_sta  <> '0' ";
                } else {
                    if ($combine_sta == "999") {
                        $combine_sta = "0";
                    }
                    $condition.=" and i.combine_sta  = '$combine_sta' ";
                }
            }

          	
            $rowCount=Db::name("bd_item_info")
				        ->alias('i')
				        ->join('bd_base_code bc','i.item_brand=bc.code_id',"LEFT")
				        ->join('sp_infos b','i.main_supcust=b.sp_no',"LEFT")
				        ->where($condition)
				        ->count();
            
            $offset = ($page - 1) * $rows;
            
            $list=Db::name("bd_item_info")
				        ->alias('i')
				        ->field($field)
				        ->join('bd_base_code bc','i.item_brand=bc.code_id',"LEFT")
				        ->join('sp_infos b','i.main_supcust=b.sp_no',"LEFT")
				        ->where($condition)
				        ->limit($offset,$rows)
				        ->select();
            //读取商品分类
            $Item_cls = new Item_cls();
            $cls = $Item_cls->select()->toArray();
            $clsList=[];
            foreach($cls as $k=>$v){
            	$clsList[$v['item_clsno']]=$v;
            }
            unset($cls);
            
            $result = array();
            $result["total"] = $rowCount;
            $temp = array();
            $rowIndex = $offset + 1;
            $colmns = "item_no,item_subno,item_name,item_clsno,item_brandname,unit_no,item_size,product_area,price,sale_price,item_size1,num2,item_stock,purchase_spec,sp_name,vip_price,sup_ly_rate,trans_price";
            foreach ($list as $k => $v) {
                $tt = array();
                foreach ($v as $kk => $vv) {
                    if (in_array($kk, explode(',', $colmns))) {
                        $tt[$kk] = $vv;
                    }
                }
                $tt["cls_name"] = $clsList[$v['item_clsno']]['item_clsname'];
                $tt["item_unit"] = $v["unit_no"];
                $tt["sp_name"] = $v["sp_name"];
                $tt["order_qty"] = $v["purchase_spec"];
                $tt["item_price"] = sprintf("%.2f", $v["price"]);
                $tt["send_qty"] = 0;
                $tt["large_qty"] = sprintf("%.2f", 1);
                $tt["sub_amt"] = sprintf("%.2f", $v["price"] * $tt["order_qty"]);
                $tt["sale_amt"] = sprintf("%.2f", $v["sale_price"] * $tt["order_qty"]);
                $tt["vip_price1"] = sprintf("%.2f", $v["vip_price"]);
                $tt["sup_ly_rate1"] = sprintf("%.2f", $v["sup_ly_rate"]);
                $tt["trans_price1"] = sprintf("%.2f", $v["trans_price"]);
                $tt["status"] = $v["status"];

                $tt["mlv"] = $v["mlv"] . "%";
                $tt["rowIndex"] = $rowIndex;
                $rowIndex++;
                array_push($temp, $tt);
            }
            $result["rows"] = $temp;
            
            return listJson(0,'',$rowCount,$result["rows"]);
        }
    }

	//获取门店信息
    public function getBranchInfo() {
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
        
        $field="branch_no,branch_name,branch_man,branch_tel";
        $condition="display_flag='1'";
   
        $paramsArr = array();
        $keyword=input("keyword");
        if (!empty($keyword)) {
        	$keyword="'%".$keyword."%'";
        	$condition.=" and (branch_no like $keyword or branch_name like $keyword)";
        }
        
        $branch_no=input("branch_no");
        if (!empty($branch_no)) {
        	$condition.=" and branch_no not in ('".$branch_no."')";
        }
        
        $offset = ($page - 1) * $rows;
        
        $rowCount=Db::name("pos_branch_info")->where($condition)->count();
        
        $list=Db::name("pos_branch_info")
        		->field($field)
        		->where($condition)
        		->limit($offset,$rows)
        		->select();
        
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $colmns = "branch_no,branch_name,branch_man,branch_tel";
        foreach ($list as $k => $v) {
            $tt = array();
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    $tt[$kk] = $vv;
                }
            }
            array_push($temp, $tt);
        }
        $result["rows"] = $temp;
        return listJson(0,'',$rowCount,$result["rows"]);
    }

	//营业员列表数据
    public function getOperInfo() {
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
       
        $field="oper_id,oper_name";
        $condition="num1='1'";
        
        $keyword=input("keyword");
        if (!empty($keyword)) {
        	$keyword="'%".$keyword."%'";
        	$condition.=" and (oper_id like $keyword or oper_name like $keyword)";
        }
       
        $offset = ($page - 1) * $rows;
        $count=Db::name("pos_operator")->where($condition)->count();
        
        $list=Db::name("pos_operator")
        ->field($field)
        ->where($condition)
        ->limit($offset,$rows)
        ->select();
        
        $result = array();
        $result["total"] = $count;
        $temp = array();
        $colmns = "oper_id,oper_name";
        $rowIndex = ($page - 1) * $rows + 1;
        foreach ($list as $k => $v) {
            $tt = array();
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    $tt[$kk] = $vv;
                }
            }
            $tt["rowIndex"] = $rowIndex;
            $rowIndex++;
            array_push($temp, $tt);
        }
        $result["rows"] = $temp;
        return listJson(0,'',$count,$result["rows"]);
    }

    //获取直调出库单信息080
    public function getdearmo() {
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
        $sheet_no = input("keyword");
        $ImSheetMaster=new ImSheetMaster();
        $result=$ImSheetMaster->GetNoneDearMo($sheet_no, $rows, $page);
        return listJson(0,'',$result['total'],$result["rows"]);
    }

    //获取供应商信息
    public function getsupcust() {
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
        $keyword = input("keyword");
        $Supcust=new Supcust();
        $result=$Supcust->GetSupcustPager($rows, $page, $keyword);
        return listJson(0,'',$result['total'],$result["rows"]);
    }

	//获取品牌数据
    public function getBrand() {
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
        
        $field="code_id,code_name";
        $condition="type_no='PP'";
        $keyword=input("keyword");
        if (!empty($keyword)) {
        	$keyword="'%".$keyword."%'";
        	$condition.=" and (code_id like $keyword or code_name like $keyword)";
        }
        
        $offset = ($page - 1) * $rows;
        $count=Db::name("bd_base_code")->where($condition)->count();
        
        $list=Db::name("bd_base_code")
        ->field($field)
        ->where($condition)
        ->limit($offset,$rows)
        ->select();
        
        $temp = array();
        $colmns = "code_id,code_name";
        foreach ($list as $k => $v) {
            $tt = array();
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    $tt[$kk] = $vv;
                }
            }
            array_push($temp, $tt);
        }
        return listJson(0,'',$count,$temp);
    }
    
    // 返回单条商品品牌数据080
    public function getOneBrand() {
    	$brand_no = strtoupper(input ( "brand_no" ));
    	if (empty ( $brand_no )) {
    		return [
    				'code' => 0,
    				'msg' => lang("invalid_variable")
    		];
    	}
    
    	$BaseCode=new BaseCode();
    	$result =$BaseCode->GetByTypeAndCode("PP", $brand_no);
    	if (!empty( $result )) {
    		return ['code'=>1,'data'=>$result];
    	}
    
    	return [
    			'code' => 0,
    			'msg' => lang("brand_not_exist")
    	];
    }

	//商品分类列表数据
    public function getItemClassList() {
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
        
        $field="item_clsno,item_clsname";
        $condition="display_flag='1'";
        $keyword=input("keyword");
        if (!empty($keyword)) {
        	$keyword="'%".$keyword."%'";
        	$condition.=" and (item_clsno like $keyword or item_clsname like $keyword)";
        }

        $offset = ($page - 1) * $rows;
        $count=Db::name("bd_item_cls")->where($condition)->count();
        
        $list=Db::name("bd_item_cls")
        ->field($field)
        ->where($condition)
        ->limit($offset,$rows)
        ->select();
        
        $temp = array();
        $colmns = "item_clsno,item_clsname";
        foreach ($list as $k => $v) {
            $tt = array();
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    $tt[$kk] = $vv;
                }
            }
            array_push($temp, $tt);
        }
        return listJson(0,'',$count,$temp);
    }
		
	// 返回单条商品分类数据080
	public function getcls() {
		$cls_no = input ( "cls_no" );
		if (empty ( $cls_no )) {
			return [ 
					'code' => 0,
					'msg' => lang("invalid_variable")
			];
		}
		
		$Item_cls=new Item_cls();
		$result = $Item_cls->GetItemClsByClsno ( $cls_no );
		if (!empty( $result )) {
			return ['code'=>1,'data'=>$result];
		}
		
		return [
				'code' => 0,
				'msg' => lang("cls_not_exist")
		];
	}
    
	//获取某门店某商品的库存
    public function getbranchstock() {
    	$branno=input("branno");
    	$itemno=input("itemno");
        if (!empty($branno) && !empty($itemno)) {
            $PosBranchStock=new PosBranchStock();
        	$result = $PosBranchStock->GetStockByBraItem($branno, $itemno);
            if (!empty($result)) {
                $item_stock = empty($result->stock_qty) ? 0 : $result->stock_qty;
                return ["code" => 1, "data" => $item_stock];
            } else {
                return ["code" => 0, "data" => 0];
            }
        } else {
        		return ["code" => 0, "msg" => lang("invalid_variable")];
        }
    }

	//获取批发商档案
    public function getWholesaleClientsList() {
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
        $areano = input("areano");
        $keyword = input("keyword");
	
        $WholesaleClients=new WholesaleClients();
        $WholesaleType=new WholesaleType();
        $PosBranch=new PosBranch();
        $BaseCode=new BaseCode();
        $PosOperator=new PosOperator();
        
        $count="SELECT count(*) as total_count ";
        $field="SELECT p.clients_no,p.own_code,p.company,p.status," .
                "p.in_date,p.mobile,p.email,p.account,p.pay_password," .
                "p.type_no,p.linkname,p.phone,p.fax,p.zip_code,p.area_no,p.enterprise_type," .
                "p.balance_way,p.bank_name,p.bank_id,p.check_out_day,p.check_out_date," .
                "p.register_type,p.memo,p.saleman,p.credit_status,p.business_statusxf," .
                "p.license_no,p.tax_no,p.credibility,p.branch_no,c.type_name,p.address,b.branch_name,o.oper_name," .
                "m.code_name as balancename,m2.code_name as areaname,b.branch_name as branchname";
        
        $sql = " FROM " . $WholesaleClients->tableName() . " AS p " .
                " LEFT JOIN " . $WholesaleType->tableName() . " AS c ON p.type_no=c.type_no" .
                " LEFT JOIN " . $PosBranch->tableName() . " AS b ON b.branch_no=p.branch_no " .
                " LEFT JOIN " . $BaseCode->tableName() . " AS m ON m.code_id=p.balance_way AND m.type_no='BW'" .
                " LEFT JOIN " . $BaseCode->tableName() . " AS m2 ON m2.code_id=p.area_no AND m2.type_no='AA'" .
                " LEFT JOIN " . $PosOperator->tableName() . " AS o ON o.oper_id=p.saleman";

        if (!empty($keyword) || !empty($areano)) {
            $sql .= " WHERE 1=1";
            if (!empty($areano)) {
                $sql .= " AND area_no='" . $areano."'";
            }
            if (!empty($keyword)) {
                $sql .= " AND (p.mobile LIKE '%" . $keyword . "%'" 
                		." OR p.linkname LIKE '%" . $keyword . "%'" 
                		." OR p.company LIKE '%" . $keyword . "%'" 
                		. " OR m2.code_name LIKE '%" . $keyword . "%')";
            }
        }

        $countQuery=Db::query($count.$sql);
        $total=$countQuery[0]['total_count'];
        
        $offset=($page - 1) * $rows;
        $limit=$rows;
        $model = Db::query($field.$sql." order by p.in_date desc limit $offset,$limit");
       
        $rowIndex = 1;
        $res = array();
        foreach ($model as $k => $v) {
            $v["rowIndex"] = $rowIndex;
            array_push($res, $v);
            $rowIndex ++;
        }

        return listJson(0,'',$total,$res);
    }

    //获取批发商价格层级
    public function getWholesaleType() {
    	$page = input("page") ? intval(input("page")) : 1;
    	$rows = input("limit") ? intval(input("limit")) : 10;
    	
    	$WholesaleType=new WholesaleType();
    	$total=$WholesaleType->count();
    	
    	$offset=($page - 1) * $rows;
    	$limit=$rows;
    	$model = $WholesaleType->limit($offset,$limit)->select();
 
    	return listJson(0,'',$total,$model);
    }
    
	//获取所有区域
    public function getAreaList() {
    	$BaseCode=new BaseCode();
        $sql = "select code_id as id, type_no as fid, code_name as name  from " . $BaseCode->tableName() . " WHERE type_no='AA'";
        $data = Db::query($sql);
        $bta = new BuildTreeArray($data, 'id', 'fid', 0);
        $tree = $bta->getTreeArray();
        $all = array('id' => 'top', 'name' => "所有区域", "children" => $tree);
        return $all;
    }

	//获取支付方式
    public function getPaymentMethod() {
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
        
        $PaymentInfo=new PaymentInfo();
        $total=$PaymentInfo->getCount(['pay_flag'=>2]);
        
        $offset=($page - 1) * $rows;
        $limit=$rows;
        $sql = "SELECT pay_way, pay_name, rate FROM " . $PaymentInfo->tableName() . " WHERE pay_flag=2 limit $offset,$limit";
        $model = Db::query($sql);

        $ary = array();
        $rowIndex = 1;
        foreach ($model as $k => $v) {
            $v["rowIndex"] = $rowIndex;
            array_push($ary, $v);
            $rowIndex++;
        }
		return listJson(0,'',$total,$ary);
    }

	//获取POS终端
    public function getpos() {
        $branch_no = input("branch_no");
        $keyword = input("keyword");
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
        $PosStatus=new PosStatus();
        $result=$PosStatus->GetModelsForControls($branch_no, $page, $rows, $keyword);
        return listJson(0,'',$result['total'],$result['rows']);
    }

	//获取流水详情-08
    public function getvoucher() {
        $flowno = input("flowno");
        $PosSaleFlow=new PosSaleFlow();
        $PosPayFlow=new PosPayFlow();
        if (empty($flowno)) {
        	return false;
        }
        
		$saleflow = $PosSaleFlow->GetModelsForVoucher ( $flowno );
		$payflow = $PosPayFlow->GetPaySumInfoForVoucher ( $flowno );
		$baseflow = $PosPayFlow->GetBaseInfoForVoucher ( $flowno );
		$result = array ();
		$result ["saleflow"] = $saleflow;
		$result ["payflow"] = $payflow;
		$result ["baseflow"] = $baseflow;
		return $result; 
    }

    //获取门店信息--分页获取
    public function getshopstore() {
        $flag = input("property");
        $keyword = input("keyword");
        $branch_no = input("branch_no");
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
        $PosBranch=new PosBranch();
        $result =$PosBranch->GetAllStoreOrShopForPager($flag, $keyword, $page, $rows, $branch_no);
        return $result;
    }
    
    //获取全部分店信息
    public function getAllshopstore() {
    	$keyword = input("keyword");
    	$PosBranch=new PosBranch();
    	$result =$PosBranch->GetAllStoreOrShop($keyword);
    	return $result;
    }

	//获取商品库存或者商品
    public function getiteminstance() {
        $branch_no = input("branch_no");
        $supcust_no = input("supcust_no");
        $item_no = input("itemno");
        $item_stock = input("item_stock");
        $Item_info=new Item_info();
        $PosBranchStock=new PosBranchStock();
        if (!empty($supcust_no)) {
            $result1 = $Item_info->SearchModelsForComSelectBySup($supcust_no, $item_no, $branch_no, "", "", $item_stock, 1, 1);
            if (count($result1["rows"]) == 1) {
                $result = $result1["rows"][0];
            }
        } else if (!empty($branch_no)) {
            $result = $PosBranchStock->GetInstanceForComSelect($branch_no, $item_no, $supcust_no, $item_stock);
        } else {
            $result = $Item_info->GetItemInfoForComSelect($item_no);
        }
        if (!empty($result)) {
        	return ["code" => 1, "data" => $result];
        } else {
        	return ["code" => 0, "data" => lang("empty_record")];
        }
    }

	//获取基础代码
    public function getcommon() {
        $type_no = input("type_no");
        $keyword = input("keyword");
        $page = input("page") ? intval(input("page")) : 1;
        $rows = input("limit") ? intval(input("limit")) : 10;
        if (empty($type_no)) {
        	return lang('invalid_variable');
        } else {
        	$BaseCode=new BaseCode();
            $result = $BaseCode->GetAllModelsForControls($type_no, $keyword, $page, $rows);
            return $BaseCode;
        }
    }

	//获取采购商
    public function getPurchase() {
        $keyword = input("keyword");
        $page = input("page");
        $rows = input("limit");
        if (empty($page)) {
            $page = 1;
        }
        if (empty($rows)) {
            $rows = 10;
        }
        $SalePurchaser=new SalePurchaser();
        $result = $SalePurchaser->GetPurchasesForControls($keyword, $page, $rows);
        return $result;
    }

}
