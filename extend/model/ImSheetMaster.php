<?php
//im_sheet_master表
namespace model;
use think\Db;
use model\ImSheetDetail;
use model\PosBranchStock;
use model\PmSheetDetail;
use model\PmSheetMaster;
use app\admin\components\Enumerable\ESheetTrans;

class ImSheetMaster extends BaseModel {
    
    protected $pk='sheet_no';
    protected $name="im_sheet_master";

  
    public function Add($model, $details, $operFunc, $transno) {
        $res = $this->Check($model);
        if ($res == 1) {
            try {
                Db::startTrans();
                $iscommit = TRUE;
                $imDetail=new ImSheetDetail();
                $iscommit = $model->save();
                if ($operFunc == "update") {
                	$iscommit = $imDetail->Del($model->sheet_no);
                }
                
                if ($iscommit) {
                    foreach ($details as $k => $v) {
                        $v->sheet_no = $model->sheet_no;
                        if (!empty($v["item_no"])) {
                            if ($imDetail->Add($v) == FALSE) {
                                $iscommit = FALSE;
                                break;
                            }
                        }
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


    private function Check($model) {
        $res = 1;
        if (empty($model->sheet_no)) {
            $res = lang("st_md_empty_sheet_no");
        } else if (empty($model->trans_no)) {
            $res = lang("st_md_empty_class");
        } else if (empty($model->db_no)) {
            $res = lang("st_md_empty_dno");
        } else if (empty($model->branch_no)) {
            $res = lang("st_md_empty_branch");
        } else if (empty($model->d_branch_no) && $model->trans_no == ESheetTrans::MO) {
            $res = lang("st_md_empty_d_branch");
        } else if (empty($model->oper_id)) {
            $res = lang("st_md_empty_oper_id");
        } else if (!is_numeric($model->sheet_amt)) {
            $res = lang("st_md_wrong_amt");
        }
        return $res;
    }

    public function Approve($sheetno, $branch_no, $details) {
        $loginname = session("loginname");
        $model = $this->where("sheet_no='$sheetno'")->find();
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
                    $branchout = $branch_no;//调出仓库
                    $PmSheetDetail=new PmSheetDetail();
                    $PmSheetMaster=new PmSheetMaster();
                    if ($model->save()) {
                        if (!empty($branchout)) {

                            foreach ($details as $v) {
                                if (!empty($v["item_no"]) && intval($v["real_qty"]) != 0) {

                                    $stockModel = new PosBranchStock();
                                    $stock = $stockModel->GetStockByBraItem(trim($branchout), trim($v["item_no"]));
                                    if (empty($stock)) {
                                        Db::rollback();
                                        return "-5001:调出仓库" . $branchout . "，没有 " . $v["item_no"] . " 商品。请添加库存调整单";
                                    } else {
                                        if ($stock->stock_qty >= intval($v["real_qty"])) {

                                            if ($stockModel->UpdateStockBySheetNo($sheetno, $branchout, $v["item_no"], intval($v["real_qty"]), ESheetTrans::MINUS) == FALSE) {
                                                $iscommit = FALSE;
                                                Db::rollback();
                                                break;
                                            }
                                        } else {

                                            Db::rollback();
                                            return $res = "-4:" . $v["item_no"];
                                        }
                                    }
                                }

                                if ($model->voucher_no != NULL && $model->voucher_no != "") {

                                    if ($PmSheetDetail->UpdateOrderQty($model->voucher_no, $v["item_no"], $v["real_qty"]) == FALSE) {
                                        $iscommit = FALSE;
                                        break;
                                    }
                                }
                            }

                            if ($model->voucher_no != NULL && $model->voucher_no != "") {
                                $pmYHSheet =$PmSheetDetail->Get($model->voucher_no);
                                $yhSendOut = $PmSheetDetail->GetSheetSendOut($model->voucher_no);
                                if (count($pmYHSheet) == count($yhSendOut)) {


                                    if ($PmSheetMaster->UpdateOrderStatus($model->voucher_no, 2) == FALSE) {
                                        $iscommit = FALSE;
                                    }
                                } else {

                                    if ($PmSheetMaster->UpdateOrderStatus($model->voucher_no, 1) == FALSE) {
                                        $iscommit = FALSE;
                                    }
                                }
                            }
                            if ($iscommit) {
                                Db::commit();
                                $ary["work_date"] = $model->work_date;
                                $ary["confirm_man"] = $model->confirm_man;
                                return $ary;
                            } else {
                                $res = -1;
                                Db::rollback();
                            }
                        }
                    } else {
                        return $res = -4;
                    }
                } catch (\Exception $e) {

                     Db::rollback();
                }
            }
        }
        return -3;
    }

    /**
     * 返回where 条件查询语句
     */
    protected function GetCondition($start, $end, $sheet_no, $approve_flag, $oper_id, $d_branch_no, $tran_no, $branch_no='')
    {
        $where="1=1";
        if (!empty($start) && !empty($end)) {
            if ($start == $end) {
                $where.=" and date(s.oper_date) = date('$start')";
            } else {
                $where.=" and date(s.oper_date) >= date('$start') and date(s.oper_date) <= date('$end')";
            }
        }
        if (!empty($sheet_no)) {
            $where.=" and s.sheet_no like '%$sheet_no%'";
        }
        if (!empty($approve_flag)) {
            if ($approve_flag == "-1") {
                $where.=" and s.approve_flag =0";
            } else {
                $where.=" and s.approve_flag ='$approve_flag'";
            }
        }
        if (!empty($oper_id)) {
            $where.=" and s.oper_id ='$oper_id'";
        }
        if (!empty($d_branch_no)) {
            $where.=" and s.d_branch_no ='$d_branch_no'";
        }
        if (!empty($branch_no)) {
            $where.=" and s.branch_no ='$branch_no'";
        }
        if (!empty($tran_no)) {
            $where.=" and s.trans_no ='$tran_no'";
        }

        return $where;
    }

    /**
     * 返回符合条件的记录条数
     */
    public function GetCount($where){
        $rowCount=Db::name($this->name)
            ->alias('s')
            ->field("s.sheet_no")
            ->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
            ->join('pos_branch_info c','s.d_branch_no = c.branch_no',"LEFT")
            ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
            ->join('sys_manager e','s.order_man= e.loginname',"LEFT")
            ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
            ->where($where)
            ->count();
        return $rowCount;
    }

    /**
     * @param	调出仓库	$d_branch_no
     * @param	仓库	$branch_no
     */
    public function GetPager($rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $d_branch_no, $tran_no, $branch_no='') {
        
        $offset = ($page - 1) * $rows;
        $order= "s.work_date DESC,s.oper_date DESC";
        
        $where=$this->GetCondition( $start, $end, $sheet_no, $approve_flag, $oper_id, $d_branch_no, $tran_no, $branch_no);
        
        $list=Db::name($this->name)
        ->alias('s')
        ->field("s.sheet_no,case s.order_status when '2' then '全部调入' when '1' then '部分调入' else '未处理' end as order_status,s.branch_no,b.branch_name,s.d_branch_no,c.branch_name as d_branch_name,s.approve_flag," .
                "s.oper_date,s.oper_id,d.oper_name,s.confirm_man,s.sheet_amt,s.memo," .
                "e.username,s.oper_date,f.username as confirm_name,s.work_date")
        ->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        ->join('pos_branch_info c','s.d_branch_no = c.branch_no',"LEFT")
        ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        ->join('sys_manager e','s.order_man= e.loginname',"LEFT")
        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        ->order($order)
        ->where($where)
        ->limit($offset,$rows)
        ->select();

        $rowCount=$this->GetCount($where);
        
        $rowIndex = ($page - 1) * $rows + 1;
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $colmns = "sheet_no,branch_no,order_status,branch_name,d_branch_no,d_branch_name," .
                "oper_date,oper_id,oper_name,confirm_man,sheet_amt,memo,username,oper_date,confirm_name,work_date,approve_flag";
        foreach ($list as $k => $v) {
            $tt = array();
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    $tt[$kk] = $vv;
                }
            }
            $tt["rowIndex"] = $rowIndex;
            $rowIndex++;
            $tt["branch_name"] = $v["branch_name"];
            $tt["d_branch_name"] = $v["d_branch_name"];
            $tt["oper_name"] = $v["oper_name"];
            $tt["oper_day"] = substr($v["oper_date"],0,10);
            $tt["username"] = $v["username"];
            $tt["confirm_name"] = $v["confirm_name"];
            array_push($temp, $tt);
        }
        $result["rows"] = $temp;

        return $result;
    }

    /**
     * 统计数量
     */
    public function staticNum($approve_flag, $oper_id, $d_branch_no, $tran_no, $branch_no){
        $where=$this->GetCondition('','','',$approve_flag, $oper_id, $d_branch_no, $tran_no, $branch_no);
        return $count=$this->GetCount($where);
    }

    public function GetModel($sheet_no) {
    	return $one=$this->where("sheet_no= '$sheet_no'")->find();
    }

    public function Get($sheet_no) {
        
        return $this->alias('s')
			        ->field("s.sheet_no,s.db_no,s.branch_no,b.branch_name,s.order_man,s.d_branch_no,c.branch_name as d_branch_name,s.approve_flag," .
			                "s.oper_date,s.oper_id,d.oper_name,s.confirm_man,s.other1,s.other2,s.sheet_amt,s.memo,e.username,f.username as confirm_name,s.work_date,s.voucher_no,s.voucher_type")
			        ->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
			        ->join('pos_branch_info c','s.d_branch_no = c.branch_no',"LEFT")
			        ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
			        ->join('sys_manager e','s.order_man= e.loginname',"LEFT")
			        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
			        ->where("s.sheet_no= '$sheet_no'")
			        ->find();
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
                    $imDetail=new ImSheetDetail();
                    if ($model->delete() > 0) {
                        $iscommit = $imDetail->Del($sheet_no);
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

        $where="s.trans_no ='MO' and s.order_status <> 2 and s.approve_flag='1'";
        
        if (!empty($sheet_no)) {
        	$where.=" and s.sheet_no like '%$sheet_no%'";
        }
        
        $offset = ($page - 1) * $rows;
        
        $list=Db::name($this->name)
        ->alias('s')
        ->field("s.sheet_no,case s.order_status when '2' then '全部调入' when '1' then '部分调入' else '未处理' end as order_status," .
                " s.branch_no,a.branch_name,s.d_branch_no,b.branch_name as d_branch_name ")
        ->join('pos_branch_info a','s.branch_no = a.branch_no',"LEFT")
        ->join('pos_branch_info b','s.d_branch_no = b.branch_no',"LEFT")
        ->where($where)
        ->limit($offset,$rows)
        ->select();
        
        $rowCount=Db::name($this->name)
        ->alias('s')
        ->field("s.sheet_no,case s.order_status when '2' then '全部调入' when '1' then '部分调入' else '未处理' end as order_status," .
        		" s.branch_no,a.branch_name,s.d_branch_no,b.branch_name as d_branch_name ")
        		->join('pos_branch_info a','s.branch_no = a.branch_no',"LEFT")
        		->join('pos_branch_info b','s.d_branch_no = b.branch_no',"LEFT")
        		->where($where)
        		->count();
        
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $rowIndex = ($page - 1) * $rows + 1;
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
            $tt["rowIndex"] = $rowIndex;
            $rowIndex++;
            array_push($temp, $tt);
        }
        $result["rows"] = $temp;
        return $result;
    }


    public function GetNoneDearMoForPos($start, $end, $sheet_no, $branch_no) {
        
        $where="s.trans_no ='MO' and s.order_status <> 2 and s.approve_flag ='1'";
        
        if (!empty($sheet_no)) {
        	$where.=" and s.sheet_no like '%$sheet_no%'";
        }
        if (!empty($branch_no)) {
        	$where.=" and s.branch_no='$branch_no'";
        }
        if (!(empty($start) && empty($end))) {
        	if (!empty($start) && !empty($end)) {
        		if ($start === $end) {
        			$where.=" and date(s.oper_date) = '$start'";
        		} else {
        			$where.=" and date(s.oper_date) >= '$start' and date(s.oper_date) <= '$end'";
        		}
        	} else {
        		if (!empty($start)) {
        			$where.=" and date(s.oper_date) = '$start'";
        		}
        		if (!empty($end)) {
        			$where.=" and date(s.oper_date) = '$end'";
        		}
        	}
        }
        
        $list=Db::name($this->name)
        ->alias('s')
        ->field("s.sheet_no,case s.order_status when '2' then '全部调入' when '1' then '部分调入' else '未处理' end as order_status," .
                " s.branch_no,a.branch_name,s.d_branch_no,b.branch_name as d_branch_name ,s.oper_date,c.oper_name as oper_name")
        ->join('pos_branch_info a','s.branch_no = a.branch_no',"LEFT")
        ->join('pos_branch_info b','s.d_branch_no = b.branch_no',"LEFT")
        ->join('pos_operator c','s.oper_id=c.oper_id',"LEFT")
        ->where($where)
        ->select();
               
        $result = array();
        foreach ($list as $v) {
            $tt = array();
            $tt["sheet_no"] = $v["sheet_no"];
            $tt["order_status"] = $v["order_status"];
            $tt["branch_no"] = $v["branch_no"];
            $tt["branch_name"] = $v["branch_name"];
            $tt["d_branch_no"] = $v["d_branch_no"];
            $tt["d_branch_name"] = $v["d_branch_name"];
            $tt["oper_date"] = $v["oper_date"];
            $tt["oper_name"] = $v["oper_name"];
            array_push($result, $tt);
        }
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
                $imDetail=new ImSheetDetail();
                if ($iscommit) {
                    if ($model->save()) {
                        if ($flag == 1) {
                            $iscommit =$imDetail->DeleteMi($model->sheet_no);
                        }
                        if ($iscommit) {
                            foreach ($details as $k => $v) {
                                $v->sheet_no = $model->sheet_no;
                                if ($imDetail->AddMi($v) == FALSE) {
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
                    $res = 0;
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

	
    public function CheckMoNo($sheet_no, $esheet_no) {
        if (empty($esheet_no)) {
            $model = $this->where("voucher_no='$sheet_no' and approve_flag='0'")->find();
        } else {
            $model = $this->where("voucher_no='$sheet_no' and approve_flag='0' and sheet_no <> '$esheet_no'")->find();
        }
        if (!empty($model)) {
            return false;
        } else {
        	return true;
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
                            	$imDetail=new ImSheetDetail();
                                $iscommit =$imDetail->DeleteMi($sheet_no);
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
                	$imDetail=new ImSheetDetail();
                    $iscommit =$imDetail->UpdateMi($sheet_no, $model->voucher_no, $model->d_branch_no);
                    if ($iscommit) {
                        if (!empty($model->voucher_no)) {
                            $model_mo = $this->where("sheet_no='{$model->voucher_no}'")->find();
                            $other1 = $imDetail->GetMoStatus($model->voucher_no);
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
                        return 0;
                    }
                } catch (\Exception $ex) {
                    return -4;
                }
            }
        }
    }

    public function GetMISheetForPos($moSheetno) {
        return $this->where("voucher_no='$moSheetno' and order_status=0 and approve_flag='0'")->find();
    }

}
