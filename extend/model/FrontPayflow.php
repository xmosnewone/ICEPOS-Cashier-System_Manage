<?php
//front_payflow表
namespace model;
use think\Db;
use constant\EFlowStatus;
use constant\ESheetOper;
use model\FrontFlow;

class FrontPayflow extends BaseModel {
	
	protected $pk='pay_no';
	protected $name="front_payflow";
	
	public $pay_typename;
	public $pay_name;
	public $in_pay;
	public $out_pay;

    public function search($condition=[]) {
        $list=Db::table($this->table)
    	->where($condition)
    	->select();
    }

    public function GetAllPayFlow($order_man, $start_date, $end_date, $sheet_no, $pay_type) {
       
        $where=" a.type_no = 'PT' and (b.type_no = 'OP' or b.type_no = 'BK') ";

        if (!empty($order_man)) {
            $where.=" and s.order_man='$order_man' ";
        }
        if (!(empty($start_date) && empty($end_date))) {
            if (!empty($start_date) && !empty($end_date)) {
                if ($start_date === $end_date) {
                    $where.=" and date(s.pay_time)='$start_date' ";
                } else {
                    $where.=" and date(s.pay_time) >= '$start_date' and date(s.pay_time) <= $end_date ";
                }
            } else {
                if (!empty($start_date)) {
                    $where.=" and date(s.pay_time) >= '$start_date' ";
                }
                if (!empty($end_date)) {
                    $where.=" and date(s.pay_time) <= '$end_date'";
                }
            }
        }
        if (!empty($sheet_no)) {
            $where.=" and s.sheet_no like '%{$sheet_no}%' ";
        }
        if (!empty($pay_type) && $pay_type != "All") {
            if ($pay_type === "1") {
                $where.=" and s.pay_type='1' ";
            } else {
                $where.=" and s.pay_type <> '1' ";
            }
        }
        
        $where.=" and s.pay_type='1' ";
        $orderby = "s.pay_time desc";
        
        $list=Db::table($this->table)
        ->alias('s')
        ->field("s.pay_time,s.sheet_no,a.code_name as pay_typename,s.memo, " .
                "case s.pay_type when s.pay_type='1' then round(s.pay_amt,2) else 0  end as out_pay," .
                "case s.pay_type when s.pay_type='1' then 0 else round(s.pay_amt,2) end as in_pay," .
                "round(s.pur_amt,2) as pur_amt,b.code_name as pay_name,s.pay_order,s.pay_amt,s.pay_type")
        ->join('bd_base_code a','s.pay_type=a.code_id',"LEFT")
        ->join('bd_base_code b','s.pay_way=b.code_id',"LEFT")
        ->order($orderby)
        ->where($where)
        ->paginate(10);
        
        $income = 0;
        $income_count = 0;
        $expland = 0;
        $expland_count = 0;
        $data = array("income" => 0, "income_count" => 0, "expland" => 0, "expland_count" => 0);
        foreach ($list as $v) {
            if ($v["pay_type"] === "1") {
                $data["expland"]+=doubleval($v["pay_amt"]);
                $data["expland_count"]+=1;
            } else {
                $data["income"]+=doubleval($v["pay_amt"]);
                $data["income_count"]+=1;
            }
        }
        
         //分页渲染
         $pages = $list->render();
        return array("list" => $list, "sum" => $data, "page" => $pages);
    }

	//支付订单
    public function PayOrder($order_no, $out_trade_no) {
    	$frontFlow=new FrontFlow();
        $result = 0;
        $unlock_sql = "UNLOCK TABLES ";
        try {
            $lock_sql = "LOCK TABLES " . $this->table . " WRITE";
            Db::query($lock_sql);
            
            $sql = "select * from " . $this->table . " where sheet_no='$order_no' order by pay_time desc limit 1 ";
            $payinfo= Db::query($sql);
            
            if (empty($payinfo)) {
                $result = -1;
            } else {
                foreach ($payinfo as $row) {
                   	Db::startTrans();
                    $pay_no = $row["pay_no"];
                    $isok = false;
                    if ($row["pay_status"] === "1") {
                        $result = 1;
                        $isok = true;
                    } else {
                        
                        Db::execute("update " . $this->table 
                        			. " set pay_status=1,over_time=".date(DATETIME_FORMAT, time()).",pay_order='$out_trade_no' where pay_no='$pay_no' ");
                        
                        if ($result1 > 0) {
                            $isok = true;
                        } else {
                        	write_log("支付异常：支付订单号" . $order_no,"FrontPayFlow");
                        }
                        if ($isok == true) {
                            $order =$frontFlow->where("sheet_no='$order_no'")->find();
                            $order->order_status = "2";
                            if ($order->save()) {
                                $log = new FlowLog();
                                $log->sheet_no = $order_no;
                                $log->o_order_status = EFlowStatus::ADD;
                                $log->n_order_status = EFlowStatus::APPROVE;
                                $log->oper_id = $order->order_man;
                                $log->oper_date = date(DATETIME_FORMAT);
                                $log->oper_type = ESheetOper::PAY;
                                $log->oper_message = "第三方订单号:" . $out_trade_no;
                                if ($log->save()) {
                                    $result = 1;
                                    write_log("支付成功：支付订单号" . $order_no . "（" . $out_trade_no . "）","FrontPayFlow");
                                    Db::commit();
                                } else {
                                    $result = 0;
                                    write_log("支付成功：支付订单号" . $order_no . "（" . $out_trade_no . "）新增订单操作记录异常","FrontPayFlow");
                                    Db::rollback();
                                }
                            } else {
                            		Db::rollback();
                                	write_log("支付成功：支付订单号" . $order_no . "（" . $out_trade_no . "）更新订单表数据异常","FrontPayFlow");
                            }
                        }
                    }
                }
            }
            
            Db::query($unlock_sql);
        } catch (\Exception $ex) {
            Db::query($unlock_sql);
            write_log("支付异常：支付订单号" . $order_no . "（" . $out_trade_no . "）异常","FrontPayFlow");
            $result = -2;
        }
        return $result;
    }
    
    public function GetPay($sheet_no) {
    	
    	return $list=Db::name($this->name)
    				->alias('s')
    				->field("s.pay_way,a.code_name as pay_name,s.pay_order,s.pay_time,s.over_time,s.memo,s.pay_status,s.client_ip,s.memo")
    				->join('bd_base_code a','s.pay_way=a.code_id',"LEFT")
    				->where("s.sheet_no= '$sheet_no' and s.pay_status='1' and a.type_no='OP' or a.type_no='BK'")
    				->find();
    }

}
