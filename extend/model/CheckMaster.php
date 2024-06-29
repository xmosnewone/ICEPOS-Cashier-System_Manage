<?php
/**
 * im_check_master表
 */
namespace model;
use think\Db;
use model\CheckDetail;
use model\CheckSum;
use model\CheckInit;

class CheckMaster extends BaseModel {

    public $branch_name;
    public $oper_name;

    protected $pk='sheet_no';
    protected $name="im_check_master";

    public function Add($master, $detail, $checkInit) {
        try {
            Db::startTrans();
            $iscommit = TRUE;
            if ($master->save()) {
                foreach ($detail as $k => $v) {
                    $v->sheet_no = $master->sheet_no;
                    if (!empty($v["item_no"])) {
                        $checkDetail = new CheckDetail();
                        $detailItemInfo = $checkDetail->GetDetailItemInfo($master->sheet_no, $v["item_no"]);
                        if (empty($detailItemInfo)) {
                            if ($checkDetail->Add($v) == FALSE) {
                                $iscommit = FALSE;
                                break;
                            }
                        } else {
                            $checkDetail = $detailItemInfo;
                            $checkDetail->recheck_qty = $v["recheck_qty"];
                            if ($checkDetail->Add($checkDetail) == FALSE) {
                                $iscommit = FALSE;
                                break;
                            }
                        }
                    }
                }

                $initModel = $this->GetCRSheetAmt($checkInit->sheet_no);
                if (empty($initModel)) {
                    $initSheetAmt = $checkInit->sheet_amt;
                } else {
                    $initSheetAmt = $initModel;
                }
                $checkInit->sheet_amt = $initSheetAmt;
                if ($checkInit->Add($checkInit, $detail) <= 0) {
                    $iscommit = FALSE;
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
        return $res;
    }


    public function Del($sheetno) {

        $model = $this->where("sheet_no='$sheetno'")->find();
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
						$checkDetail=new CheckDetail();
                        $modelDetail =$checkDetail->GetCheckDetailBySheetno($sheetno);
                        if (!is_array($modelDetail)) {
                            return 0;
                        }
                        if($checkDetail->Del($sheetno)!==false){
                            $iscommit = TRUE;
                        }else{
                             return -3;
                        }
                        $chkSum = new CheckSum();
                        foreach ($modelDetail as $key => $value) {
                            if ($chkSum->Del($model->check_no, $value["item_no"])!==false) {
                                $iscommit = TRUE;
                            } else {
                                return -3;
                            }
                        }
                        $checkInit=new CheckInit();
                        $chkInit = $checkInit->GetSheet($model->check_no);
                        if (empty($chkInit)) {
                            return -4;
                        }
                        if ($chkInit->UpdateSheetAmt($model->check_no, $chkInit->sheet_amt - $model->sheet_amt)) {
                            $iscommit = TRUE;
                        } else {
                            return -5;
                        }
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


    public function DelByCheckno($checkno){
        $master = $this->GetAllByCheckno($checkno);
        $flag = TRUE;
        $delNum=$this->where("check_no='$checkno'")->delete();
        if($delNum > 0){
        	$checkDeatil=new CheckDetail();
            foreach ($master as $key => $value) {
                 
                if($checkDeatil->Del($value->sheet_no)){
                    
                     $flag = TRUE;
                }else{
                    return FALSE;
                }
            }
        }
        return $flag;
    }

    public function Approve($checkno, $confirmMan, $workDate){
        $attrArry = array(
            "approve_flag"=>1,
            "confirm_man"=>$confirmMan,
            "work_date"=>$workDate
        );
        $updateNum=$this->where("check_no='$checkno'")->update($attrArry);
        if($updateNum > 0){
            return TRUE;
        }
        return FALSE;
    }

    public function GetAllByCheckno($checkno){
    	return $this->where("check_no='$checkno'")->select();
    }
    
    public function GetSheet($sheetno) {
        return $this->where("sheet_no='$sheetno'")->find();
    }

    //盘点单号获取数据
    public function GetArraySheet($sheetno) {

        $list=Db::name($this->name)
        ->alias('m')
        ->field("m.sheet_no,m.trans_no, m.check_no, m.branch_no,b.branch_name,m.approve_flag,m.oper_range,m.oper_date,m.confirm_man,m.oper_id,o.username AS oper_name,m.memo")
        ->join('im_check_init i','m.check_no = i.sheet_no',"LEFT")
        ->join('pos_branch_info b','b.branch_no = m.branch_no',"LEFT")
        ->join('sys_manager o','o.loginname = m.oper_id',"LEFT")
        ->where("m.sheet_no = '$sheetno'")
        ->find();
        
        $result = array();
        $colmns = "sheet_no,trans_no,check_no,branch_no,branch_name,approve_flag,oper_date,oper_range,confirm_man,oper_id,oper_name,memo";
        if(empty($list)){
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
    
    //盘点批号获取数据
    public function GetArraySheetPdno($pdno) {
    
    	$list=Db::name("im_check_init")
    	->alias('i')
    	->field("i.sheet_no, i.branch_no,b.branch_name,i.approve_flag,i.oper_range,i.oper_date,i.confirm_man,i.oper_id,i.memo,o.username AS oper_name")
    	->join('pos_branch_info b','b.branch_no = i.branch_no',"LEFT")
    	->join('sys_manager o','o.loginname = i.oper_id',"LEFT")
    	->where("i.sheet_no = '$pdno'")
    	->find();
    
    	$result = array();
    	$colmns = "sheet_no,branch_no,branch_name,approve_flag,oper_date,oper_range,confirm_man,oper_id,oper_name,memo";
    	if(empty($list)){
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


    public function GetCRSheetAmt($checkno) {
        return Db::name($this->name)->where("check_no='$checkno'")->sum("sheet_amt");
    }


    public function GetPager($rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $transno, $branch_no) {
        
        $where = "1=1";
        if (!empty($start) && !empty($end)) {
        	if ($start == $end) {
        		$where.=" and date(m.oper_date) = date('$start')";
        	} else {
        		$where.=" and date(m.oper_date) >= date('$start') and date(m.oper_date) <= date('$end')";
        	}
        }
        if (!empty($sheet_no)) {
        	$where.=" and m.sheet_no like '%$sheet_no%' ";
        }
        if (!empty($approve_flag)) {
        	if ($approve_flag == "-1") {
        		$where.=" and m.approve_flag =0 ";
        	} else {
        		$where.=" and m.approve_flag =$approve_flag ";
        	}
        }
        if (!empty($oper_id)) {
        	$where.=" and m.oper_id=$oper_id";
        }
        if (!empty($branch_no)) {
        	$where.=" and m.branch_no='$branch_no'";
        }
        if (!empty($transno)) {
        	$where.=" and m.trans_no='$transno'";
        }
       
        $order = "m.oper_date DESC";
        
        $rowCount=Db::name($this->name)
        ->alias('m')
        ->join('im_check_init i','m.check_no = i.sheet_no',"LEFT")
        ->join('pos_branch_info b','b.branch_no = m.branch_no',"LEFT")
        ->join('sys_manager o','o.loginname = m.oper_id',"LEFT")
        ->where($where)
        ->count();
        
        $offset = ($page - 1) * $rows;
        
        $list=Db::name($this->name)
        ->alias('m')
        ->field("m.sheet_no,m.trans_no, m.check_no, m.branch_no,b.branch_name,m.approve_flag,m.oper_date,m.confirm_man,m.oper_id,o.username AS oper_name,m.memo")
        ->join('im_check_init i','m.check_no = i.sheet_no',"LEFT")
        ->join('pos_branch_info b','b.branch_no = m.branch_no',"LEFT")
        ->join('sys_manager o','o.loginname = m.oper_id',"LEFT")
        ->order($order)
        ->where($where)
        ->limit($offset,$rows)
        ->select();
        
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $colmns = "sheet_no,trans_no,check_no,branch_no,branch_name,approve_flag,oper_date,confirm_man,oper_id,oper_name,memo";
        $rowIndex = 1;
        foreach ($list as $k => $v) {
            $tt = array();
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    $tt[$kk] = $vv;
                }
            }
            $tt["rowIndex"] = $rowIndex;
            $tt["branch_name"] = $v["branch_name"];
            $tt["oper_name"] = $v["oper_name"];
            array_push($temp, $tt);
            $rowIndex++;
        }
        $result["rows"] = $temp;

        return $result;
    }

}
