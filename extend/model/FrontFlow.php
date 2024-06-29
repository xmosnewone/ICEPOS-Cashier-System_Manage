<?php
//front_flow表
namespace model;
use think\Db;
use constant\EFlowStatus;
use constant\ESheetOper;
use model\FrontSaleflow;
use model\FlowLog;
use model\PosBranchStock;

class FrontFlow extends BaseModel {

	protected $pk='sheet_no';
	protected $name="front_flow";
	
	public $order_name;
	public $pay_way;
	public $fast_time;
	public $fast_info;
	public $pay_time;
	
	public $pay_typename;
	public $send_name;
	public $status;
	public $pay_name;
	public $rowIndex;
	public $branch_name;

    public function search($condition=[]) {
    	$list=Db::table($this->table)
    	->where($condition)
    	->select();
    }

    public function SearchOrders($startDate, $endDate, $sheet_no, $pur_no, $order_status, $pay_type, $branch_no, $page, $rows, $isvoucher_no = "") {
    	
    	$where="a.type_no='OF' and b.pay_status='1' and (c.type_no='OP' or c.type_no='BK')";
    	if (!empty($sheet_no)) {
    		$where.=" and s.sheet_no like '%$sheet_no%'";
    	}
    	if (!empty($pur_no)) {
    		$where.=" and s.order_man ='$pur_no'";
    	}
    	if (!empty($order_status)) {
    		$where.=" and s.order_status ='$order_status'";
    	}
    	if (!empty($isvoucher_no)) {
    		$where.=" and s.trans_no ='BR'";
    	} else {
    		$where.=" and s.trans_no ='FR'";
    	}
    	
    	if (!empty($startDate) && !empty($endDate)) {
    		if ($startDate == $endDate) {
    			$where.=" and date(s.order_time) = date('$startDate')";
    		} else {
    			$where.=" and date(s.order_time) >= date('$startDate') and date(s.order_time) <= date('$endDate')";
    		}
    	}
    	if (!empty($pay_type)) {
    		$where.=" and b.pay_way ='$pay_type'";
    	}
    	if (!empty($branch_no)) {
    		$where.=" and s.branch_no='$branch_no'";
    	}
    	
    	$offset= ($page - 1) * $rows;
    	$list=Db::name($this->name)
    	->alias('s')
    	->field("s.sheet_no,s.order_time,round(s.order_amt,2) as order_amt,round(s.pay_amt,2) as pay_amt,a.code_name as status,c.code_name as pay_name,"
    			. "d.branch_name,e.pur_realname as order_name")
    	->join('bd_base_code a','s.order_status=a.code_id',"LEFT")
    	->join('front_payflow b','s.sheet_no=b.sheet_no',"LEFT")
    	->join('bd_base_code c','b.pay_way=c.code_id',"LEFT")
    	->join('pos_branch_info d','s.branch_no=d.branch_no',"LEFT")
    	->join('sale_purchaser e','s.order_man=e.pur_no',"LEFT")
    	->where($where)
    	->limit($offset,$rows)
    	->select();
    	
    	$count=Db::name($this->name)
		    	->alias('s')
		    	->field("s.sheet_no,s.order_time,round(s.order_amt,2) as order_amt,round(s.pay_amt,2) as pay_amt,a.code_name as status,c.code_name as pay_name,"
		    			. "d.branch_name,e.pur_realname as order_name")
		    	->join('bd_base_code a','s.order_status=a.code_id',"LEFT")
		    	->join('front_payflow b','s.sheet_no=b.sheet_no',"LEFT")
		    	->join('bd_base_code c','b.pay_way=c.code_id',"LEFT")
		    	->join('pos_branch_info d','s.branch_no=d.branch_no',"LEFT")
		    	->join('sale_purchaser e','s.order_man=e.pur_no',"LEFT")
		    	->where($where)
		    	->count();
    	
    	$result = array();
    	$i = 1;
    	foreach ($temp as $v) {
    		$tt = array();
    		$tt["rowIndex"] = $i;
    		$tt["sheet_no"] = $v["sheet_no"];
    		$tt["order_time"] = $v["order_time"];
    		$tt["order_amt"] = $v["order_amt"];
    		$tt["pay_amt"] = $v["pay_amt"];
    		$tt["status"] = $v["status"];
    		$tt["pay_name"] = $v["pay_name"];
    		$tt["branch_name"] = $v["branch_name"];
    		$tt["order_name"] = $v["order_name"];
    		$i++;
    		array_push($result, $tt);
    	}
    	return array("total" => $count, "rows" => $result);
    }
    
    public function GetSheet($sheet_no) {
    	
    	return $list=Db::name($this->name)
    	->alias('s')
    	->field("s.sheet_no,s.branch_no,s.sell_way,s.order_man," .
    			"s.order_amt,s.pay_amt,s.sale_amt,s.fast_amt," .
    			"s.address,s.invoice," .
    			"s.pay_type,s.send_type," .
    			"s.order_time,s.approve_time,s.cancel_time,s.confirm_time,s.return_time," .
    			"s.order_man,s.approve_man,s.cancel_man,s.send_man," .
    			"s.order_status,s.order_orgi," .
    			"a.code_name as status," .
    			"b.pur_name as order_name," .
    			"c.code_name as pay_typename," .
    			"d.code_name as send_name," .
    			"s.branch_no,e.branch_name,s.voucher_no")
    	->join('bd_base_code a','s.order_status=a.code_id',"LEFT")
    	->join('sale_purchaser b','s.order_man=b.pur_no',"LEFT")
    	->join('bd_base_code c','s.pay_type=c.code_id',"LEFT")
    	->join('bd_base_code d','s.send_type=d.code_id',"LEFT")
    	->join('pos_branch_info e','s.branch_no=e.branch_no',"LEFT")
    	->where("s.sheet_no = '$sheet_no' and c.type_no='PW' and d.type_no='PS'")
    	->find();
    	
    }
    
    public function ApproveOrder($sheet_no, $orderMan) {
    	$result = 0;
    	try {
    		$order = $this->where("sheet_no='$sheet_no'")->find();
    		if (!empty($order)) {
    			if ($order->order_status == EFlowStatus::APPROVE || ($order->order_status == EFlowStatus::CANCEL && strlen($order->voucher_no) == 0)) {
    				$log = new FlowLog();
    				$isok = true;
    				if ($order->order_status == EFlowStatus::APPROVE) {
    					$order->order_status = EFlowStatus::SEND;
    					$log->o_order_status = EFlowStatus::APPROVE;
    					$log->n_order_status = EFlowStatus::SEND;
    					$log->oper_type = ESheetOper::APPROVE;
    					$log->oper_message = "确认审核订单";
    				} else {
    					$order->order_status = EFlowStatus::BACK;
    					$log->o_order_status = EFlowStatus::CANCEL;
    					$log->n_order_status = EFlowStatus::BACK;
    					$log->oper_type = ESheetOper::ACANCEL;
    					$log->oper_message = "确认退货订单";
    					
    					$frontSaleFlow=new FrontSaleflow();
    					$details = $frontSaleFlow->GetSaleDetails($order->order_status);
    					foreach ($details as $detail) {
    						$psbs=new PosBranchStock();
    						if ($psbs->UpdateStockForFlow($detail->sheet_no, "+", $order->branch_no, $detail->item_no, $detail->real_qty, $detail->sell_way) <= 0) {
    							$isok = false;
    							break;
    						}
    					}
    					if ($isok) {
    						$order_t = $this->where("voucher_no='{$order->sheet_no}'")->find();
    						if (!empty($order_t)) {
    							$order_t->order_status = EFlowStatus::BACK;
    							$isok = $order_t->save();
    						} else {
    							$isok = FALSE;
    						}
    					}
    
    				}
    				if ($isok) {
    					$order->approve_man = $orderMan;
    					$order->approve_time = date(DATE_FORMAT);
    					$log->sheet_no = $sheet_no;
    					$log->oper_id = $orderMan;
    					$log->oper_orgi = "B";
    					$log->oper_date = date(DATETIME_FORMAT);
    					if ($log->save()) {
    						if ($order->save()) {
    							$result = 1;
    						} else {
    							$result = 0;
    						}
    					}
    				}
    			} else {
    				$result = -1;
    			}
    		} else {
    			$result = -1;
    		}
    	} catch (\Exception $ex) {
    		write_log("审核订单(ApproveOrder)异常:" . $ex,"FrontFlow");
    		$result = -2;
    	}
    	return $result;
    }
    
    public function SendOrder($sheet_no, $orderMan, $fast) {
    	$result = 0;
    	 Db::startTrans();
    	try {
    		$order = $this->where("sheet_no='$sheet_no'")->find();
    		if (!empty($order)) {
    			if ($order !== "3") {
    				if ($fast->save()) {
    					$order->order_status = "4";
    					$order->send_man = $orderMan;
    					$log = new FlowLog();
    					$log->sheet_no = $sheet_no;
    					$log->o_order_status = EFlowStatus::SEND;
    					$log->n_order_status = EFlowStatus::RECIVE;
    					$log->oper_type = ESheetOper::SEND;
    					$log->oper_id = $orderMan;
    					$log->oper_date = date(DATETIME_FORMAT);
    					$log->oper_orgi = "B";
    					$log->oper_message = "配送订单";
    					if ($log->save()) {
    						if ($order->save()) {
    							$result = 1;
    						}
    					}
    				}
    			} else {
    				$result = -1;
    			}
    		} else {
    			$result = -1;
    		}
    		if ($result > 0) {
    			 Db::commit();
    		} else {
    			 Db::rollback();
    		}
    	} catch (\Exception $ex) {
    		write_log("配送订单(SendOrder)异常:" . $ex,"FrontFlow");
    		 Db::rollback();
    		$result = -2;
    	}
    	return $result;
    }
    
    public function AddFlow($order, $details, $log=false) {
        $result = $this->CheckModel($order);
        $frontSaleFlow=new FrontSaleflow();
        if ($result === 1) {
        	Db::startTrans();
            try {
                if ($order->save() === TRUE) {
                    $result = $frontSaleFlow->AddDetails($details, $order['branch_no'], true);
                }
                if ($result === 1) {
                    if ($result == 1) {
                        Db::commit();
                    } else {
                        Db::rollback();
                    }
                } else {
                    Db::rollback();
                }
            } catch (\Exception $ex) {
            	write_log("提交订单(AddFlow)异常:" . $ex,"FrontFlow");
               	Db::rollback();
                $result = -2;
            }
        }
        return $result;
    }


    private function CheckModel($order) {
        $result = 0;
        try {
            if (empty($order)) {
                $result = 0;
            } else if (empty($order['sheet_no'])) {
                $result = '单号不能为空';
            } else if (empty($order['branch_no'])) {
                $result = "门店编号不能为空";
            } else if (empty($order['order_man'])) {
                $result = "订货人不能为空";
            } else if (!is_numeric($order['order_amt'])) {
                $result = "订单金额格式不正确";
            } else if (!is_numeric($order['sale_amt'])) {
                $result = "优惠金额格式不正确";
            } else if (!is_numeric($order['fast_amt'])) {
                $result = "快递费用格式不正确";
            } else if (!is_numeric($order['pay_amt'])) {
                $result = "支付金额格式不正确";
            } else if (empty($order['address'])) {
                $result = "收获人信息和地址不能为空";
            } else if (empty($order['invoice'])) {
                $result = "发票信息不能为空";
            } else if (empty($order['order_orgi'])) {
                $result = "订单来源不能为空";
            } else {
                $result = 1;
            }
        } catch (\Exception $ex) {
        	write_log("订单表检查订单(CheckModel)异常:" . $ex,"FrontFlow");
            $result = -2;
        }
        return $result;
    }

    public function GetPager($order_man, $start_date, $end_date, $start_price, $end_price, $sheet_no, $order_status) {
		
        $where="a.type_no='OF' and b.pay_status='1' and (c.type_no='BK' or c.type_no ='OP') ";
        $orderby = "s.order_time desc";
        
        if (!empty($order_man)) {
            $where.=" and s.order_man='$order_man' ";
        }
        if (!(empty($start_date) && empty($end_date))) {
            if (!empty($start_date) && !empty($end_date)) {
                if ($start_date === $end_date) {
                    $where.=" and date(s.order_time)='$start_date' ";
                } else {
                    $where.=" and date(s.order_time) >= $start_date and date(s.order_time) <= $end_date ";
                }
            } else {
                if (!empty($start_date)) {
                    $where.=" and date(s.order_time) >= $start_date ";
                }
                if (!empty($end_date)) {
                    $where.=" and date(s.order_time) <= $end_date ";
                }
            }
        }
        if (!(empty($start_price) && empty($end_price))) {
            if (!empty($start_price) && !empty($end_price)) {
                if ($start_price === $end_price) {
                    $where.=" and s.pay_amt=$end_price ";
                } else {
                    $where.=" and s.pay_amt >= $start_price and s.pay_amt <= $end_price ";
                }
            } else {
                if (!empty($start_price)) {
                    $where.=" and s.pay_amt >= $start_price ";
                }
                if (!empty($end_price)) {
                    $where.=" and s.pay_amt <= $end_price ";
                }
            }
        }
        if (!empty($sheet_no)) {
            $where.=" and s.sheet_no like '%{$sheet_no}%' ";
        }
        if (!empty($order_status) && $order_status != "all") {
            $where.=" and s.order_status=$order_status ";
        }
   
		
        $list=Db::table($this->table)
        ->alias('s')
        ->field("s.sheet_no,a.code_name as order_name,round(s.pay_amt,2) as pay_amt,c.code_name as pay_way,s.order_time")
        ->join('bd_base_code a','s.order_status=a.code_id',"LEFT")
        ->join('front_payflow b','s.sheet_no=b.sheet_no',"LEFT")
        ->join('bd_base_code c','b.pay_way=c.code_id',"LEFT")
        ->order($orderby)
        ->where($where)
        ->paginate(10);
        
        //分页渲染
        $page = $list->render();
        
        //统计数量
        $recordCount=Db::table($this->table)
        ->alias('s')
        ->join('bd_base_code a','s.order_status=a.code_id',"LEFT")
        ->join('front_payflow b','s.sheet_no=b.sheet_no',"LEFT")
        ->join('bd_base_code c','b.pay_way=c.code_id',"LEFT")
        ->where($where)
        ->count();
        
        return array("count" => $recordCount, "list" => $list, "page" => $page);
    }

	//获取账单
    public function GetFlow($sheet_no) {
        $one=Db::table($this->table)
        ->alias('s')
        ->field("s.order_status,a.code_name as order_name,s.address,s.order_time,c.pay_time,b.fast_time,CONCAT(b.fast_company,':',b.fast_no) as fast_info,s.confirm_time,s.pay_amt")
        ->join('bd_base_code a'," s.order_status=a.code_id ","LEFT")
        ->join('front_sendflow b'," s.sheet_no=b.sheet_no ","LEFT")
        ->join('front_payflow c'," s.sheet_no=c.sheet_no ","LEFT")
        ->where("s.sheet_no='$sheet_no' and a.type_no='OF' and c.pay_status='1' ")
        ->find();
        //a.type_no='OF' 标签前台下单基础代码
        return $one;
    }

    public function SaveOrder($sheet_no, $orderMan) {
        $result = 0;
        $frontFlow=new FrontFlow();
        try {
        	$order=$frontFlow->where("sheet_no='$sheet_no'")->find();
            if (!empty($order)) {
                if (intval($order->order_status) === intval(EFlowStatus::RECIVE)) {
                    $log = new FlowLog();
                    $order->order_status = EFlowStatus::OVER;
                    $order->confirm_time = date(DATETIME_FORMAT);
                    $log->sheet_no = $order->sheet_no;
                    $log->o_order_status = EFlowStatus::RECIVE;
                    $log->n_order_status = EFlowStatus::OVER;
                    $log->oper_id = $orderMan;
                    $log->oper_type = ESheetOper::OVER;
                    $log->oper_date = date(DATETIME_FORMAT);
                    $log->oper_orgi = "A";
                    $log->oper_message = "确认收货";
                    if ($log->save()) {
                        $result = $order->save() ? 1 : 0;
                    }
                } else {
                    $result = -1;
                }
            } else {
                $result = -1;
            }
        } catch (\Exception $ex) {
            write_log("审核订单(ApproveOrder)异常:" . $ex,"FrontFlow");
            $result = -2;
        }
        return $result;
    }


    public function CancelOrder($sheet_no, $orderMan) {
       $result = 0;
        try {
        	$order=$this->where("sheet_no='$sheet_no'")->find();
            if (!empty($order)) {
                if ($order->order_status === EFlowStatus::CANCEL || $order->order_status === EFlowStatus::BACK) {
                    $result = -1;
                } else {
                    $result = $this->AddCancelOrder($order, $orderMan);
                }
            } else {
                $result = -1;
            }
        } catch (\Exception $ex) {
            $result = -2;
           	write_log("取消订单(CancelOrder)异常:" . $ex,"FrontFlow");
        }
        return $result;
    }


    public function AddCancelOrder($order, $orderMan) {
      	$result = 0;
       	Db::startTrans();
        try {
            $order_t = new FrontFlow();
            $order_t->sheet_no = "T" . $order->sheet_no;
            $order_t->branch_no = $order->branch_no;
            $order_t->sell_way = "B";
            $order_t->trans_no = "BR";
            $order_t->db_no = "+";
            $order_t->order_man = $orderMan;
            $order_t->order_time = date(DATETIME_FORMAT);
            $order_t->order_amt = $order->order_amt;
            $order_t->pay_amt = $order->pay_amt;
            $order_t->sale_amt = $order->sale_amt;
            $order_t->fast_amt = $order->fast_amt;

            $order_t->order_orgi = "A";
            $frontSaleflow=new FrontSaleflow();
            $details = $frontSaleflow->GetSaleDetails($order->sheet_no);
            $details_t = array();
            foreach ($details as $detail) {
                $de = new FrontSaleflow();
                $de->sheet_no = $order_t->sheet_no;
                $de->real_qty = $detail["real_qty"];
                $de->price = $detail["price"];
                $de->item_no = $detail["item_no"];
                $de->sell_way = "B";
                array_push($details_t, $de);
            }
            if ($order->order_status === EFlowStatus::RECIVE || $order->order_status === EFlowStatus::OVER) {

                $result =$frontSaleflow->AddDetails($details_t, $order_t->branch_no, false);
                $order_t->order_status = EFlowStatus::CANCEL;
            } else {

                $result = $frontSaleflow->AddDetails($details_t, $order_t->branch_no, true);
                $order_t->order_status = EFlowStatus::CANCEL;
            }
            if ($result > 0) {
                $result = $order_t->save() ? 1 : 0;
                if ($result > 0) {
                    $order_status = $order->order_status;
                    $order->voucher_no = $order_t->sheet_no;
                    $order->order_status = EFlowStatus::CANCEL;
                    $order->cancel_man = $orderMan;
                    $order->cancel_time = date(DATETIME_FORMAT);
                    $result = $order->save() ? 1 : 0;
                    if ($result > 0) {
                        $log = new FlowLog();
                        $log->sheet_no = $order->sheet_no;
                        $log->o_order_status = $order_status;
                        $log->n_order_status = EFlowStatus::CANCEL;
                        $log->oper_type = ESheetOper::CANCEL;
                        $log->oper_id = $orderMan;
                        $log->oper_orgi = "A";
                        $log->oper_date = date(DATETIME_FORMAT);
                        $log->oper_message = "取消订单" . $order_t->sheet_no;
                        $result = $log->save() ? 1 : 0;
                    }
                }
            }
            if ($result > 0) {
                Db::commit();
            } else {
                Db::rollback();
            }
        } catch (\Exception $ex) {
            Db::rollback();
            $result = -2;
            write_log("新增取消订单(AddCancelOrder)异常:" . $ex,"FrontFlow");
        }
        return $result;
    }

}
