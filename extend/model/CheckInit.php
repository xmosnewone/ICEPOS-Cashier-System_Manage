<?php
/**
 * im_check_init表
 */
namespace model;
use think\Db;
use model\CheckSum;
use model\CheckMaster;
use model\PosBranchStock;
use app\admin\components\Enumerable\ECheckRange;

class CheckInit extends BaseModel {

	protected $pk='sheet_no';
	protected $name="im_check_init";
	
    public $branch_name;
    public $oper_name;

    public function CreateSheetno($model) {
        $iscommit = FALSE;
        try {
            if ($model->save()) {
                $iscommit = TRUE;
            } else {
                $iscommit = FALSE;
            }
        } catch (\Exception $ex) {
            $iscommit = $ex;
        }
        return $iscommit;
    }


    public function Add($master, $detail) {
        try {
            $iscommit = TRUE;
            if ($master->save()) {
                foreach ($detail as $k => $v) {
                    $v->sheet_no = $master->sheet_no;
                    if (!empty($v["item_no"])) {

                        $checkSum = new CheckSum();
                        $sumItemInfo = $checkSum->GetSumItemInfo($master->sheet_no, $v["item_no"]);
                        if (empty($sumItemInfo)) {
                            $newDetail = new CheckSum();
                            $newDetail->sheet_no = $master->sheet_no;
                            $newDetail->branch_no = $master->branch_no;
                            $newDetail->item_no = $v["item_no"];
                            $newDetail->in_price = $v["in_price"];
                            $newDetail->sale_price = $v["sale_price"];
                            $newDetail->stock_qty = $v["real_qty"];
                            $newDetail->check_qty = $v["recheck_qty"];
                            $newDetail->balance_qty = abs($v["recheck_qty"] - $v["real_qty"]);
                            $newDetail->process_status = 1;
                            if ($checkSum->Add($newDetail) == FALSE) {
                                $iscommit = FALSE;
                                break;
                            }
                        } else {
                            $checkSum = $sumItemInfo;
                            $checkSum->check_qty = $v["recheck_qty"];
                            $checkSum->balance_qty = abs($v["recheck_qty"] - $v["real_qty"]);

                            if ($checkSum->Add($checkSum) == FALSE) {
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
            } else {
                $res = -1;
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }


    public function Del($sheetno) {
        $master = $this->GetSheet($sheetno);
        if (empty($master)) {
            return -2;
        }
        if ($master->approve_flag == 1) {
            return -0;
        }
        Db::startTrans();
        try {
            $iscommit = TRUE;
            $chkSum = new CheckSum();
			$chkMaster=new CheckMaster();
			$del=$this->where("sheet_no='$sheetno'")->delete();
			
            if ($del!==false && $chkSum->DelAll($sheetno)) {

                if ($chkMaster->DelByCheckno($sheetno)) {
                    $iscommit = TRUE;
                     Db::commit();
                } else {
                    $iscommit = FALSE;
                }
            } else {
                $iscommit = FALSE;
            }
            if ($iscommit) {
                return 1;
            } else {
                return -1;
            }
        } catch (\Exception $e) {
            $iscommit = FALSE;
             Db::rollback();
            return -1;
        }
    }


    public function Approve($initModel, $chkSum, $rangeno = '', $items = '') {
        Db::startTrans();
        $iscommit = TRUE;
        try {
            set_time_limit(860);
            $confirmMan = session("loginname");
            $workDate = date(DATETIME_FORMAT, time());
            $initModel->approve_flag = 1;
            $initModel->confirm_man = $confirmMan;
            $initModel->work_date = $workDate;
            $masterModel =new CheckMaster();
            $checkSum=new CheckSum();
            if ($initModel->save() > 0 && $masterModel->Approve($initModel->sheet_no, $confirmMan, $workDate)) {


                if ($rangeno == ECheckRange::ALL) {
                	$delNum=$checkSum->where("sheet_no='{$initModel->sheet_no}'")->delete();
                    if ($delNum > 0) {
                        foreach ($items as $k => $v) {
                            $newDetail = new CheckSum();
                            $newDetail->sheet_no = $initModel->sheet_no;
                            $newDetail->branch_no = $initModel->branch_no;
                            $newDetail->item_no = $v->item_no == '' ? 0.00 : $v->item_no;
                            $newDetail->in_price = $v->item_price == '' ? 0.00 : $v->item_price;
                            $newDetail->sale_price = $v->sale_price == '' ? 0.00 : $v->sale_price;
                            $newDetail->stock_qty = $v->item_stock == '' ? 0.00 : $v->item_stock;
                            $newDetail->check_qty = $v->check_qty == '' ? 0.00 : $v->check_qty;
                            $newDetail->balance_qty = abs($v->check_qty - $v->real_qty);
                            $newDetail->process_status = 1;
                            $newDetail->memo = $v->memo == '' ? '' : $v->memo;
                            $sumModel=new CheckSum();
                            if ($sumModel->Add($newDetail) == FALSE) {
                                $iscommit = FALSE;
                                break;
                            }
                            $posBranchStock=new PosBranchStock();
                            if ($posBranchStock->UpdateStockQty($initModel->sheet_no, $initModel->branch_no, $v->item_no, $v->check_qty)) {
                                $iscommit = TRUE;
                            } else {
                               Db::rollback();
                                return -3;
                            }
                        }
                    }
                   
                } else {
                    foreach ($chkSum as $key => $value) {
                    	$posBranchStock=new PosBranchStock();
                        if ($posBranchStock->UpdateStockQty($initModel->sheet_no, $initModel->branch_no, $value["item_no"], $value["check_qty"])) {
                            $iscommit = TRUE;
                        } else {
                            Db::rollback();
                            return -3;
                        }
                    }
                }
            } else {
                $iscommit = -1;
            }
            if ($iscommit) {
                Db::commit();
                $ary["work_date"] = $workDate;
                $ary["confirm_man"] = $confirmMan;
                return $ary;
            } else {
                return 0;
            }
        } catch (\Exception $e) {
            $iscommit = FALSE;
           Db::rollback();
            return -2;
        }
    }


    public function UpdateSheetAmt($sheetno, $sheetAmt) {
    	try{
    		$updateNum=$this->save(['sheet_amt'=>$sheetAmt],['sheet_no'=>$sheetno]);
    	}catch(\Exception $e){
    	}
    	
    	return $updateNum;
    }


    public function UpdateSheetProcessStatus($checkMaster, $details) {

        try {
            $iscommit = TRUE;
            $checkSum=new CheckSum();
            $chkSumAll = $checkSum->GetAllSum($checkMaster->sheet_no);
            $exist = array();
            foreach ($details as $kk => $vv) {
                foreach ($chkSumAll as $k => $v) {
                    if (trim($v["item_no"]) == trim($vv["item_no"])) {
                        $v->process_status = 1;
                        $v->memo = $vv["memo"];
                        $exist[] = $vv["item_no"];
                        if ($v->Add($v) == FALSE) {
                            $iscommit = FALSE;
                            break;
                        }
                    } else if (!in_array($v["item_no"], $exist)) {
                        $v->process_status = 0;
                        if ($v->Add($v) == FALSE) {
                            $iscommit = FALSE;
                            break;
                        }
                    }
                }
            }
            if ($iscommit) {
                $res = 1;
            } else {
                $res = -1;
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }


    public function GetSheet($sheetno = "") {
        if (empty($sheetno)) {
            return $this->select();
        } else {
            return $this->where("sheet_no='$sheetno'")->find();
        }
    }


    public function GetPager($rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $transno, $branch_no) {
       
        $where="1=1";
        
        if (!empty($start) && !empty($end)) {
        	if ($start == $end) {
        		$where.=" and date(i.start_time) = date('$start') ";
        	} else {
        		$where.=" and date(i.start_time)>= date('$start') and date(i.start_time) <= date('$end') ";
        	}
        }
        if (!empty($sheet_no)) {
        	$where.=" and i.sheet_no like '%$sheet_no%'";
        }
        if (!empty($approve_flag)) {
        	if ($approve_flag == "-1") {
        		$where.=" and i.approve_flag =0";
        	} else {
        		$where.=" and i.approve_flag ='$approve_flag'";
        	}
        }
        if (!empty($oper_id)) {
        	$where.=" and i.oper_id='$oper_id'";
        }
        if (!empty($branch_no)) {
        	$where.=" and i.branch_no='$branch_no'";
        }
        
        $order = "i.start_time DESC";
        
        $offset = ($page - 1) * $rows;
        
        $list=Db::name($this->name)
        ->alias('i')
        ->field("i.sheet_no, i.branch_no,o.username as oper_name, b.branch_name,i.oper_id,i.oper_date,i.approve_flag,i.start_time, i.work_date,i.confirm_man,i.oper_range, i.check_cls, i.memo")
        ->join('pos_branch_info b','b.branch_no=i.branch_no',"LEFT")
        ->join('sys_manager o','o.loginname = i.oper_id',"LEFT")
        ->order($order)
        ->where($where)
        ->limit($offset,$rows)
        ->select();
        
        
        $rowCount=Db::name($this->name)
        ->alias('i')
       	->join('pos_branch_info b','b.branch_no=i.branch_no',"LEFT")
        ->join('sys_manager o','o.loginname = i.oper_id',"LEFT")
        ->where($where)
        ->count();
       
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $colmns = "sheet_no,branch_no,branch_name,oper_name,approve_flag,work_date,confirm_man,oper_date,oper_range,check_cls,oper_id,start_time,memo";
        $rowIndex = 1;
        foreach ($list as $k => $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    if ($kk == "oper_range") {
                        $vv = $this->GetChectRange($vv);
                    }
                    if ($kk == "start_time") {

                        $vv = date("Y-m-d", strtotime($vv));
                    }
                    $tt[$kk] = $vv;
                    $tt["branch_name"] = $v["branch_name"];
                    $tt["oper_name"] = $v["oper_name"];
                }
            }

            array_push($temp, $tt);
            $rowIndex ++;
        }
        $result["rows"] = $temp;
        return $result;
    }


    public function GetNotUsedPDSheet($sheetno = "") {
        if (empty($sheetno)) {
            return $this->select();
        } else {
            return $this->where("sheet_no='$sheetno' and approve_flag='0'")->find();
        }
    }


    public function GetBranchNotUserSheetno($branchno) {
        if (empty($branchno)) {
            return false;
        } else {
        	return $this->where("branch_no='$branchno' and approve_flag='0'")->find();
        }
    }


    public function GetArraySheet($sheetno) {
        
        $list=Db::name($this->name)
        ->alias('m')
        ->field("m.sheet_no, m.branch_no,b.branch_name,m.approve_flag,m.oper_range,m.oper_date,m.confirm_man,m.oper_id,m.work_date,o.username AS oper_name,m.memo")
        ->join('im_check_init i','m.sheet_no = i.sheet_no',"LEFT")
        ->join('pos_branch_info b','b.branch_no = m.branch_no',"LEFT")
        ->join('sys_manager o','o.loginname = m.oper_id',"LEFT")
        ->where("m.sheet_no = '$sheetno'")
        ->find();
        
        $result = array();
        $colmns = "sheet_no,branch_no,branch_name,approve_flag,oper_date,oper_range,confirm_man,oper_id,work_date,oper_name,memo";
        if (empty($list)) {
            return $result;
        }
        foreach ($list as $kk => $vv) {
            if (in_array($kk, explode(',', $colmns))) {
                $result[$kk] = $vv;
            }
        }
        $result["branch_name"] = $list["branch_name"];
        $result["oper_name"] = $list["oper_name"];
        return $result;
    }


    public function GetChectRange($num) {
        switch ($num) {
            case ECheckRange::ALL:
                $num = "全场盘点";
                break;
            case ECheckRange::SINGLE:
                $num = "单品盘点";
                break;
            case ECheckRange::SORT:
                $num = "类别盘点";
                break;
            case ECheckRange::BRAND:
                $num = "品牌盘点";
                break;
            default :
        }
        return $num;
    }

}
