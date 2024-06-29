<?php
//pos_daysum表
namespace model;
use think\Db;
use model\PosSaleFlow;
use model\BdItemCombsplit;
use model\PosBranchStock;

class PosDaysum extends BaseModel {

	protected $pk='sum_id';
	protected $name="pos_daysum";
	
    public function GetStart() {
        $res = $this->max("end_date",false);
        if (empty($res)) {
            return date("Y-m-d", strtotime("-1 day")) . " 00:00:00";
        } else {
            if (empty($res)) {
                return date("Y-m-d", strtotime("-1 day")) . " 00:00:00";
            } else {
                $tm = strtotime($res);
                $tm += 1;
                $dt = date('Y-m-d H:i:s', $tm);
                return $dt;
            }
        }
    }


    public function AddDaysum($start, $end, $oper_id) {
        $result = 0;
        if ($this->CheckDaysum($start, $end) == 1) {
            $result = -3;
        } else {
            Db::startTrans();
            try {
            	$PosSaleflow=new PosSaleFlow();
                $sale_flows = $PosSaleflow->GetModelsForDaySum($start, $end);

                $isok = TRUE;
                foreach ($sale_flows as $sale) {
                    $db_no = "-";
                    if ($sale->sell_way == "B") {
                        $db_no = "+";
                    }
                    $BdItemCombsplit=new BdItemCombsplit();
                    $PosBranchStock=new PosBranchStock();
                    $combs = $BdItemCombsplit->GetSingle($sale->item_no);
                    
                    if (empty($combs)) {
                    	
                        $isok = $PosBranchStock->UpdateStock($sale->branch_no, $sale->item_no, $sale->sale_qnty, $db_no);
                        if (!$isok) {
                            break;
                        } else {
                            $sale->over_flag = '1';
                            if ($sale->save() == FALSE) {
                                $isok = FALSE;
                                break;
                            }
                        }
                    } else {
                        foreach ($combs as $cob) {
                            $isok = $PosBranchStock->UpdateStock($sale->branch_no, trim($cob->item_no), ($sale->sale_qnty) * ($cob->item_qty), $db_no);
                            if (!$isok) {
                                break;
                            } else {
                                $sale->over_flag = '1';
                                if ($sale->save() == FALSE) {
                                    $isok = FALSE;
                                    break;
                                }
                            }
                        }
                    }
                }
                if ($isok) {

                    $models = $this->GetDaySum($start, $end, $oper_id);
                    if (empty($models)) {
                        $result = 0;
                    } else {
                        foreach ($models as $model) {
                            if ($model->save() == FALSE) {
                                $isok = FALSE;
                            }
                        }
                        if ($isok == FALSE) {
                            $result = -1;
                        } else {
                            $result = 1;
                        }
                    }
                } else {
                    $result = -1;
                }

                if ($result == 1) {
                    Db::commit();
                } else {
                    Db::rollback();
                }
            } catch (\Exception $ex) {
                $result = -2;
            }
        }
        return $result;
    }


    private function CheckDaysum($start, $end) {
        $result = $this->where("begin_date >= '$start' and end_date <= '$end'")->select();
        if (count($result) == 0) {
            return 0;
        } else {
            return 1;
        }
    }


    private function GetDaySum($start, $end, $oper_id) {
    	$prefix=$this->prefix;
        $sql = "SELECT 
				d.branch_no,
				d.pos_id,
				d.oper_id,
				d.real_amt as pay_amt,
				d.pay_amt as pay_sum_amt,
				d.rete_amt as pay_ret_amt,
				d.give_amt as pay_giv_amt,
				d.pay_rmb_amt-d.rete_amt as pay_rmb_amt,
				d.pay_crd_amt as pay_crd_amt,
				d.pay_card_amt as pay_card_amt,
				c.real_money as sale_amt,
				c.sale_money-c.ret_money as sale_sum_amt,
				c.ret_money as sale_ret_amt,
				c.giv_money as sale_giv_amt,
				c.real_qty as pay_qty,
				c.sale_qty as sale_qty,
				c.ret_qty as ret_qty,
				c.giv_qty as giv_qty
				FROM 
				(
				SELECT
				b.branch_no,
				b.pos_id,
				b.oper_id,
				b.pay_amt-b.rete_amt-b.give_amt as real_amt,
				b.pay_amt,
				b.rete_amt,
				b.give_amt,
				b.pay_rmb_amt-b.give_amt as pay_rmb_amt,
				b.pay_crd_amt,
				b.pay_card_amt
				FROM 
				(
				SELECT p.branch_no,p.pos_id,sum(p.convert_amt) sum_amt,p.oper_id,
				sum(case when p.sale_way='A' then p.convert_amt else 0 end) as pay_amt,
				sum(case when p.sale_way='B' then p.convert_amt else 0 end) as rete_amt,
				sum(case when p.sale_way='D' then p.convert_amt else 0 end) as give_amt,
				sum(case when p.sale_way='A'  then 
				  case when p.pay_way='RMB'  then p.convert_amt else 0 end else 0 end) as pay_rmb_amt,
				sum(case when p.sale_way='A'  then 
				  case when p.pay_way='CRD'  then p.convert_amt else 0 end else 0 end) as pay_crd_amt,
				sum(case when p.sale_way='A'  then 
				  case when p.pay_way='HF'  then p.convert_amt else 0 end else 0 end) as pay_card_amt
				FROM ".$prefix."pos_payflow p
				where p.oper_date >= '$start' and p.oper_date <= '$end'
				group by p.branch_no,p.pos_id,p.oper_id
				) b ) d
				LEFT JOIN
				(
				SELECT
				a.branch_no,
				a.pos_id,
				a.oper_id,
				a.sale_money-a.ret_money as real_money,
				a.sale_money,
				a.ret_money,
				a.giv_money,
				a.sale_qty-a.ret_qty as real_qty,
				a.sale_qty,
				a.ret_qty,
				a.giv_qty
				FROM 
				(
				SELECT
				s.branch_no,s.pos_id,s.oper_id,
				sum(case when s.sell_way='A' then s.sale_money else 0 end ) as sale_money,
				sum(case when s.sell_way='B' then s.sale_money else 0 end ) as ret_money,
				sum(case when s.sell_way='C' then s.unit_price*s.sale_qnty else 0 end) as giv_money,
				sum(case when s.sell_way='A' then s.sale_qnty else 0 end ) as sale_qty,
				sum(case when s.sell_way='B' then s.sale_qnty else 0 end ) as ret_qty,
				sum(case when s.sell_way='C' then s.sale_qnty else 0 end) as giv_qty 
				from ".$prefix."pos_saleflow s
				where s.oper_date >= '$start' and s.oper_date <= '$end'
				group by s.branch_no,s.pos_id,s.oper_id
				) a ) c
				ON 
				d.branch_no=c.branch_no and d.pos_id=c.pos_id and d.oper_id=c.oper_id";
        
        $result = Db::query($sql);
        $res = array();
        if (!empty($result)) {
            foreach ($result as $v) {
                $model = new PosDaysum();
                $model->branch_no = $v["branch_no"];
                $model->pos_id = $v["pos_id"];
                $model->sale_man = $v["oper_id"];
                $model->begin_date = $start;
                $model->end_date = $end;
                $model->oper_id = $oper_id;
                $model->oper_date = date(DATETIME_FORMAT,time());
                $model->sale_amt = $v["sale_amt"];
                $model->pay_amt = $v["pay_amt"];
                $model->sale_sum_amt = $v["sale_sum_amt"];
                $model->pay_sum_amt = $v["pay_sum_amt"];
                $model->pay_giv_amt = $v["pay_giv_amt"];
                $model->pay_ret_amt = $v["pay_ret_amt"];
                $model->sale_ret_amt = $v["sale_ret_amt"];
                $model->sale_giv_amt = $v["sale_giv_amt"];
                $model->pay_rmb_amt = $v["pay_rmb_amt"];
                $model->pay_crd_amt = $v["pay_crd_amt"];
                $model->pay_card_amt = $v["pay_card_amt"];
                $model->pay_qty = $v["pay_qty"];
                $model->sale_qty = $v["sale_qty"];
                $model->ret_qty = $v["ret_qty"];
                $model->giv_qty = $v["giv_qty"];
                array_push($res, $model);
            }
        }
        return $res;
    }

    //080
    public function SearchDaySum($start, $branch_no, $page, $rows) {

        $where="1=1";
        if (!empty($start)) {
            $where.=" and s.begin_date <= '$start' and s.end_date >= '$start'";
        }
        if (!empty($branch_no)) {
            $where.=" and s.branch_no='$branch_no'";
        }
        
        $offset = ($page - 1) * $rows;
        
        $result=Db::name($this->name)
        		->alias('s')
        		->field("s.oper_id,s.oper_date,s.begin_date,s.end_date,s.branch_no,a.branch_name,b.oper_name,s.pos_id," .
                "sum(s.sale_amt) as sale_amt,sum(s.pay_amt) as pay_amt,sum(s.sale_sum_amt) as sale_sum_amt," .
                "sum(s.pay_sum_amt) as pay_sum_amt,sum(s.pay_giv_amt) as pay_giv_amt," .
                "sum(s.sale_ret_amt) as sale_ret_amt,sum(s.pay_ret_amt) as pay_ret_amt," .
                "sum(s.pay_rmb_amt) as pay_rmb_amt,sum(s.pay_card_amt) as pay_card_amt," .
                "sum(s.sale_giv_amt) as sale_giv_amt,sum(s.sale_qty) as sale_qty,sum(s.ret_qty) as ret_qty ," .
                "sum(s.giv_qty) as giv_qty,sum(s.pay_qty) as pay_qty," .
                "s.sale_man,c.oper_name as sale_name")
        		->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        		->join('pos_operator b','s.oper_id=b.oper_id',"LEFT")
        		->join('pos_operator c','s.sale_man=c.oper_id',"LEFT")
        		->group("s.branch_no,s.oper_id,s.oper_date,s.begin_date,s.end_date,a.branch_name,b.oper_name,s.pos_id,s.sale_man,c.oper_name")
        		->limit($offset,$rows)
        		->where($where)
        		->select();
       
        $prefix=$this->prefix;
        $countSQL="select count(*) as total from (";
        $countSQL.="select count(*) from ".$prefix."pos_daysum s"
        		." left join ".$prefix."pos_branch_info a on s.branch_no=a.branch_no"
        		." left join ".$prefix."pos_operator b on s.oper_id=b.oper_id"
        		." left join ".$prefix."pos_operator c on s.sale_man=c.oper_id"
        		." where ".$where
        		." group by s.branch_no,s.oper_id,s.oper_date,s.begin_date,s.end_date,a.branch_name,b.oper_name,s.pos_id,s.sale_man,c.oper_name";
        $countSQL.=") temp";

        $count=Db::query($countSQL);
        $total=$count[0]['total'];
        
        $res = array();
        foreach ($result as $v) {
            $tt = array();
            $tt["oper_id"] = $v["oper_id"];
            $tt["pos_id"] = $v["pos_id"];
            $tt["sale_name"] = $v["sale_name"];
            $tt["oper_date"] = $v["oper_date"];
            $tt["begin_date"] = $v["begin_date"];
            $tt["end_date"] = $v["end_date"];
            $tt["branch_no"] = $v["branch_no"];
            $tt["branch_name"] = $v["branch_name"];
            $tt["oper_name"] = $v["oper_name"];
            $tt["sale_amt"] = sprintf("%.2f", $v["sale_amt"]);
            $tt["pay_amt"] = sprintf("%.2f", $v["pay_amt"]);
            $tt["sale_sum_amt"] = sprintf("%.2f", $v["sale_sum_amt"]);
            $tt["pay_sum_amt"] = sprintf("%.2f", $v["pay_sum_amt"]);
            $tt["pay_giv_amt"] = sprintf("%.2f", $v["pay_giv_amt"]);
            $tt["sale_ret_amt"] = sprintf("%.2f", $v["sale_ret_amt"]);
            $tt["pay_ret_amt"] = sprintf("%.2f", $v["pay_ret_amt"]);
            $tt["sale_giv_amt"] = sprintf("%.2f", $v["sale_giv_amt"]);
            $tt["pay_rmb_amt"] = sprintf("%.2f", $v["pay_rmb_amt"]);

            $tt["pay_card_amt"] = sprintf("%.2f", $v["pay_card_amt"]);
            $tt["sale_qty"] = sprintf("%.2f", $v["sale_qty"]);
            $tt["ret_qty"] = sprintf("%.2f", $v["ret_qty"]);
            $tt["giv_qty"] = sprintf("%.2f", $v["giv_qty"]);
            $tt["pay_qty"] = sprintf("%.2f", $v["pay_qty"]);
            array_push($res, $tt);
        }
        return ['total'=>$total,"rows"=>$res];
    }

    

}
