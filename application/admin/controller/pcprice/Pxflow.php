<?php
namespace app\admin\controller\pcprice;
use app\admin\controller\Super;
use app\admin\components\Enumerable\ESheetTrans;

use model\PcPriceMaster;
use model\PosBranch;
use model\SysSheetNo;
use model\PcPriceDetail;
use model\BaseCode;
use model\Item_info;
use model\Supcust;
use think\Db;

class Pxflow extends Super {

    //首页列表页
    public function index(){
        return $this->fetch("pcprice/pxindex");
    }
    
    //调价单详细页面
    public function sheetDetail() {

        $operDate = date('Y-m-d H:i', $this->_G['time']);
        $orderMan = session('loginname');
        $sheetno = input("sheetno");
        $approve = "add";
        if (!empty($sheetno)) {
        	$PcPriceMaster=new PcPriceMaster();
            $modelMaster = $PcPriceMaster->Get($sheetno);
            $pageParams["sheetNo"] = $modelMaster["sheet_no"];
            $pageParams["branchNo"] = $modelMaster["branch_no"];
            $pageParams["branchName"] = $modelMaster["branch_name"];
            $operDate = $modelMaster["oper_date"];
            $pageParams["operId"] = $modelMaster["oper_id"];
            $pageParams["confirmMan"] = $modelMaster["confirm_man"];
            $pageParams["workDate"] = $modelMaster["work_date"];
            $pageParams["operName"] = $modelMaster["oper_name"];
            $orderMan=$modelMaster["oper_id"];
            if ($modelMaster["approve_flag"] == 1) {
                $approve = "approve";
            } else {
                $approve = "update";
            }
            $pageParams["memo"] = $modelMaster["memo"];
            $pageParams['branchlist'] = $modelMaster["branchlist"];
          
        }else{
			$branch=new PosBranch();
            $branchall=$branch->select();
            $branchlist='';
            foreach($branchall as $k=>$v){
                $branchlist.=$v['branch_no'].",";
            }
            $branchlist=rtrim($branchlist,',');
            $pageParams["branchNo"] = 'ALL';
            $pageParams["branchName"] = '所有门店';
            $pageParams['branchlist'] = $branchlist;
        }
        $pageParams['approve'] = $approve;
        $pageParams['operDate'] = $operDate;
        $pageParams['orderMan'] = $orderMan;
        $this->assign("pageParams", $pageParams);
        return $this->fetch("pcprice/pxsheet");
    }

    //保存调价单
    public function save() {
       
        if (!IS_POST) {
        	return ["code" => 0, "msg" => lang("illegal_operate")];
        }
        
        $branch_no = input("branchno");
        $oper_date = input("operdate");
        $branchlist =input("branchlist");
        $oper_no = input("operno");
        $memo = input("memo");
        $operFunc = "add";
        $sheetno = input("sheetno");
        $items=input("items/a");
        $time=$this->_G['time'];

        if (empty($sheetno)) {
        	$SysSheetNo=new SysSheetNo();
            $sheetno = $SysSheetNo->CreateSheetNo(ESheetTrans::PX, $branch_no);
        } else {
            $sheetno = input("sheetno");
            $operFunc = "update";
        }
        if (isset($branch_no) && !empty($branch_no) && isset($items) && !empty($items)) {
            if (empty($items)) {
            	return ["code" => 0, "msg" => lang("illegal_data")];
            }
            $amount = 0;
            $details = array();

            $monnow=date("m",$time);
            if($monnow<12){
                $year=date("Y",$time);
                $month=$monnow+1;
            }else{
                $year=date("Y",$time)+1;
                $month='01';
            }
            
            foreach ($items as $k => $v) {
                if (!empty($v['item_no'])) {
                    $detail = new PcPriceDetail();
                    $detail->sheet_no = $sheetno;
                    $detail->price_type = '1';
                    $detail->item_no = $v["item_no"];

                    $detail->old_price = $v["price"];
                    $detail->new_price = $v["price1"];

                    $detail->old_price1 = $v["sale_price"];
                    $detail->new_price1 = $v["sale_price1"];

                    $detail->old_price2 = $v["vip_price"];
                    $detail->new_price2 = $v["vip_price1"];

                    $detail->old_price3 = $v["sup_ly_rate"];
                    $detail->new_price3 = $v["sup_ly_rate1"];

                    $detail->old_price4 = $v["trans_price"];
                    $detail->new_price4 = $v["trans_price1"];
                    
                    $detail->start_date = date("Y-m-d H:i:s",$time);
                    
                    $detail->end_date = $year."-".$month.date("-d H:i:s",$time);

                    array_push($details, $detail);
                }

            }

            if (count($details) == 0) {
            	return ["code" => 0, "msg" =>lang("illegal_data")];
            }
            //$orderman = session("loginname");
            $master = new PcPriceMaster();
            $master->sheet_no = $sheetno;
            $master->branch_no = $branch_no;
            $master->oper_id = $oper_no;
            $master->trans_no = ESheetTrans::PX;
            $master->oper_date = date("Y-m-d H:i:s",$time);
            $master->branchlist = $branchlist;
            $master->valid_flag = '0';
            $master->approve_flag = '0';
            $master->sale_way = 'A';
            $master->branch_flag='000';
            $master->week='1111111';
            $master->memo = $memo;
            $PcPriceMaster=new PcPriceMaster();
            $res = $PcPriceMaster->Add($master, $details, $operFunc, ESheetTrans::PX);
            if ($res > 0) {
            	return ["code" => 1, "msg" =>$master->sheet_no];
            } else {
            	return ["code" => 0, "msg" => lang("save_error")];
            }
        } else {
        		return ["code" => 0, "msg" => lang("invalid_variable")];
        }
    }
    
	//审核
    public function approve() {
        if (!IS_POST) {
        	return ["code" => 0, "message" => lang("illegal_operate")];
        }
        $sheetno = trim(input("sheetno"));
        if (empty($sheetno)) {
        	return ["code" => 0, "msg" =>lang("invalid_variable")];
        }
		$PcPriceMaster = new PcPriceMaster ();
		$res = $PcPriceMaster->Approve ( $sheetno );
		if (is_array ( $res )) {
			$message ["workDate"] = $res ["work_date"];
			$message ["confirmMan"] = $res ["confirm_man"];
			$message ["msg"] = lang ( "pcprice_check_success" );
			return [ 
					"code" => 1,
					"msg" => $message ["msg"] 
			];
		}
		switch ($res) {
			case - 4 :
				return [ 
						"code" => 0,
						"msg" => lang ( "pcprice_check_fail" ) 
				];
			case - 3 :
				return [ 
						"code" => 0,
						"msg" => lang ( "pcprice_check_fail" ) 
				];
			case - 2 :
				return [ 
						"code" => 0,
						"msg" => lang ( "empty_record" ) 
				];
			case - 1 :
				return [ 
						"code" => 0,
						"msg" => lang ( "pcprice_master_fail" ) 
				];
			case 0 :
				return [ 
						"code" => 0,
						"msg" => lang ( "pcprice_check_not_approve" ) 
				];
			default :
				return [ 
						"code" => 1,
						"msg" => lang ( "pcprice_check_success" ) 
				];
		}
        
    }
    
	//删除调价单
    public function delete(){
        if(!IS_POST){
        	return ["code" => 1, "message" => lang("illegal_operate")];
        }
        $sheetno = input("sheetno");
        if(empty($sheetno)){
        	return ["code" => 0, "msg" => lang("invalid_variable")];
        }
        
		$arr = [ ];
		if (strpos ( $sheetno, "," ) != false) {
			$arr = explode ( ",", $sheetno );
		} else {
			$arr [] = $sheetno;
		}
		
		$num = count ( $arr );
		$succ = 0;
		$msg = [];
		foreach ( $arr as $value ) {
			if (trim ( $value ) == '') {
				continue;
			}
			$PcPriceMaster = new PcPriceMaster ();
			$res = $PcPriceMaster->Del ( $value );
			switch ($res) {
				case - 2 :
					$msg [] = $value . ":".lang("empty_record");
					break;
				case - 1 :
					$msg [] = $value . ":".lang("delete_error");
					break;
				case 0 :
					$msg [] = $value . ":".lang("pcprice_be_approve");
					break;
				default :
					$succ ++;
					break;
			}
		}
       
		if($succ==$num&&$succ>0){
			$code=true;
			$msg=lang("delete_success");
		}else{
			$code=false;
			$msg=implode(",", $msg);
		}
		
        return ['code'=>$code,'msg'=> $msg];
    }
    
    //批量导入
    public function batchprice(){
    	 
    	$Execl_Error = array (
    			"数据导入成功", "找不到文件",
    			"Execl文件格式不正确",
    			"数据导入到产品表时出现部分错误",
    	);
    	 
    	//导入数据库操作
    	if (isset ( $_FILES ['import'] ) && ($_FILES ['import'] ['error'] == 0)) {
    		 
    		$result = $this->use_phpexcel ( $_FILES ["import"] ["tmp_name"] );
    
    		$code=$price=array();
    
    		if ($Execl_Error [$result ["error"]] == 0) {
    			$execl_data = $result ["data"] [0] ["Content"];
    			unset ( $execl_data [1] );
    			 
    			 
    			foreach ( $execl_data as $k => $v ) {
    				 
    				foreach ( $v as $key => $value ) { 						//删除空值，如果不处理会无法插入
    					if ($value == "") {
    						$v [$key] = "";
    					}
    					$v [$key] = trim ( $value );
    				}
    
    				$code[]=$v[0];	//第一列-商品编号
    				$t=array();
    				$t['code']=$v[0];//第二列-商品编号
    				$t['price']=$v[1];//第二列-现进价
    				$t['sale_price']=$v[2];//第三列-现售价
    				$t['vip_price']=$v[3];//第四列-现会员价
    				$t['sup_ly_rate']=$v[4];//第五列-现联营扣率
    				$t['trans_price']=$v[5];//第六列-现配送价
    
    				$price[]=$t;//合并成数组
    
    			}
    			 
    			if(count($price)>0){
    
    				$list=$this->GetItemInfo($code);
    
    				foreach($list as $key=>$value){
    					foreach($price as $pval){
    						if($pval['code']==$value['item_no']){
    								
    							$list[$key]['price1']=$pval['price'];//现进价
    							$list[$key]['sale_price1']=$pval['sale_price'];//现售价
    							$list[$key]['vip_price1']=$pval['vip_price'];//现会员价
    							$list[$key]['sup_ly_rate1']=$pval['sup_ly_rate'];//现联营扣率
    							$list[$key]['trans_price1']=$pval['trans_price'];//现联营扣率
    								
    							break;
    						}
    					}
    				}
    
    			}
    			 
    		}
    
    		$this->assign("code",$code);
    		$this->assign("price",$list);
    
    		$operDate = date('Y-m-d H:i', time());
    		$orderMan = session('loginname');
    		$sheetno = input("sheetno");
    		$approve = "add";
			
    		$branch=new PosBranch();
    		$branchall=$branch->select();
    		$branchlist='';
    		foreach($branchall as $k=>$v){
    			$branchlist.=$v['branch_no'].",";
    		}
    		$branchlist=rtrim($branchlist,',');
    		$pageParams["branchNo"] = 'ALL';
    		$pageParams["branchName"] = '所有门店';
    		$pageParams['branchlist'] = $branchlist;
    		$pageParams['approve'] = $approve;
    		$pageParams['orderMan'] = $orderMan;
    		$this->assign("pageParams", $pageParams);
    		return $this->fetch("pcprice/pxsheet");
    
    	}else{
    		return $this->fetch("pcprice/pximport");
    	}
    	
    }
    
    //获取商品(根据商品编号)
    public function getItemInfo($code){
    	
    	$BaseCode=new BaseCode();
    	$Supcust=new Supcust();
    	$Item_info=new Item_info();
    	
    	$codeStr="'" . implode ( "','", $code ) . "'";
    	$where=" where i.item_no  in( $codeStr )";
    	
    	$fieldsql="i.item_no,i.item_subno,i.item_name,i.item_clsno,bc.code_name as item_brandname," .
    			"i.unit_no,i.item_size,i.product_area,i.price,i.sale_price," .
    			"i.item_size1,i.num2,i.item_stock,i.purchase_spec,b.sp_company as sp_name," .
    			"i.vip_price,i.sup_ly_rate,i.trans_price,i.status," .
    			"case i.sale_price when 0 then 0  when null then 0 when '' then 0 else round( (i.sale_price-i.price)*100 /i.sale_price,2) end as mlv";
    	$joinsql.=" LEFT JOIN " . $BaseCode->tableName() . " as bc on i.item_brand=bc.code_id AND bc.type_no='PP'"
    			. " LEFT JOIN " . $Supcust->tableName() . " as b on i.main_supcust=b.sp_no ";
    	
    	$sql="select ".$fieldsql." from ".$Item_info->tableName()." as i".$joinsql.$where;
    	$list=Db::query($sql);
    	
    	$temp = array();
    	$rowIndex = 1;
    	$colmns = "item_no,item_subno,item_name,item_clsno,item_brandname,unit_no,item_size,product_area,price
    				,sale_price,item_size1,num2,item_stock,purchase_spec,sp_name,vip_price,sup_ly_rate,trans_price";
    	foreach ($list as $k => $v) {
    		$tt = array();
    		foreach ($v as $kk => $vv) {
    			if (in_array($kk, explode(',', $colmns))) {
    				$tt[$kk] = $vv;
    			}
    		}
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
    	return $temp;
    }
}
