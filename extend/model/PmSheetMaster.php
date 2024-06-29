<?php
//pm_sheet_master	采购订单主表
namespace model;
use think\Db;
use model\PmSheetDetail;
use model\ImSheetDetail;
use app\admin\components\Enumerable\ESheetStatus;
use app\admin\components\Enumerable\ESheetTrans;

class PmSheetMaster extends BaseModel {

    protected $pk='sheet_no';
    protected $name="pm_sheet_master";
    
    public $rowIndex;
    public $approve_name;

    public function Add($model, $details, $operFunc) {
        $res = $this->Check($model);
        if ($res == 1) {
            try {
                Db::startTrans();
                $iscommit = TRUE;
                if ($operFunc == "update") {
                	$PmSheetDetail=new PmSheetDetail();
                    $iscommit = $PmSheetDetail->Del($model->sheet_no);
                }
                if ($model->save()) {
                    if ($iscommit) {
                    	$ImSheetDetail=new ImSheetDetail();
                        foreach ($details as $k => $v) {
                            $v->sheet_no = $model->sheet_no;
                            if (!empty($v["item_no"])) {
                                if ($ImSheetDetail->Add($v) == FALSE) {
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
            $res = lang("po_empty_sheetno");
        } else if (empty($model->trans_no)) {
            $res = lang("po_empty_type");
        } else if (empty($model->db_no)) {
            $res =	lang("po_empty_dbno");
        } else if (empty($model->branch_no)) {
            $res = lang("po_empty_branchno");
        } else if (empty($model->oper_id)) {
            $res = lang("po_empty_oper");
        } else if (!is_numeric($model->sheet_amt)) {
            $res = lang("po_error_amount");
        }
        return $res;
    }


    public function Get($sheet_no) {
        
        return $one=$this->alias('s')
        ->field("s.sheet_no,s.branch_no,b.branch_name,s.order_man,s.d_branch_no,c.branch_name as d_branch_name,s.approve_flag," .
                "s.oper_date,s.valid_date,s.order_status,s.oper_id,d.oper_name,s.confirm_man,s.sheet_amt,s.memo,e.username,f.username as confirm_name,s.work_date")
        ->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        ->join('pos_branch_info c','s.d_branch_no = c.branch_no',"LEFT")
        ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        ->join('sys_manager e','s.order_man= e.loginname',"LEFT")
        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        ->where("s.sheet_no= '$sheet_no'")
        ->find();
       
    }


    public function GetPager($rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $d_branch_no, $tran_no) {
       
    	$lang_in=lang("po_md_all_in");
    	$lang_part=lang("po_md_part_in");
    	$lang_unhandle=lang("po_md_unhandle");
    	
        $where="1=1";
        if (!empty($start) && !empty($end)) {
            if ($start == $end) {
                $where.=" and date(s.oper_date) = date('$start')";
            } else {
                $where.=" and date(s.oper_date) >= date('$start') and date(s.oper_date) <= date('$end')";
            }
        }
        $where.=" and s.order_status <> 't'";
        if (!empty($sheet_no)) {
            $where.=" and s.sheet_no like '%$sheet_no%'";
        }
        if (!empty($approve_flag)) {
            if ($approve_flag == "-1") {
                $approve_flag=0;
            }
            $where.=" and s.approve_flag ='$approve_flag'";
        }
        if (!empty($oper_id)) {
            $where.=" and s.oper_id='$oper_id'";
        }
        if (!empty($d_branch_no)) {
            $where.=" and s.branch_no='$d_branch_no'";
        }
        if (!empty($tran_no)) {
            $where.=" and s.trans_no='$tran_no'";
        }
       
        $order = "s.work_date DESC";
       
        $rowCount=Db::name($this->name)
        ->alias('s')
        ->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        ->join('pos_branch_info c','s.d_branch_no = c.branch_no',"LEFT")
        ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        ->join('sys_manager e','s.order_man= e.loginname',"LEFT")
        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        ->where($where)
        ->count();
        
        $offset = ($page - 1) * $rows;
        
        $list=Db::name($this->name)
        ->alias('s')
        ->field("s.sheet_no,case s.order_status when '2' then '$lang_in' when '1' then '$lang_part' else '$lang_unhandle' end as order_status"
        		.",s.branch_no,b.branch_name,s.d_branch_no,c.branch_name as d_branch_name,s.approve_flag," .
                "s.oper_date,s.oper_id,d.oper_name,s.confirm_man,s.sheet_amt,s.memo," .
                "e.username,s.oper_date,f.username as confirm_name,s.work_date")
        ->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        ->join('pos_branch_info c','s.d_branch_no = c.branch_no',"LEFT")
        ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        ->join('sys_manager e','s.order_man= e.loginname',"LEFT")
        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        ->where($where)
        ->limit($offset,$rows)
        ->order($order)
        ->select();
        
        
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $rowIndex = ($page - 1) * $rows + 1;
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
            $tt["branch_name"] = $v["branch_name"];
            $tt["d_branch_name"] = $v["d_branch_name"];
            $tt["oper_name"] = $v["oper_name"];
            $tt["username"] = $v["username"];
            $tt["confirm_name"] = $v["confirm_name"];
            array_push($temp, $tt);
            $rowIndex++;
        }
        $result["rows"] = $temp;

        return $result;
    }

	//审核采购单
    public function Approve($sheetno) {
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
                    if ($model->save()) {
                        if ($iscommit) {
                            Db::commit();
                            $ary["work_date"] = $model->work_date;
                            $ary["confirm_man"] = $model->confirm_man;
                            return $ary;
                        } else {
                            $res = -1;
                            Db::rollback();
                        }
                    } else {
                        return $res = -4;
                    }
                } catch (\Exception $e) {
              		echo $e;
                     Db::rollback();
                }
            }
        }
        return -3;
    }


    public function Del($sheet_no) {
        $model = $this->where("sheet_no='$sheet_no'")->find();
        if (empty($model)) {
            return -1;
        } else {
            if (intval($model->order_status) != 0 || intval($model->approve_flag) != 0) {
                return -3;
            } else {
                try {
                    Db::startTrans();
                    $iscommit = TRUE;
                    if ($model->delete() > 0) {
                    	$PmSheetDetail=new PmSheetDetail();
                        $iscommit = $PmSheetDetail->Del($sheet_no);
                    } else {
                        $iscommit = FALSE;
                    }
                    if ($iscommit == TRUE) {
                        Db::commit();
                        return 1;
                    } else {
                        Db::rollback();
                        return 0;
                    }
                } catch (\Exception $ex) {
                    return -2;
                }
            }
        }
    }


    public function UpdateOrderStatus($sheetno, $orderStatus) {
        if (!empty($sheetno) && !empty($orderStatus)) {
            $model = $this->where("sheet_no='$sheetno'")->find();
            $model->order_status = $orderStatus;
            return $model->save();
        }
    }


    public function Zhongzhi($sheet_no) {
        $model = $this->where("sheet_no='$sheet_no'")->find();
        if (empty($model)) {
            reurn - 2;
        } else {
            if ($model->approve_flag != '1' || $model->order_status == '4') {
                return 0;
            }
            $model->order_status = ESheetStatus::CLOSE;
            if ($model->save()) {
                return 1;
            } else {
                return -1;
            }
        }
    }


    public function SaveSheet($model, $details, $addflag) {
        $check = $this->CheckModel($model);
        $result = 1;
        if ($check == TRUE) {
            Db::startTrans();
            $res = 1;
            try {
            	
                if ($addflag == "edit") {
                    if ($model->approve_flag == '1') {
                        $result = -1;
                        Db::rollback();
                        return $result;
                    }
                    $res = $this->BeginDelete($model);
                }
               
                if ($res == 1) {
                    $model->oper_date = date(DATETIME_FORMAT, time());
                    if ($model->save()) {
                    	$PmSheetDetail=new PmSheetDetail();
                        foreach ($details as $k => $v) {
                            $v->sheet_no = $model->sheet_no;
                            $res = $PmSheetDetail->SaveDetails($v);
                            if ($res != 1) {
                                break;
                            }
                        }
                    } else {
                        $res = 0;
                    }
                }
                if ($res == 1) {
                    if ($model->trans_no == "PI") {
                        $res = $this->UpdateStatus($model->voucher_no);
                    }
                }
                if ($res == 1) {
                    $result = 1;
                    Db::commit();
                } else {
                    $result = $res;
                    Db::rollback();
                }
            } catch (\Exception $ex) {
               	Db::rollback();
                $result = -2;
            }
        } else {
            $result = -1;
        }
        return $result;
    }


    public function GetSheet($sheet_no) {
        
        return $one=$this->alias('s')
        ->field("s.sheet_no,s.branch_no,a.branch_name,s.voucher_no,s.supcust_no,b.sp_company as sp_name,s.order_man,d.oper_name,s.oper_id,c.username," .
                "s.confirm_man,e.username as confirm_name,s.oper_date,s.work_date,s.valid_date,s.pay_date,s.memo,s.approve_flag,s.order_status,s.linkman,s.telephone,s.address")
        ->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        ->join('sp_infos b','s.supcust_no=b.sp_no',"LEFT")
        ->join('sys_manager c','s.order_man= c.loginname',"LEFT")
        ->join('pos_operator d','s.oper_id= d.oper_id',"LEFT")
       	->join('sys_manager e','s.confirm_man= e.loginname',"LEFT")
        ->where("s.sheet_no= '$sheet_no'")
        ->find();
    }
    
    //单个获取数据
    public function GetInstance($sheet_no) {
    	return $this->where("sheet_no= '$sheet_no'")->find();
    }

    //080 收货明细报表用到
    public function GetSheetPager($rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $supcust_no, $order_status, $sheet_amt, $trans_no,$branch_no='',$limit=true) {
       
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
                $approve_flag = '0';
            } 
            $where.=" and s.approve_flag ='$approve_flag'";
        }
        if (!empty($oper_id)) {
            $where.=" and s.oper_id ='$oper_id'";
        }
        if (!empty($supcust_no)) {
            $where.=" and s.supcust_no ='$supcust_no'";
        }
        if (!empty($order_status)) {
            if ($order_status == "-1") {
                $where.=" and s.order_status ='0'";
            } else {
                $order_status = str_replace("-1", '0', $order_status);
                $where.=" and s.order_status in ($order_status)";
            }
        }
        if (!empty($sheet_amt)) {
            $where.=" and s.sheet_amt >= $sheet_amt";
        }
        if (!empty($trans_no)) {
        	$where.=" and s.trans_no = '$trans_no'";
        }
    	if (!empty($branch_no)) {
        	$where.=" and s.branch_no = '$branch_no'";
        }
        
        $order = "s.oper_date desc";
        $offset = ($page - 1) * $rows;
        
        //分页时才统计数量
        if($limit){
        	$rowCount=Db::name($this->name)
        	->alias('s')
        	->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        	->join('sp_infos c','s.supcust_no = c.sp_no',"LEFT")
        	->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        	->join('sys_manager e','s.order_man= e.loginname',"LEFT")
        	->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        	->where($where)
        	->count();
        }
        
        $lang_part=lang("pi_md_part_handle");
        $lang_all=lang("pi_md_all_handle");
        $lang_stop=lang("pi_md_stop");
        $lang_un=lang("pi_md_un_handle");
        
        if($limit){
        	$list=Db::name($this->name)
        	->alias('s')
        	->field("s.sheet_no,s.branch_no,b.branch_name,s.approve_flag,s.oper_date,case s.order_status when '1' then '$lang_part' when '2' then '$lang_all' when '4' then '$lang_stop' else '$lang_un' end as order_status," .
        			"s.oper_id,d.oper_name,s.sheet_amt,s.supcust_no,c.sp_company as sp_name," .
        			"f.username as confirm_name")
        			->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        			->join('sp_infos c','s.supcust_no = c.sp_no',"LEFT")
        			->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        			->join('sys_manager e','s.order_man= e.loginname',"LEFT")
        			->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        			->where($where)
        			->limit($offset,$rows)
        			->order($order)
        			->select();
        }else{
        	
        	$list=Db::name($this->name)
        	->alias('s')
        	->field("s.sheet_no,s.branch_no,b.branch_name,s.approve_flag,s.oper_date,case s.order_status when '1' then '$lang_part' when '2' then '$lang_all' when '4' then '$lang_stop' else '$lang_un' end as order_status," .
        			"s.oper_id,d.oper_name,s.sheet_amt,s.supcust_no,c.sp_company as sp_name," .
        			"f.username as confirm_name")
        			->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        			->join('sp_infos c','s.supcust_no = c.sp_no',"LEFT")
        			->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        			->join('sys_manager e','s.order_man= e.loginname',"LEFT")
        			->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        			->where($where)
        			->order($order)
        			->select();
        	$rowCount=count($list);
        }
        
        
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $rowIndex = 1;
        foreach ($list as $k => $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["sheet_no"] = $v["sheet_no"];
            $tt["order_status"] = $v["order_status"];
            $tt["sheet_amt"] = $v["sheet_amt"];
            $tt["approve_flag"] = $v["approve_flag"];
            $tt["oper_date"] = $v["oper_date"];
            $tt["branch_name"] = $v["branch_name"];
            $tt["oper_name"] = $v["oper_name"];
            $tt["username"] = $v["username"];
            $tt["sp_name"] = $v["sp_name"];
            $tt["confirm_name"] = $v["confirm_name"];
            $tt["branch_no"] = $v["branch_no"];
            $tt["oper_id"] = $v["oper_id"];
            $tt["supcust_no"] = $v["supcust_no"];
            array_push($temp, $tt);
            $rowIndex++;
        }
        $result["rows"] = $temp;
        return $result;
    }

    public function DeleteSheet($sheet_no) {
        $model = $this->where("sheet_no='$sheet_no'")->find();
        $result = 1;
        if (empty($model)) {
            $result = -1;
        } else {
            if (intval($model->approve_flag) == 1) {
                $result = -3;
            } else {
                Db::startTrans();
                try {
                    $res = $this->BeginDelete($model);
                    if ($res == 1) {
                        if ($model->delete()) {
                            $res = 1;
                        } else {
                            $res = 0;
                        }
                    }
                    if ($res == 1) {
                        $result = $res;
                        Db::commit();
                    } else {
                        $result = $res;
                        Db::rollback();
                    }
                } catch (\Exception $ex) {
                    $result = -2;
                    Db::rollback();
                }
            }
        }
        return $result;
    }


    public function ApproveSheet($sheet_no, $confirm_man) {
        $model = $this->where("sheet_no='$sheet_no'")->find();
        $result = 1;
        if (empty($model)) {
            $result = -1;
        } else {
            if (intval($model->approve_flag) == 1) {
                $result = -3;
            } else {
                Db::startTrans();
                $res = 1;
                try {
                    if ($model->trans_no == ESheetTrans::PI) {
						$PmSheetDetail=new PmSheetDetail();
                        $res = $PmSheetDetail->UpdateDetail($model->sheet_no, $model->voucher_no, $model->branch_no, $model->supcust_no);
                        if ($res == 1) {
                            $res = $this->UpdateStatus($model->voucher_no);
                        }
                    }
                    if ($res == 1) {
                        $model->confirm_man = $confirm_man;
                        $model->work_date = date(DATETIME_FORMAT, time());
                        $model->approve_flag = '1';
                        if ($model->save()) {
                            $res = 1;
                        } else {
                            $res = 0;
                        }
                    }
                    if ($res == 1) {
                        Db::commit();
                    } else {
                        Db::rollback();
                    }
                    $result = $res;
                } catch (\Exception $ex) {
                    Db::rollback();
                    $result = -2;
                }
            }
        }
        return $result;
    }


    public function CanDear($sheet_no) {
        $result = 1;
        $records = $this->where("voucher_no='$sheet_no' and approve_flag='0'")->find();
        if (empty($records)) {
            $result = 1;
        } else {
            $result = -1;
        }
        return $result;
    }


    private function BeginDelete($model) {
    	$PmSheetDetail=new PmSheetDetail();
        $res = $PmSheetDetail->DeleteDetail($model->sheet_no);
        if ($res == 1) {
            if ($model->trans_no == ESheetTrans::PI) {
                $res = $this->UpdateStatus($model->voucher_no);
            }
        }
        return $res;
    }


    private function UpdateStatus($sheet_no) {
        $res = 1;
        try {
            $model_po = $this->where("sheet_no='$sheet_no'")->find();
            if (empty($model_po)) {
                $res = -1;
            } else {
            	$PmSheetDetail=new PmSheetDetail();
                $order_status = $PmSheetDetail->GetOrderStatus($sheet_no);
                $model_po->order_status = $order_status;
                if ($model_po->save()) {
                    $res = 1;
                } else {
                    $res = 0;
                }
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }


    private function CheckModel($model) {
        $isok = TRUE;
        if (empty($model->sheet_no)) {
            $isok = lang("po_empty_sheetno");
        } else if (empty($model->trans_no)) {
            $isok = lang("po_empty_type");
        } else if (empty($model->db_no)) {
            $isok = lang("po_empty_dbno");
        } else if (empty($model->branch_no)) {
            $isok = lang("po_empty_branchno");
        } else if ($model->trans_no == ESheetTrans::YH && empty($model->d_branch_no)) {
            $isok = lang("po_empty_branchno");
        } else if ($model->trans_no != ESheetTrans::YH && empty($model->supcust_no)) {
            $isok = lang("po_empty_sup");
        } else if (!is_numeric($model->sheet_amt)) {
            $isok = lang("po_empty_amount");
        } else if ($model->trans_no == ESheetTrans::PO && empty($model->valid_date)) {
            $isok = lang("po_empty_vali_date");
        } else if ($model->trans_no == ESheetTrans::PI && empty($model->pay_date)) {
            $isok = lang("po_empty_pay_date");
        }
        return $isok;
    }

    public function GetModelsForPos($branch_no, $sheet_no, $trans_no, $start = "", $end = "", $approve_flag = "") {
        
        $where="1=1";
        if (!empty($sheet_no)) {
            $where.=" and s.sheet_no like '%$sheet_no%'";
        }
        //收货门店
        if (!empty($branch_no)) {
            $where.=" and s.d_branch_no like '%$branch_no%'";
        }
        if ($approve_flag != "-1") {
            if (!empty($approve_flag)) {
                $where.=" and s.approve_flag like '%$approve_flag%'";
            }
        }
        if (!empty($start) && empty($end)) {
            $where.=" and date(s.oper_date) >= '$start'";
        } else {
            if (empty($start) && !empty($end)) {
                $where.=" and date(s.oper_date) <= '$end'";
            } else {
                if (!empty($start) && !empty($end)) {
                    if ($start == $end) {
                        $where.=" and date(s.oper_date) = '$end'";
                    } else {
                        $where.=" and date(s.oper_date) >= '$start' and date(s.oper_date) <= '$end'";
                    }
                }
            }
        }
        if (!empty($trans_no)) {
            $where.=" and s.trans_no ='$trans_no'";
        }
        
        $lang_unsubmit=lang("pi_md_un_submit");
        $lang_approved=lang("pi_md_is_approve");
        $lang_noapprove=lang("pi_md_not_approve");
        
        $res=Db::name($this->name)
        ->alias('s')
        ->field("s.sheet_no as sheet_no,s.branch_no,a.branch_name as branch_name,s.d_branch_no,b.branch_name as d_branch_name,s.oper_id as oper_id,s.oper_date as oper_date," .
                "  case s.order_status when 't' then '$lang_unsubmit' else case s.approve_flag when '1' then '$lang_approved' else '$lang_noapprove' end end as approve_name,s.confirm_man,s.valid_date,s.memo,s.approve_flag,c.oper_name,order_status")
        		->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        		->join('pos_operator c','s.oper_id=c.oper_id',"LEFT")
        		->join('pos_branch_info b','s.d_branch_no=b.branch_no',"LEFT")
        		->where($where)
        		->limit($offset,$rows)
        		->order($order)
        		->select();
        
        $result = array();
        foreach ($res as $k => $v) {
            $tt = array();
            $tt["sheet_no"] = $v["sheet_no"];
            $tt["branch_no"] = $v["branch_no"];
            $tt["branch_name"] = $v["branch_name"];
            $tt["d_branch_no"] = $v["d_branch_no"];
            $tt["d_branch_name"] = $v["d_branch_name"];
            $tt["oper_id"] = $v["oper_id"];
            $tt["oper_date"] = $v["oper_date"];
            $tt["approve_name"] = $v["approve_name"];
            $tt["confirm_man"] = $v["confirm_man"];
            $tt["valid_date"] = $v["valid_date"];
            $tt["memo"] = $v["memo"];
            $tt["approve_flag"] = $v["approve_flag"];
            $tt["oper_name"] = $v["oper_name"];
            $tt["order_status"] = $v["order_status"];
            array_push($result, $tt);
        }
        return $result;
    }

}
