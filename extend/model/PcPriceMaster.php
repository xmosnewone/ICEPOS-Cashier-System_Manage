<?php
//pc_price_flow_master表
namespace model;
use think\Db;
use model\PcPriceDetail;
use model\PcBranchPriceBreakpoint;
use model\ImSheetDetail;
use model\Item_info;
use model\PcBranchPrice;
use app\admin\components\Enumerable\EOperStatus;

class PcPriceMaster extends BaseModel {

    public $branch_name;
    public $oper_name;
    public $username;
    public $confirm_name;
    
    protected $pk='sheet_no';
    protected $name="pc_price_flow_master";

    public function Add($model, $details, $operFunc, $transno) {
        $res = $this->Check($model);
        if ($res == 1) {
            try {
                Db::startTrans();
                $PcPriceDetail=new PcPriceDetail();
                $iscommit = TRUE;
                if ($operFunc == "update") {
                    $iscommit = $PcPriceDetail->Del($model->sheet_no);
                }
                if ($operFunc == "update" ?$model->save($model, ["sheet_no" => $model->sheet_no]) : $model->save()) {
                    if ($iscommit) {
                        foreach ($details as $k => $v) {
                            $v->sheet_no = $model->sheet_no;
                            if ($v->item_no != '') {
                                if ($PcPriceDetail->Add($v) == FALSE) {
                                    $iscommit = FALSE;
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    $iscommit = FALSE;
                }

                if ($iscommit) {
                    $res = 1;
                    Db::commit();
                } else {
                    $res = -1;
                    Db::rollback();
                }
            } catch (\Exception $ex) {
                $res = -2;
                Db::rollback();
            }
        } else {
            $res = 0;
        }
        return $res;
    }


    private function Check($model) {
        $res = 1;
        if (empty($model->sheet_no)) {
            $res = lang("pcprice_sheetno_empty");
        } else if (empty($model->trans_no)) {
            $res = lang("pcprice_sheetnocls_empty");
        }
        return $res;
    }

	//审核订单
    public function Approve($sheetno) {
        $loginname = session("loginname");
        $model = $this->where("sheet_no='$sheetno'")->find();
        $order = $model;
        $time=time();
        if (empty($model)) {
            return -2;
        } else {
            if (intval($model->approve_flag) != 0) {
                return 0;
            } else {
                try {
                    Db::startTrans();
                    $model->approve_flag = '1';
                    $model->work_date = date(DATETIME_FORMAT, time());
                    $model->confirm_man = $loginname;
                    $iscommit = true;
                    $PcBranchPrice=new PcBranchPrice();
                    if ($model->save()) {
                    	$PcPriceDetail=new PcPriceDetail();
                        $details = $PcPriceDetail->where("sheet_no='$sheetno'")->select();
                        $prefix=$this->prefix;
                        foreach ($details as $k => $v) {
                            if ($v["item_no"] != '') {
                            	
                                $operation='';
                                if($order['branch_no'] == 'ALL'){
                                	$num = $PcBranchPrice->where("item_no='{$v["item_no"]}'")->count();
                                }else{
                                	$branchlist = $order['branchlist'];
                                	$branchlistarr = explode(",", $branchlist);
                                	
                                	$brachlistnew=simplode($branchlistarr);
                                	$num = $PcBranchPrice->where("item_no='{$v["item_no"]}' and `branch_no` in (" . $brachlistnew . ")")->count();
                                }
                                
                                if ($num > 0) {
                                	
                                	//修改
                                    if ($order['branch_no'] == 'ALL') {

                                        $sql = "update ".$prefix."pc_branch_price set "
                                                . "price='" . $v['new_price'] . "',"
                                                . "`sale_price`='" . $v['new_price1'] . "',"
                                                . "`vip_price`='" . $v['new_price2'] . "',"
                                                . "`sup_ly_rate`='" . $v['new_price3'] . "',"
                                                . "`oper_date`='" . date("Y-m-d H:i:s", $time) . "'"
                                                . " where `item_no`='" . $v['item_no'] . "'";
                                    } else {
                                       

                                        
                                        $sql = "update ".$prefix."pc_branch_price set "
                                                . "price='" . $v['new_price'] . "',"
                                                . "`sale_price`='" . $v['new_price1'] . "',"
                                                . "`vip_price`='" . $v['new_price2'] . "',"
                                                . "`sup_ly_rate`='" . $v['new_price3'] . "',"
                                                . "`oper_date`='" . date("Y-m-d H:i:s", $time) . "'"
                                                . " where `item_no`='" . $v['item_no'] . "' and `branch_no` in (" . $brachlistnew . ")";
                                    }
                                    
                                    $operation=EOperStatus::UPDATE;//修改操作

                                }else{
                                	//新增记录
                                	//@hj 新增添加调价记录，否则门店POS系统没有商品调价的记录，价格也不会变
                                	if ($order['branch_no'] == 'ALL') {
                                	
                                		$sql = "insert into ".$prefix."pc_branch_price (`branch_no`,`item_no`,`price`,`sale_price`,`vip_price`,`sup_ly_rate`,`oper_date`) 
                                				values (
                                				    '" . 'ALL'. "',
                                					'" . $v['item_no'] . "',
                                					'" . $v['new_price'] . "',
                                					'" . $v['new_price1'] . "',
                                					'" . $v['new_price2'] . "',
                                					'" . $v['new_price3'] . "',
                                					'" . date("Y-m-d H:i:s", $time). "'
                                				)";
                                	} else {
                                		 
                                		$branchlist = $order['branchlist'];
                                		$branchlistarr = explode(",", $branchlist);
                                		$sql = "insert into ".$prefix."pc_branch_price (`branch_no`,`item_no`,`price`,`sale_price`,`vip_price`,`sup_ly_rate`,`oper_date`) values ";
                                		foreach ($branchlistarr as $kk => $vv) {
                                			
                                			if(trim($vv)==''){
                                				continue;
                                			}
                                			
                                			if($kk>0){
                                				$sql.=",";
                                			}
                                			$sql.="(
                                					'" . $vv. "',
                                					'" .  $v['item_no']. "',
                                					'" . $v['new_price'] . "',
                                					'" . $v['new_price1'] . "',
                                					'" . $v['new_price2'] . "',
                                					'" . $v['new_price3'] . "',
                                					'" . date("Y-m-d H:i:s", $time). "'
                                					)";
                                		}
                                	}
                                	
                                	$operation=EOperStatus::ADD;//添加操作
                                }
                         
                                Db::execute($sql);
                                                       
                                if ($order["branch_no"] == "ALL") {
                                	$bdBreakPoint = new PcBranchPriceBreakpoint();
                                	$bdBreakPoint->rtype = $operation;
                                	$bdBreakPoint->item_no = $v["item_no"];
                                	$bdBreakPoint->branch_no = $order["branch_no"];
                                	$bdBreakPoint->updatetime = date(DATETIME_FORMAT);
                                	$bdBreakPoint->save();
                                } else {
                                	$arr = explode(",", $branchlist);
                                	foreach ($arr as $vv) {
                                		if(trim($vv)==''){
                                			continue;
                                		}
                                		$bdBreakPoint = new PcBranchPriceBreakpoint();
                                		$bdBreakPoint->rtype =$operation;
                                		$bdBreakPoint->item_no = $v["item_no"];
                                		$bdBreakPoint->branch_no = str_replace("'", "", $vv);
                                		$bdBreakPoint->updatetime = date(DATETIME_FORMAT);
                                		$bdBreakPoint->save();
                                	}
                                }
                                
                                //全部分店-统一修改商品价格
                                if ($order['branch_no'] == 'ALL') {
                                    $arr = array();

                                    if ($v['old_price'] != $v['new_price']) {
                                        $arr['price'] = $v['new_price'];
                                    }
                                    if ($v['old_price1'] != $v['new_price1']) {
                                        $arr['sale_price'] = $v['new_price1'];
                                    }
                                    if ($v['old_price2'] != $v['new_price2']) {
                                        $arr['vip_price'] = $v['new_price2'];
                                    }
                                    if ($v['old_price3'] != $v['new_price3']) {
                                        $arr['sup_ly_rate'] = $v['new_price3'];
                                    }
                                    if ($v['old_price4'] != $v['new_price4']) {
                                        $arr['trans_price'] = $v['new_price4'];
                                    }
                                    if (count($arr) > 0) {
                                    	$Item_info=new Item_info();
                                        $models = $Item_info->GetOne($v['item_no']);
                                        if ($models->save($arr)) {
                                            $iscommit = TRUE;
                                        } else {
                                            $iscommit = FALSE;
                                        }
                                    }
                                }
                            } else {
                                $iscommit = FALSE;
                            }
                        }//end of foreach ($details as $k => $v)
                        
                        if ($iscommit) {
                            Db::commit();
                            $ary["work_date"] = $model->work_date;
                            $ary["confirm_man"] = $model->confirm_man;
                            return $ary;
                        } else {
                            Db::rollback();
                            return -1;
                        }
                    } else {
                        	return -4;
                    }
                } catch (\Exception $e) {
                	//var_dump($e);
                    Db::rollback();
                }
            }
        }
        return -3;
    }


    public function GetPager($rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id) {
    	$where="1=1";
    	if (!empty($start) && !empty($end)) {
    		if ($start == $end) {
    			$where.=" and date(s.oper_date) = date('$start')";
    		} else {
    			$where.=" and date(s.oper_date)>=date('$start') and date(s.oper_date)<=date('$end')";
    		}
    	}
    	if (!empty($sheet_no)) {
    		$where.=" and s.sheet_no like '%$sheet_no%'";
    	}
    	if (!empty($approve_flag)) {
    		if ($approve_flag == "-1") {
    			$approve_flag=0;
    		}
    		$where.=" and s.approve_flag = '$approve_flag'";
    	}
    	if (!empty($oper_id)) {
    		$where.=" and s.oper_id = '$oper_id'";
    	}
    	
    	$offset = ($page - 1) * $rows;
    	
        $list=Db::name($this->name)
        ->alias('s')
        ->field("s.sheet_no"
                . ",s.branch_no"
                . ",s.approve_flag,"
                . "s.oper_date,"
                . "s.oper_id,"
                . "s.confirm_man,"
                . "s.oper_date")
        ->join('sys_manager d','s.oper_id = d.loginname',"LEFT")
        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        ->order("s.oper_date DESC")
        ->limit($offset,$rows)
        ->where($where)
        ->select();
        
        $rowCount=Db::name($this->name)
        ->alias('s')
        ->join('sys_manager d','s.oper_id = d.loginname',"LEFT")
        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        ->where($where)
        ->count();
        
        $rowIndex = ($page - 1) * $rows + 1;
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $colmns = "sheet_no,branch_no,order_status,branch_name," .
                "oper_date,sheet_amt,memo,oper_date,work_date,approve_flag,oper_name";
        foreach ($list as $k => $v) {
            $tt = array();
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    $tt[$kk] = $vv;
                }
            }
            $tt["rowIndex"] = $rowIndex;
            $tt["branch_name"] = $v["branch_name"];
            $tt["branch_no"] = $v["branch_no"];
            $tt["oper_name"] = $v["oper_id"];
            $tt["username"] = $v["username"];
            $tt["confirm_name"] = $v["username"];
            array_push($temp, $tt);
            $rowIndex++;
        }
        $result["rows"] = $temp;

        return $result;
    }


    public function Get($sheet_no) {
       
        $where="s.sheet_no='$sheet_no'";
        $one=Db::name($this->name)
        ->alias('s')
        ->field("s.sheet_no,s.branch_no,b.branch_name,s.approve_flag," .
                "s.oper_date,s.branchlist,s.oper_id,d.oper_name,s.confirm_man,s.other1,s.other2,s.memo,f.username as confirm_name,s.work_date")
        ->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        ->where($where)
        ->find();
        return	$one;
    }


    public function Del($sheet_no) {
        $model = $this->where("sheet_no='$sheet_no'")->find();
        if (empty($model)) {
            return -2;
        } else {
            if (intval($model->approve_flag) != 0) {
                return 0;
            } else {
                try {
                	Db::startTrans();
                    $iscommit = TRUE;
                    if ($model->delete() > 0) {
                    	$PcPriceDetail=new PcPriceDetail();
                        $iscommit = $PcPriceDetail->Del($sheet_no);
                    } else {
                        $iscommit = FALSE;
                    }
                    if ($iscommit == TRUE) {
                        Db::commit();
                        return 1;
                    } else {
                        Db::rollback();
                        return -1;
                    }
                } catch (\Exception $ex) {
                    return -1;
                }
            }
        }
    }


    public function GetNoneDearMo($sheet_no, $rows, $page) {
        
    	$lang_in=lang("pcprice_md_all_in");
    	$lang_part_in=lang("pcprice_md_part_in");
    	$lang_unhandle=lang("pcprice_md_unhandle");
    	
        $offset = ($page - 1) * $rows;
        
        $where="s.trans_no ='MO' and s.order_status <> 2 and s.approve_flag = 1";
        if (!empty($sheet_no)) {
        	$where.=" and s.sheet_no like '%$sheet_no%'";
        }
        
        $list=Db::name($this->name)
        ->alias('s')
        ->field("sheet_no,case s.order_status when '2' then '$lang_in' when '1' then '$lang_part_in' else '$lang_unhandle' end as order_status," .
                " s.branch_no,a.branch_name")
        ->join('pos_branch_info a','s.branch_no = a.branch_no',"LEFT")
        ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        ->limit($offset,$rows)
        ->where($where)
        ->select();
        
        $rowCount=Db::name($this->name)
        ->alias('s')
        ->join('pos_branch_info a','s.branch_no = a.branch_no',"LEFT")
        ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        ->where($where)
        ->count();
        
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $colmns = "sheet_no,order_status,branch_no,branch_name,d_branch_no,d_branch_name";
        foreach ($list as $k => $v) {
            $tt = array();
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    $tt[$kk] = $vv;
                }
            }
            $tt["branch_name"] = $v["branch_name"];
            $tt["d_branch_name"] = $v["d_branch_name"];
            $tt["order_status"] = $v["order_status"];
            array_push($temp, $tt);
        }
        $result["rows"] = $temp;
        return $result;
    }

    public function SetMIStatus($mono, $mino, $flag) {

        $model = $this->where("sheet_no='$mono'")->find();
        if ($flag == "edit") {
            $models = $this->where("voucher_no ='$mono' and sheet_no <> '$mino'")->select();
            if (count($models) == 0) {
                $model->order_status = 0;
            } else {
                $model->order_status = 1;
            }
        } else {
            $model->order_status = 1;
        }
        if ($model->save()) {
            return TRUE;
        } else {
            return FALSE;
        }
    }


    public function AddMi($model, $details, $flag) {
        $res = $this->Check($model);
        if ($res == 1) {
            try {
                Db::startTrans();
                $model->oper_date = date(DATETIME_FORMAT, time());
                $iscommit = TRUE;
                if ($flag == 1) {
                    if ($iscommit) {
                        $temp = $this->where("sheet_no='{$model->sheet_no}'")->find();
                        if (!empty($temp->voucher_no)) {
                            $iscommit = $this->SetMIStatus($temp->voucher_no, $model->sheet_no, "edit");
                        }
                    }
                }
                if ($iscommit) {
                    if ($model->save()) {
                        if ($flag == 1) {
                        	$ImSheetDetail=new ImSheetDetail();
                            $iscommit = $ImSheetDetail->DeleteMi($model->sheet_no);
                        }
                        if ($iscommit) {
                            foreach ($details as $k => $v) {
                                $v->sheet_no = $model->sheet_no;
                                if ($ImSheetDetail->AddMi($v) == FALSE) {
                                    $iscommit = FALSE;
                                    break;
                                }
                            }
                        }
                        if (!empty($model->voucher_no)) {
                            $iscommit = $this->SetMIStatus($model->voucher_no, $model->sheet_no, "add");
                        }
                    } else {
                        $iscommit = FALSE;
                    }
                }
                if ($iscommit) {
                    $res = 1;
                    Db::commit();
                } else {
                    $res = -1;
                    Db::rollback();
                }
            } catch (\Exception $ex) {
                $res = -2;
                Db::rollback();
            }
        } else {
            $res = 0;
        }
        return $res;
    }

    public function CheckMoNo($sheet_no) {
        $model = $this->where("voucher_no='$sheet_no' and approve_flag='0'")->select();
        if (empty($model)) {
            return TRUE;
        } else {
            RETURN FALSE;
        }
    }

    public function DeleteMi($sheet_no) {
        $model = $this->where("sheet_no='$sheet_no'")->find();
        if (empty($model)) {
            return -2;
        } else {
            if (intval($model->approve_flag) != 0) {
                return 0;
            } else {
                try {
                    Db::startTrans();
                    $iscommit = TRUE;
                    if (!empty($model->voucher_no)) {
                        $iscommit = $this->SetMIStatus($model->voucher_no, $sheet_no, "edit");
                        if ($iscommit === TRUE) {
                            if ($model->delete() > 0) {
                            	$ImSheetDetail=new ImSheetDetail();
                                $iscommit = $ImSheetDetail->DeleteMi($sheet_no);
                            } else {
                                $iscommit = FALSE;
                            }
                        }
                    }
                    if ($iscommit === TRUE) {
                        Db::commit();
                        return 1;
                    } else {
                        Db::rollback();
                        return -1;
                    }
                } catch (\Exception $ex) {
                    return -1;
                }
            }
        }
    }


    public function ApproveMi($sheet_no, $confirm_man) {
        $model = $this->where("sheet_no='$sheet_no'")->find();
        if (empty($model)) {
            return -2;
        } else {
            if (intval($model->approve_flag) == 1) {
                return -3;
            } else {
                $iscommit = TRUE;
                Db::startTrans();
                try {
                	$ImSheetDetail=new ImSheetDetail();
                    $iscommit = $ImSheetDetail->UpdateMi($sheet_no, $model->voucher_no, $model->d_branch_no);
                    if ($iscommit) {
                        if (!empty($model->voucher_no)) {
                            $model_mo = $this->where("sheet_no='{$model->voucher_no}'")->find();
                            $other1 = $ImSheetDetail->GetMoStatus($model->voucher_no);
                            $model_mo->order_status = $other1;
                            $iscommit = $model_mo->save();
                            if ($iscommit) {

                                $model->approve_flag = '1';
                                $model->confirm_man = $confirm_man;
                                $model->work_date = date(DATETIME_FORMAT, time());
                                $iscommit = $model->save();
                            }
                        } else {
                            $model->approve_flag = '1';
                            $model->confirm_man = $confirm_man;
                            $model->work_date = date(DATETIME_FORMAT, time());
                            $iscommit = $model->save();
                        }
                    }
                    if ($iscommit) {
                        Db::commit();
                        return 1;
                    } else {
                        Db::rollback();
                        return -1;
                    }
                } catch (\Exception $ex) {
                    return -4;
                }
            }
        }
    }

}
