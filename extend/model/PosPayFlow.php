<?php
//pos_payflow表
namespace model;
use think\Db;
use model\PosSaleFlow;
use model\BdItemCombsplit;
use model\PosBranchStock;

class PosPayFlow extends BaseModel {

	protected $pk='id';
	protected $name="pos_payflow";

    public function getall($content) {
        
        $where=[];
        
        if ($content['flow_no'] != '') {
        	$where['flow_no']= $content['flow_no'];
        }
        if ($content['vip_no'] != '') {
        	$where['vip_no']= $content['vip_no'];
        }
        
        $pagesize = 30;
        $list=$this->where($where)->order("id desc")->paginate($pagesize);
        $page=$list->render();

        $return['result'] = $list;
        $return['pages'] = $page;
        return $return;
    }
	
    //根据订单号获取收银流水
    public function getFlowItems($flow_no) {
    	$list=$this->where(["flow_no"=>$flow_no])->order("id desc")->select();
    	return $list;
    }

    public function get($flowno) {
        return $this->where("flow_no='$flowno'")->find();
    }


    public function add($payflow, $saleflow) {

        Db::startTrans();
        $res = 0;
        try {

            $iscommit = TRUE;
            $payflow = $this->get($payflow->flow_no);
            if (!empty($payflow)) {
                Db::rollback();
                return -5;
            }
            if ($payflow->save()) {
            	$PosSaleFlow=new PosSaleFlow();
                foreach ($saleflow as $k => $v) {
                    $v->flow_no = $payflow->flow_no;
                    if (!empty($v["item_no"])) {
                        if ($PosSaleFlow->add($v) == FALSE) {
                            $iscommit = FALSE;
                            break;
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
        return $res;
    }


    public function AddModelsForPos($pays, $sales) {
        $result = 0;
        Db::startTrans();
        try {
            $isok = true;
            foreach ($pays as $pay) {
                if ($pay->save() == FALSE) {
                    $isok = FALSE;
                    break;
                }
            }
            if ($isok) {
            	$BdItemCombsplit=new BdItemCombsplit();
            	$PosBranchStock=new PosBranchStock();
                foreach ($sales as $sale) {
                    $combs = $BdItemCombsplit->GetSingle($sale->item_no);
                    $db_no = "-";
                    if ($sale->sell_way == "B") {
                        $db_no = "+";
                    }
                    if (empty($combs)||count($combs)<=0) {
                        if ($PosBranchStock->UpdateStockBySheetNo($pay->flow_no, $sale->branch_no, $sale->item_no, $sale->sale_qnty, $db_no)) {
                            $isok = TRUE;
                        } else {
                            $isok = FALSE;
                        }
                    } else {
                        foreach ($combs as $cob) {
                            if ($PosBranchStock->UpdateStockBySheetNo($pay->flow_no, $sale->branch_no, trim($cob->item_no), ($sale->sale_qnty) * ($cob->item_qty), $db_no)) {
                                $isok = TRUE;
                            } else {
                                $isok = FALSE;
                            }
                        }
                    }
                    if ($isok == TRUE) {
                        $sale->over_flag = 1;
                        if ($sale->save() == FALSE) {
                            $isok = FALSE;
                            break;
                        }
                    } else {
                        break;
                    }
                }
            }
            if ($isok) {
                Db::commit();
                $result = 1;
            } else {
                Db::rollback();
                $result = 0;
            }
        } catch (\Exception $ex) {
            Db::rollback();
            $result = $ex;
        }
        return $result;
    }


    public $branch_name;

    public $oper_name;
    public $rowIndex;


    public function SearchModels($start, $end, $branch_no, $posid, $flowno, $vipno, $sale_way, $payflag, $posflag, $page, $rows, $operid) {
       
        $where="1=1";
        if (!empty($start) && !empty($end)) {
            if ($start == $end) {
                $where.=" and date(s.oper_date) = '$start'";
            } else {
                $end = date('Y-m-d', strtotime('+1 day', strtotime($end)));
                $where.=" and s.oper_date >= '$start' and s.oper_date < '$end'";
            }
        }
        if (!empty($branch_no)) {
           	 	$where.=" and s.branch_no='$branch_no'";
        }
        if (!empty($posid)) {
            $where.=" and s.pos_id='$posid'";
        }
        if (!empty($flowno)) {
            $where.=" and s.flow_no like '%$flowno%'";
        }
        if (!empty($vipno)) {
            $where.=" and s.vip_no = '$vipno'";
        }
        if (!empty($sale_way)) {
            $where.=" and s.sale_way = '$sale_way'";
        }
        if (!empty($payflag)) {
            $where.=" and s.coin_type = '$payflag'";
        }
        if (!empty($operid)) {
            $where.=" and s.oper_id = '$operid'";
        }
        if (!empty($posflag)) {
            if ($posflag == "-1") {
                $posflag = "0";
            }
            $where.=" and s.pos_flag = '$posflag'";
        }

        $offset = ($page - 1) * $rows;
        
        $list=Db::name($this->name)
        		->alias('s')
        		->field("s.branch_no,s.flow_no,s.oper_date,s.coin_type,s.pay_name," .
                "s.card_no,s.vip_no,s.sale_amount,s.pay_amount," .
                "s.pos_id,s.oper_id,s.voucher_no,s.memo,a.branch_name,b.oper_name," .
                "case s.sale_way when 'A' then '销售' when 'B' then '退货' when 'C' then '赠送' else '找零' end as sale_way")
        		->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        		->join('pos_operator b','s.oper_id= b.oper_id',"LEFT")
        		->order("oper_date desc")
        		->limit($offset,$rows)
        		->where($where)
        		->select();

        $rowCount=Db::name($this->name)
        		->alias('s')
        		->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        		->join('pos_operator b','s.oper_id= b.oper_id',"LEFT")
        		->where($where)
        		->count();
        
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $footer = array();
        $footer_detail = array();

        $footer_detail["branch_no"] = "合计:";
        $footer_detail["flow_no"] = "";
        $temp_flow = array();
        $rowIndex = ($page - 1) * $rows + 1;
        foreach ($list as $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["branch_no"] = $v["branch_no"];
            $tt["flow_no"] = $v["flow_no"];
            if (!in_array($v["flow_no"], $temp_flow)) {
                array_push($temp_flow, $v["flow_no"]);
                $footer_detail["sale_amount"]+=doubleval($v["sale_amount"]);
                $footer_detail["pay_amount"]+=doubleval($v["sale_amount"]);
            }
            $tt["oper_date"] = $v["oper_date"];
            $tt["coin_type"] = $v["coin_type"];
            $tt["pay_name"] = $v["pay_name"];
            $tt["card_no"] = $v["card_no"];
            $tt["vip_no"] = $v["vip_no"];
            $tt["sale_way"] = $v["sale_way"];
            $tt["sale_amount"] = formatMoneyDisplay($v["sale_amount"]);
            $tt["pay_amount"] = formatMoneyDisplay($v["pay_amount"]);
            $tt["pos_id"] = $v["pos_id"];
            $tt["oper_id"] = $v["oper_id"];
            $tt["voucher_no"] = $v["voucher_no"];
            $tt["memo"] = $v["memo"];
            $tt["branch_name"] = $v["branch_name"];
            $tt["oper_name"] = $v["oper_name"];
            $rowIndex++;
            array_push($temp, $tt);
        }
        array_push($footer, $footer_detail);
        $result["rows"] = $temp;
        $result["footer"] = $footer;
        return $result;
    }


    public function GetPaySumInfoForVoucher($flowno) {
        
        $temp=Db::name($this->name)
        		->alias('s')
        		->field("s.pay_name,sum(s.pay_amount) as pay_amount")
        		->group("s.pay_name")
        		->where(['s.flow_no'=>$flowno])
        		->select();
        
        $result = array();
        foreach ($temp as $v) {
            $tt = array();
            $tt["pay_name"] = $v["pay_name"];
            $tt["pay_amount"] = formatMoneyDisplay($v["pay_amount"]);
            array_push($result, $tt);
        }
        return $result;
    }


    public function GetBaseInfoForVoucher($flowno) {
        
        $result=Db::name($this->name)
        		->alias('s')
        		->field("s.branch_no,s.flow_no,s.oper_date," .
                "s.pos_id,a.branch_name as branch_name,b.oper_name as oper_name,s.vip_no,s.sale_way")
        		->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        		->join('pos_operator b','s.oper_id= b.oper_id',"LEFT")
        		->where(['s.flow_no'=>$flowno])
        		->find();
        
        $temp = array();
        $temp["branch_no"] = $result["branch_no"];
        $temp["flow_no"] = $result["flow_no"];
        $temp["oper_date"] = $result["oper_date"];
        $temp["pos_id"] = $result["pos_id"];
        $temp["branch_name"] = $result["branch_name"];
        $temp["oper_name"] = $result["oper_name"];
        $temp["vip_no"] = $result["vip_no"];
        $temp["sale_way"] = $result["sale_way"];
        return $temp;
    }


    public $sale_name;


    public function AccountSumarryForOperatorStop($start, $end, $branch_no, $oper_id, $pay_way, $page, $rows, $mark) {
        
        $where="b.type_no='SY'";
        if (!empty($start) && !empty($end)) {
            if ($start == $end) {
                $where.=" and date(s.oper_date) = '$start'";
            } else {
                $end = date(DATE_FORMAT, strtotime("+1 days", strtotime($end)));
                $where.=" and s.oper_date >= '$start' and s.oper_date < '$end'";
            }
        }
        if (!empty($branch_no)) {
            $where.=" and s.branch_no='$branch_no'";
        }
        if (!empty($pay_way)) {
            $where.=" and s.pay_way='$pay_way'";
        }
        if (!empty($oper_id)) {
            $where.=" and s.oper_id='$oper_id'";
        }
        
        $temp=Db::name($this->name)
        		->alias('s')
        		->field("s.branch_no,a.branch_name,s.oper_id,c.oper_name,s.pay_way,s.sale_way," .
                "s.pos_id,s.pay_name,b.code_name as sale_name,sum(s.pay_amount) as pay_amount,sum(s.pay_amount*s.coin_rate) as convert_amt")
        		->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        		->join('bd_base_code b','s.sale_way = b.code_id',"LEFT")
        		->join('pos_operator c','s.oper_id= c.oper_id',"LEFT")
        		->group("s.branch_no,a.branch_name,s.oper_id,c.oper_name,s.pay_way,s.sale_way," .
                "s.pos_id,s.pay_name,b.code_name")
        		->where(['s.flow_no'=>$flowno])
        		->select();
        
        $rowCount=Db::name($this->name)
        		->alias('s')
        		->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        		->join('bd_base_code b','s.sale_way = b.code_id',"LEFT")
        		->join('pos_operator c','s.oper_id= c.oper_id',"LEFT")
        		->group("s.branch_no,a.branch_name,s.oper_id,c.oper_name,s.pay_way,s.sale_way," .
                "s.pos_id,s.pay_name,b.code_name")
        		->where(['s.flow_no'=>$flowno])
        		->count();
        
        $result = array();
        $result["total"] = $rowCount;
        $res = array();
        $footer = array();
        $footer1 = array();
        $footer1["branch_no"] = "合计:";
        $footer1["branch_name"] = "";
        $footer1["oper_id"] = "";
        $footer1["oper_name"] = "";
        $footer1["pos_id"] = "";
        $footer1["pay_name"] = "";
        $footer1["sale_name"] = "";

        $rowIndex = 0;
        foreach ($temp as $k => $v) {
            $tt = array();
            if ($v["sale_way"] != "D") {
                $tt["rowIndex"] = $rowIndex + 1;
                $tt["branch_no"] = $v["branch_no"];
                $tt["branch_name"] = $v["branch_name"];
                $tt["oper_id"] = $v["oper_id"];
                $tt["oper_name"] = $v["oper_name"];
                $tt["pos_id"] = $v["pos_id"];
                $tt["pay_name"] = $v["pay_name"];
                $tt["sale_name"] = $v["sale_name"];
                $tt["pay_amount"] = "";
                $tt["convert_amt"] = "";
                if ($v["pay_way"] == "RMB" && $v["sale_way"] == "A") {
                    $charge = $this->GetChargeAmt($temp, $v["branch_no"], $v["pos_id"], $v["oper_id"]);
                    $tt["pay_amount"] = formatMoneyDisplay($v["pay_amount"] - $charge);
                    $tt["convert_amt"] = formatMoneyDisplay($v["convert_amt"] - $charge);
                } else {
                    if ($v["sale_way"] == "B") {
                        $tt["pay_amount"] = formatMoneyDisplay(-$v["pay_amount"]);
                        $tt["convert_amt"] = formatMoneyDisplay(-$v["convert_amt"]);
                    } else {
                        $tt["pay_amount"] = formatMoneyDisplay($v["pay_amount"]);
                        $tt["convert_amt"] = formatMoneyDisplay($v["convert_amt"]);
                    }
                }
                $footer1["pay_amount"] +=doubleval($tt["pay_amount"]);
                $footer1["convert_amt"] +=doubleval($tt["convert_amt"]);
                $rowIndex++;
                array_push($res, $tt);
            }
        }
        $result["rows"] = $res;
        array_push($footer, $footer1);

        return $result;
    }

    public function AccountSumarryForOperator($start, $end, $branch_no, $oper_id, $pay_way, $page = "", $rows = "", $mark = "") {
        $result = array();
        $params = array();
        $prefix=$this->prefix;
        $where1 = "";
        $iswhere1 = false;
        if (!empty($start) && !empty($end)) {
            if ($start == $end) {
                $where1 = $where1 . " where date(oper_date) = '$start'";
                $iswhere1 = true;
            } else {
                $where1 = $where1 . " where oper_date >= '$start' and oper_date < '$end'";
                $iswhere1 = true;
            }
        }
        $where2 = "";
        $iswhere2 = false;
        if (!empty($branch_no)) {
            $where2 = $where2 . " where e.branch_no = '$branch_no'";
            $iswhere2 = true;
        }
        if (!empty($oper_id)) {
            if ($iswhere1) {
                $where1 = $where1 . " and oper_id = '$oper_id'";
            } else {
                $where1 = $where1 . " where oper_id = '$oper_id'";
                $iswhere2 = true;
            }
        }
        $where3 = $where1;
        if (!empty($pay_way)) {
            if ($iswhere2) {
                $where2 = $where2 . " and e.pay_way = '$pay_way'";
            } else {
                $where2 = $where2 . " where e.pay_way = '$pay_way'";
                $iswhere2 = true;
            }
        }
        $sql = "SELECT
                e.branch_no,
	i.branch_name,
	e.oper_id,
	o.oper_name,
	e.pay_way,
	y.pay_name as pay_name,
	e.sale_way,
	q.code_name as sale_name,
	e.sum_amt as pay_amount,
	e.convert_amt as convert_amt
    FROM
                (
                    SELECT
                            d.branch_no,
			d.pos_id,
			d.oper_id,
			d.pay_way,
			d.sale_way,
			d.sum_amt,
			d.convert_amt
                        FROM
                             (
                                    SELECT
                                            b.branch_no,
					b.pos_id,
					b.oper_id,
					b.pay_way,
					b.sale_way,
					b.sum_amt,
					b.sum_amt AS convert_amt
                                    FROM
                                                (
                                                    SELECT
                                                            p.branch_no,
							p.pos_id,
							p.oper_id,
							p.pay_way,
							p.sale_way,
							sum(p.convert_amt) sum_amt
                                                    FROM
                                                            {$prefix}pos_payflow   p
                                                    " . $where1 . "
                                                    GROUP BY
                                                            p.branch_no,
							p.pos_id,
							p.oper_id,
							p.pay_way,
							p.sale_way
					)       b
                            )   d
                    UNION
                            (
                                    SELECT
                                            a.branch_no,
					a.pos_id,
					a.oper_id,
					'RMB' AS pay_way,
					a.sell_way AS sale_way,
					sum(a.giv_money) AS sum_amt,
					sum(a.giv_money) AS convert_amt
                                    FROM
                                            (
                                                    SELECT
                                                            s.branch_no,
							s.pos_id,
							s.oper_id,
							s.sell_way AS sell_way,
							sum(
								CASE
                                                                    WHEN s.sell_way = 'C' THEN
                                                                            s.unit_price * s.sale_qnty
                                                                    ELSE
                                                                            0
                                                                    END
                                                            )   AS giv_money
                                                     FROM
                                                                {$prefix}pos_saleflow s
						" . $where3 . "
                                                        AND s.sell_way = 'C'
                                                        GROUP BY
                                                            s.branch_no,
							s.pos_id,
							s.oper_id,
							s.sell_way
                                            )    AS  a group by a.branch_no,
					a.pos_id,
					a.oper_id,
					'RMB',
					a.sell_way 
                            )
            )   e
    LEFT JOIN {$prefix}pos_branch_info i ON e.branch_no = i.branch_no
    LEFT JOIN {$prefix}pos_operator o ON e.oper_id = o.oper_id
    LEFT JOIN {$prefix}bd_base_code q ON e.sale_way = q.code_id
    AND q.type_no = 'SY'
    LEFT JOIN {$prefix}bd_payment_info y ON e.pay_way = y.pay_way
    AND y.pay_flag = '0'" . $where2;
        
        $list = Db::query($sql);
        $rowIndex = 0;
        foreach ($list as $v) {
            $tt = array();
            if ($v["sale_way"] != "D") {
                $tt["rowIndex"] = $rowIndex + 1;
                $tt["branch_no"] = $v["branch_no"];
                $tt["branch_name"] = $v["branch_name"];
                $tt["oper_id"] = $v["oper_id"];
                $tt["oper_name"] = $v["oper_name"];
                $tt["pos_id"] = $v["pos_id"];
                $tt["pay_name"] = $v["pay_name"];
                $tt["sale_name"] = $v["sale_name"];
                $tt["pay_amount"] = "";
                $tt["convert_amt"] = "";
                if ($v["pay_way"] == "RMB" && $v["sale_way"] == "A") {
                    $charge = $this->GetChargeAmt($list, $v["branch_no"], $v["pos_id"], $v["oper_id"]);
                    $tt["pay_amount"] = formatMoneyDisplay($v["pay_amount"] - $charge);
                    $tt["convert_amt"] = formatMoneyDisplay($v["convert_amt"] - $charge);
                } else {
                    if ($v["sale_way"] == "B") {
                        $tt["pay_amount"] = formatMoneyDisplay(-$v["pay_amount"]);
                        $tt["convert_amt"] = formatMoneyDisplay(-$v["convert_amt"]);
                    } else {
                        $tt["pay_amount"] = formatMoneyDisplay($v["pay_amount"]);
                        $tt["convert_amt"] = formatMoneyDisplay($v["convert_amt"]);
                    }
                }
                $footer1["pay_amount"] +=doubleval($tt["pay_amount"]);
                $footer1["convert_amt"] +=doubleval($tt["convert_amt"]);
                $rowIndex++;
                array_push($result, $tt);
            }
        }
        return $result;
    }

    private function GetChargeAmt($arr, $branch_no, $pos_id, $oper_id) {
        $charge = 0;
        foreach ($arr as $v) {
            if ($v["branch_no"] == $branch_no && $v["pos_id"] == $pos_id && $v["oper_id"] == $oper_id && $v["sale_way"] == "D") {
                $charge = $v["pay_amount"];
            }
        }
        return $charge;
    }

    public $pay_sum_amt;
    public $pay_rmb_amt;
    public $pay_crd_amt;
    public $pay_card_amt;
    public $pay_cha_amt;
	public $pay_wx_amt;

    public function SearchModelsForDayReport($branch_no, $oper_id, $start, $end, $page, $rows) {
       
        $condition = "";
        if (!empty($start) || !empty($branch_no) || !empty($oper_id)) {
            $condition = $condition . " where ";
        }
        $res = 0;
        if (!empty($start) && !empty($end)) {
            $res = 1;
            $condition = $condition . "    p.oper_date >= '$start'  and p.oper_date < '$end'";
        }
        if (!empty($branch_no)) {
            if ($res == 1) {
                $condition = $condition . "  and  p.branch_no = '$branch_no'";
            } else {
                $condition = $condition . "    p.branch_no = '$branch_no'";
            }
            $res = 1;
        }
        if (!empty($oper_id)) {
            if ($res == 1) {
                $condition = $condition . "  and  p.oper_id = '$oper_id'";
            } else {
                $condition = $condition . "    p.oper_id = '$oper_id'";
            }
        }
        $sql = "
SELECT
e.branch_no,
f.branch_name,
e.oper_id,
g.oper_name,
e.pos_id,
e.pay_amt as pay_sum_amt,
e.pay_giv_amt,
e.pay_rmb_amt,
e.pay_crd_amt,
e.pay_card_amt,
e.pay_cha_amt,
e.pay_zfb_amt,
e.pay_wx_amt
FROM
(
SELECT 
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
d.pay_cha_amt as pay_cha_amt,
d.pay_zfb_amt as pay_zfb_amt,
d.pay_wx_amt as pay_wx_amt
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
b.pay_rmb_amt-b.pay_rmb_ret as pay_rmb_amt,
b.pay_crd_amt,
b.pay_card_amt,
b.pay_cha_amt-b.pay_cha_ret as pay_cha_amt,
b.pay_zfb_amt-b.pay_zfb_ret as pay_zfb_amt,
b.pay_wx_amt-b.pay_wx_ret as pay_wx_amt
FROM 
(
SELECT p.branch_no,p.pos_id,sum(p.convert_amt) sum_amt,p.oper_id,
sum(case when p.sale_way='A' then p.convert_amt  else 0 end) as pay_amt,
sum(case when p.sale_way='B' then p.convert_amt else 0 end) as rete_amt,
sum(case when p.sale_way='D' then p.convert_amt else 0 end) as give_amt,
sum(case when p.sale_way='A'  then 
  case when p.pay_way='RMB'  then p.convert_amt else 0 end else 0 end) as pay_rmb_amt,
sum(case when p.sale_way='A'  then 
  case when p.pay_way='CRD'  then p.convert_amt else 0 end else 0 end) as pay_crd_amt,
sum(case when p.sale_way='A'  then 
  case when p.pay_way='HF'  then p.convert_amt else 0 end else 0 end) as pay_card_amt,
  sum(case when p.sale_way='A'  then 
  case when p.pay_way='ZFB'  then p.convert_amt else 0 end else 0 end) as pay_zfb_amt,
  sum(case when p.sale_way='B'  then 
  case when p.pay_way='RMB'  then p.convert_amt else 0 end else 0 end) as pay_rmb_ret,
  sum(case when p.sale_way='A'  then 
  case when p.pay_way='CHA'  then p.convert_amt else 0 end else 0 end) as pay_cha_amt,
  sum(case when p.sale_way='B'  then 
  case when p.pay_way='CHA'  then p.convert_amt else 0 end else 0 end) as pay_cha_ret,
  sum(case when p.sale_way='B'  then 
  case when p.pay_way='ZFB'  then p.convert_amt else 0 end else 0 end) as pay_zfb_ret,
  sum(case when p.sale_way='A'  then 
  case when p.pay_way='WX'  then p.convert_amt else 0 end else 0 end) as pay_wx_amt,
  sum(case when p.sale_way='B'  then 
  case when p.pay_way='WX'  then p.convert_amt else 0 end else 0 end) as pay_wx_ret
  
FROM ".$this->prefix."pos_payflow p " . $condition . "
group by p.branch_no,p.pos_id,p.oper_id
) b ) d ) e LEFT JOIN " . $this->prefix."pos_branch_info" . " as f on e.branch_no=f.branch_no " .
                "   LEFT JOIN " . $this->prefix."pos_operator" . " as g on e.oper_id=g.oper_id";
        $result1 = Db::query($sql);
        $result = array();
        $result["total"] = count($result1);
        $res = array();
        $rowIndex = 1;
        foreach ($result1 as $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["pay_rmb_amt"] = "";
            $tt["oper_id"] = $v["oper_id"];
            $tt["pos_id"] = $v["pos_id"];
            $tt["oper_name"] = $v["oper_name"];
            $tt["branch_no"] = $v["branch_no"];
            $tt["branch_name"] = $v["branch_name"];
            $tt["pay_giv_amt"] = sprintf("%.2f", doubleval($v["pay_giv_amt"]));
            $tt["pay_card_amt"] = sprintf("%.2f", doubleval($v["pay_card_amt"]));
            $tt["pay_rmb_amt"] = sprintf("%.2f", doubleval($v["pay_rmb_amt"]));
            $tt["pay_crd_amt"] = sprintf("%.2f", doubleval($v["pay_crd_amt"]));
            $tt["pay_sum_amt"] = sprintf("%.2f", doubleval($v["pay_sum_amt"]));
            $tt["pay_cha_amt"] = sprintf("%.2f", doubleval($v["pay_cha_amt"]));
            $tt["pay_zfb_amt"] = sprintf("%.2f", doubleval($v["pay_zfb_amt"]));
			$tt["pay_wx_amt"] = sprintf("%.2f", doubleval($v["pay_wx_amt"]));
            $rowIndex++;
            array_push($res, $tt);
        }
        $result["rows"] = $res;
        return $result;
    }


    public function UpdatePaywayForPos($flow_no, $flow_id, $pay_way, $pay_name, $pay_amount) {
        $result = 0;
        try {
            $model = $this->where("flow_no='$flow_no' and flow_id='$flow_id'")->find();
            if (empty($model)) {
                $result = -1;
            } else {
                $model->pay_way = $pay_way;
                $model->pay_name = $pay_name;
                $model->coin_type = $pay_way;
                $model->pay_amount = $pay_amount;
                $model->convert_amt = $pay_amount;
                if ($model->save()) {
                    $result = 1;
                } else {
                    $result = 0;
                }
            }
        } catch (\Exception $ex) {
            $result = -2;
        }
        return $result;
    }

}
