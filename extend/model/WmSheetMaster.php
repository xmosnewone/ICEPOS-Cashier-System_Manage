<?php
//wm_sheet_master	批发销售退货单等主表
namespace model;
use think\Db;
use model\WmSheetDetail;
use app\admin\components\Enumerable\ESheetTrans;

class WmSheetMaster extends BaseModel {

    protected $pk='sheet_no';
    protected $name="wm_sheet_master";

    public function Add($model, $details, $operFunc, $transno) {
        $res = $this->Check($model);
        if ($res == 1) {
            try {
               Db::startTrans();
                $iscommit = TRUE;
                $WmSheetDetail=new WmSheetDetail();
                if ($operFunc == "update") {
                    $iscommit = $WmSheetDetail->Del($model->sheet_no);
                }
                if ($operFunc == "update" ? $model->save($model, ['sheet_no'=>$model->sheet_no]) : $model->save()) {
                    if ($iscommit) {
                        foreach ($details as $k => $v) {
                            $v->sheet_no = $model->sheet_no;
                            if (!empty($v["item_no"])) {
                                if ($WmSheetDetail->Add($v) == FALSE) {
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
            $res = lang("rp_md_empty_no");
        } else if (empty($model->trans_no)) {
            $res = lang("rp_md_empty_trans_no");
        } else if (empty($model->db_no)) {
            $res = lang("rp_md_empty_db_no");
        } else if (empty($model->branch_no)) {
            $res = lang("rp_md_empty_branch_no");
        } else if (empty($model->d_branch_no) && $model->trans_no == ESheetTrans::MO) {
            $res = lang("rp_md_empty_dbranch_no");
        } else if (empty($model->oper_id)) {
            $res = lang("rp_md_empty_operid");
        } else if (!is_numeric($model->sheet_amt)) {
            $res = lang("rp_md_empty_amt");
        }
        return $res;
    }


    public function Approve($sheetno) {

        $loginname = session("loginname");
        $model = $this->where("sheet_no='$sheetno'")->find();
        
        if (empty($model)) {
            return -2;
        }
        if ($model->approve_flag == 1) {
            return -0;
        }

        try {

            $model->approve_flag = '1';
            $model->work_date = date(DATETIME_FORMAT,time());
            $model->confirm_man = $loginname;

            if ($model->save()) {
                $isCommit = true;
            }

            if ($isCommit) {

                $ary["work_date"] = $model->work_date;
                $ary["confirm_man"] = $model->confirm_man;
                return $ary;
            } else {

                return -1;
            }
        } catch (\Exception $e) {
            return -3;
        }
    }
    
    public function ApproveSO($sheetno){

        $loginname = session("loginname");
        $modelSO = $this->where("sheet_no='$sheetno'")->find();
        if (empty($modelSO)) {
            return -2;
        }
        if ($modelSO->approve_flag == 1) {
            return -0;
        }

        Db::startTrans();
        try {

            $modelSO->approve_flag = '1';
            $modelSO->work_date = date(DATETIME_FORMAT,time());
            $modelSO->confirm_man = $loginname;
           
            $isCommit = FALSE;
            if ($modelSO->save()) {

                $modelSS = $this->where("sheet_no='{$modelSO->voucher_no}'")->find();
                $modelSS->order_status = 2;
                if($modelSS->save()){
                     $isCommit = true;
                }
            }

            if ($isCommit) {
                Db::commit();
                $ary["work_date"] = $modelSO->work_date;
                $ary["confirm_man"] = $modelSO->confirm_man;
                return $ary;
            } else {
                Db::rollback();
                return -1;
            }
        } catch (\Exception $e) {
           		Db::rollback();
           		return -3;
        }
    }

	
    public function GetPager($rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id, $tran_no,$clients_no,$amount) {
       
    	$lang_in=lang("wm_md_all_in");
    	$lang_part=lang("wm_md_part_in");
    	$lang_unhandle=lang("wm_md_unhandle");

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
        		$approve_flag=0;
        	} 
        	$where.=" and s.approve_flag = '$approve_flag'";
        	
        }
        if (!empty($oper_id)) {
        	$where.=" and s.oper_id = '$oper_id'";
        }
        if (!empty($tran_no)) {
        	$where.=" and s.trans_no = '$tran_no'";
        }
        if (!empty($clients_no)) {
        	$where.=" and s.supcust_no = '$clients_no'";
        }
        if (!empty($amount)) {
        	$where.=" and s.sheet_amt > $amount";
        }
        
        $offset = ($page - 1) * $rows;
        $list=Db::name($this->name)
        ->alias('s')
        ->field( "s.sheet_no,case s.order_status when '2' then '$lang_in' when '1' then '$lang_part' else '$lang_unhandle' end as order_status
        		,s.branch_no,b.branch_name,s.voucher_no,c.branch_name as d_branch_name,s.approve_flag," .
                "s.oper_date,s.oper_id,d.oper_name,s.confirm_man,s.sheet_amt,s.memo," .
                "e.username,s.oper_date,f.username as confirm_name,s.work_date")
        		->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        		->join('pos_branch_info c','s.branch_no = c.branch_no',"LEFT")
        		->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        		->join('sys_manager e','s.order_man= e.loginname',"LEFT")
        		->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        		->order("s.oper_date DESC")
        		->limit($offset,$rows)
        		->where($where)
        		->order("oper_date desc")
        		->select();
        
        $rowCount=Db::name($this->name)
        		->alias('s')
        		->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        		->join('pos_branch_info c','s.branch_no = c.branch_no',"LEFT")
        		->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        		->join('sys_manager e','s.order_man= e.loginname',"LEFT")
        		->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        		->where($where)
        		->count();
      
        $result = array();
        $result["total"] = $rowCount;
        $rowIndex = ($page - 1) * $rows + 1;
        $temp = array();
        $colmns = "sheet_no,branch_no,order_status,branch_name,voucher_no,d_branch_name," .
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

	//080
    public function verifySheetExists($sheet_no){

        $model=$this
        ->alias('s')
        ->field("sheet_no")
        ->where("s.voucher_no='$sheet_no' and s.approve_flag != 1")
        ->find();
        return $model;
    }
    
    public function Get($sheet_no){
        
        $where="s.sheet_no='$sheet_no'";
        $one=$this->alias('s')
        		->field("s.sheet_no,s.branch_no,s.trans_no,s.supcust_no,i.pay_name,s.coin_no, s.pay_way,p.linkname,s.valid_date,b.branch_name,s.order_man,s.voucher_no,c.branch_name as d_branch_name,s.approve_flag," .
                "s.oper_date,s.oper_id,d.oper_name,s.confirm_man,s.sheet_amt,s.memo,e.username,f.username as confirm_name,s.work_date,s.voucher_no")
        		->join('pos_branch_info b','s.branch_no = b.branch_no',"LEFT")
        		->join('pos_branch_info c','s.branch_no = c.branch_no',"LEFT")
        		->join('bd_wholesale_clients p','p.clients_no = s.supcust_no',"LEFT")
        		->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        		->join('sys_manager e','s.order_man= e.loginname',"LEFT")
        		->join('bd_payment_info i','i.pay_way= s.pay_way',"LEFT")
        		->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        		->where($where)
        		->find();
        return $one;
    }

    public function Del($sheet_no) {
    	$sheet_no=trim($sheet_no);
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
                    	$WmSheetDetail=new WmSheetDetail();
                        $iscommit =$WmSheetDetail->Del($sheet_no);
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

    public function getCostomerAmount($supcustno){
        
        $where=[];
        $where['supcust_no']=$supcustno;
        $where['trans_no']=ESheetTrans::SO;
        $where['approve_flag']=1;
        $one=$this->field("sheet_no,sum(sheet_amt) as sheet_amt")->where($where)->find();
        return $one->sheet_amt;
        
    }
    
}
