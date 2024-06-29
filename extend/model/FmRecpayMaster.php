<?php
/**
 * fm_recpay_master表
 */
namespace model;
use think\Db;
use model\FmRecpayDetail;

class FmRecpayMaster extends BaseModel {

	protected $pk='sheet_no';
	protected $name="fm_recpay_master";

    public function GetPager($rows, $page, $start, $end, $sheet_no,$supcustno, $approve_flag, $oper_id, $tran_no,$amount) {
        
        $where = "1=1";
        if (!empty($start) && !empty($end)) {
        	if ($start == $end) {
        		$where.=" and date(s.oper_date) = date('$start')";
        	} else {
        		$where.=" and date(s.oper_date) > = date('$start') and date(s.oper_date) <= date('$end')";
        	}
        }
        if (!empty($sheet_no)) {
        	$where.=" and s.sheet_no like '%$sheet_no%'";
        }
        if (!empty($approve_flag)) {
        	if ($approve_flag == "-1") {
        		$where.=" and s.approve_flag = 0";
        	} else {
        		$where.=" and s.approve_flag = '$approve_flag'";
        	}
        }
        if (!empty($oper_id)) {
        	$where.=" and s.oper_id='$oper_id'";
        }
        if (!empty($tran_no)) {
        	$where.=" and s.trans_no='$tran_no'";
        }
        if (!empty($supcustno)) {
        	$where.=" and s.supcust_no='$supcustno'";
        }
        if (!empty($amount)) {
        	$where.=" and s.sheet_amt>'$amount'";
        }
       
        $offset = ($page - 1) * $rows;
        
        $list=Db::name($this->name)
        ->alias('s')
        ->field("s.sheet_no, s.approve_flag, p.linkname as supcust_no, s.sheet_amt,d.oper_name as oper_id,s.oper_date,s.confirm_man")
        ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
        ->join('bd_wholesale_clients p','p.clients_no= s.supcust_no',"LEFT")
        ->where($where)
        ->limit($offset,$rows)
        ->select();
        
        $rowCount=Db::name($this->name)
			        ->alias('s')
			       	->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
			        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
			        ->join('purchaser p','p.pur_no= s.supcust_no',"LEFT")
			        ->where($where)
			        ->count();
        
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $colmns = "sheet_no,approve_flag,supcust_no,sheet_amt,oper_id,oper_date,confirm_man";
        $rowIndex = 1;
        foreach ($list as $k => $v) {
            $tt = array();
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    $tt[$kk] = $vv;
                }
            }
            $tt['rowIndex'] = $rowIndex;
            array_push($temp, $tt);
            $rowIndex++;
        }
        $result["rows"] = $temp;
        return $result;
    }


    public function getRpMaster($sheetno) {
       
        return $list=$this->alias('s')
			        ->field("s.sheet_no, s.supcust_no, p.linkname,i.pay_name, s.approve_flag, s.oper_id, d.oper_name, f.loginname , s.oper_date, s.pay_way, s.confirm_man, s.work_date ,s.memo")
			        ->join('pos_operator d','s.oper_id = d.oper_id',"LEFT")
			        ->join('sys_manager f','s.confirm_man= f.loginname',"LEFT")
			        ->join('bd_wholesale_clients p','p.clients_no= s.supcust_no',"LEFT")
			        ->join('bd_payment_info i','i.pay_way= s.pay_way',"LEFT")
			        ->where("s.sheet_no= '$sheetno' and i.pay_flag=2")
        			->find();
    }


    public function RPApprove($sheetno) {
        $loginname = session("loginname");
        $model = $this->where("sheet_no='$sheetno'")->find();

        if (empty($model)) {
            return -2;
        }
        if ($model->approve_flag == 1) {
            return -0;
        }

        $model->approve_flag = '1';
        $model->work_date = date(DATETIME_FORMAT,time());
        $model->confirm_man = $loginname;
        $isCommit = false;

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
    }


    public function verifyApproveCount($supcustno) {
        return $list=$this->alias('s')
			        ->field("s.sheet_no")
			        ->join('fm_recpay_detail d','d.sheet_no=s.sheet_no',"LEFT")
			        ->where("s.supcust_no='$supcustno' and s.approve_flag=0")
			        ->select();
    }
    
    public function verifyApprove($supcustno) {
    	return $list=$this->alias('s')
    	->field("s.sheet_no")
    	->join('fm_recpay_detail d','d.sheet_no=s.sheet_no',"LEFT")
    	->where("s.supcust_no='$supcustno' and s.approve_flag=0")
    	->find();
    }

    
    public function addDetails($details) {
    	Db::startTrans();
    	$res = 0;
    	try {
    		$iscommit = TRUE;
    		 
    		if ($details->addData($details) == FALSE) {
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

    public function addData($master, $details, $funcType) {
        Db::startTrans();
        $res = 0;
        try {
            $iscommit = TRUE;
           
            if ($funcType == "add" ? $master->save() : $master->save($master,['sheet_no'=>$master->sheet_no])) {
                if ($details->addData($details) == FALSE) {
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

    public function delData($sheetno) {
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
                    	$frDetail=new FmRecpayDetail();
                        $iscommit = $frDetail->delData($sheetno);
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
    
}
