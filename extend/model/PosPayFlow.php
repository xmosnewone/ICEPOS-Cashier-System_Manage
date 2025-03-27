<?php
//pos_payflow表
namespace model;
use think\Db;
use model\PosSaleFlow;
use model\BdItemCombsplit;
use model\PosBranchStock;
use model\PosPay;
use model\PosBranch;
use model\PosStatus;

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
                $coin_type=$pay->coin_type;
                //更新微信/支付宝等临时支付流水对应
                if($coin_type=="Wechat"||$coin_type=="ZFB"){
                    if(isset($payRelation[$pay->flow_no])){
                        $pflow_id=$payRelation[$pay->flow_no][$coin_type]['pflow_id'];//sale.db中t_app_payflow的pflow_id
                        $posPay=new PosPay();
                        $posPay->UpdatePosPayFlowno($pay->flow_no,$pay->id,$pflow_id);
                    }
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
        		->field("s.id,s.branch_no,s.flow_no,s.oper_date,s.coin_type,s.pay_name,s.pay_way," .
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
        $vip_code=array();
        $footer = array();
        $footer_detail = array();

        $footer_detail["branch_no"] = "合计:";
        $footer_detail["flow_no"] = "";
        $temp_flow = array();
        $rowIndex = ($page - 1) * $rows + 1;
        foreach ($list as $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["id"] = $v["id"];
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
            $tt["nickname"] = $v["vip_no"];
            $tt["mobile"] = '';
            if(!empty($v["vip_no"])){
                $vip_code[]=trim($v["vip_no"]);
            }
            $tt["sale_way"] = $v["sale_way"];
            $tt["pay_way"] = $v["pay_way"];
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
        if(count($vip_code)>0){
            $vip_code=array_unique($vip_code);
            $whereVip="ucode in (".simplode($vip_code).")";
            $members=Db::name("member")->where($whereVip)->field("uname,nickname,ucode,mobile")->select();
            $vips=array();
            if(is_array($members)&&count($members)>0){
                foreach($members as $val){
                    $vips[$val['ucode']]['nickname']=$val['nickname']!=''?$val['nickname']:$val['uname'];
                    $vips[$val['ucode']]['mobile']=$val['mobile'];
                }
                foreach($temp as $k=>$value){
                    $vip_no=$value['vip_no'];
                    if(isset($vips[$vip_no])){
                        $temp[$k]['nickname']=$vips[$vip_no]['nickname'];
                        $temp[$k]['mobile']=$vips[$vip_no]['mobile'];
                    }
                }
            }
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
        		->where($where)
        		->select();
        
        $rowCount=Db::name($this->name)
        		->alias('s')
        		->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        		->join('bd_base_code b','s.sale_way = b.code_id',"LEFT")
        		->join('pos_operator c','s.oper_id= c.oper_id',"LEFT")
        		->group("s.branch_no,a.branch_name,s.oper_id,c.oper_name,s.pay_way,s.sale_way," .
                "s.pos_id,s.pay_name,b.code_name")
        		->where($where)
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

    //读取用户订单信息
    public function getUserOrderCount($vip_no){
        return $this->where("vip_no='$vip_no'")->group("flow_no")->count();
    }

    //年内消费
    public function getUserYearConsume($vip_no){
        $now=time();
        $year=date("Y",$now);
        $year_start=$year."-01-01 00:00:00";
        $year_end=$year."-12-31 23:59:59";
        return $this->where("vip_no='$vip_no' and oper_date>='$year_start' and oper_date<='$year_end'")
            ->sum("pay_amount");
    }

    //分页获取个人订单列表
    public function getUserOrderList($vip_no,$status,$start,$limit){
        //查询所有门店
        $PosBranch=new PosBranch();
        $branchs=$PosBranch->GetAllBranchField("branch_no,branch_name,logo");
        $branchsList=array();
        foreach ($branchs as $v) {
            $branchsList[$v["branch_no"]]=$v;
        }

        $where="";
        if($status){
            switch ($status) {
                case 1:     //未支付==挂账
                    $where=" and pay_way='GZ' and sale_way='E'";
                    break;
                case 2:     //已退款
                    $where=" and order_refund=1 ";
                    break;
                case 3:     //已完成
                    $where=" and (sale_way='A' or sale_way='C') and order_refund=0 ";
                    break;
            }
        }
        //通过$start,$limit 分页定位到paly_flow表的的flow_no范围
        $pageList=$this->where("vip_no='$vip_no'".$where)
            ->order("oper_date desc")
            ->group("flow_no")
            ->limit($start,$limit)
            ->select();
        $listCount=$this->where("vip_no='$vip_no'".$where)
            ->order("oper_date desc")
            ->group("flow_no")
            ->count();

        $flow_no=array();
        if($pageList&&count($pageList)>0){
            $pageList=$pageList->toArray();
            foreach($pageList as $k=>$v){
                $no=trim($v['flow_no']);
                $flow_no[$no]=$no;
            }
        }else{
            return ['list'=>[],'total'=>$listCount];
        }

        unset($pageList);

        $flow_nos=simplode($flow_no);
        $consume=consume_payment();//用于统计支付总金额的支付方式
        $list=$this->where("vip_no='$vip_no' and flow_no in ($flow_nos)")
            ->order("oper_date desc,id asc")
            ->select()
            ->toArray();

        $flows=array();
        foreach ($list as $k=>$v){
            if(!isset($flows[$v['flow_no']])){
                $t=array();
                $payways=array();
                $memo=array();
                $amount_pay=0;
                $sale_qnty=0;
                $same_flow=array();
                //统计金额
                foreach($list as $k1=>$v1){
                    //统计需要支付的总金额
                    if($v1['flow_no']==$v['flow_no']){
                        $same_flow[]=$v1;
                        $amount_pay+=$v1['pay_amount'];
                        $payways[]=$v1['pay_name'];
                        $memo[]=$v1['memo'];
                    }
                }
                if(isset($branchsList[$v['branch_no']])){
                    $t['branch_no']=$v['branch_no'];
                    $t['branch_name']=$branchsList[$v['branch_no']]['branch_name'];
                    $t['branch_logo']=$branchsList[$v['branch_no']]['logo'];
                }else{
                    $t['branch_no']=$v['branch_no'];
                    $t['branch_name']="总店";
                    $t['branch_logo']="";
                }
                $t['flow_no']=$v['flow_no'];
                $t['sale_amount']=sprintf("%.2f", $v['sale_amount']);
                $t['pay_amount']=sprintf("%.2f", $amount_pay);
                $t['pay_way']=$payways;
                $t['oper_date']=$v['oper_date'];
                $t['oper_id']=$v['oper_id'];
                $t['pos_id']=$v['pos_id'];
                $t['refund_flag']=$v['refund_flag'];
                $t['order_status']=$this->OrderStatus($same_flow);

                //查询商品
                $salesInfo=$this->SalesInfo($v['flow_no']);
                $t['sales_info']=$salesInfo;
                foreach($salesInfo as $sv){
                    $sale_qnty+=$sv['sale_qnty'];
                }
                $t['sale_qnty']=$sale_qnty;
                //查询终端
                $PosStatus=$this->PosStatus($v['branch_no'],$v['pos_id']);
                $t['pos_status']=$PosStatus;

                $flows[$v['flow_no']]=$t;
            }
        }
        return ['list'=>$flows,'total'=>$listCount];
    }

    //根据单号，返回销售数据和商品信息
    public function SalesInfo($flow_no){
        $PosSaleFlow=new PosSaleFlow();
        $goods=$PosSaleFlow->alias("a")
            ->join("bd_item_info b","b.item_no=a.item_no","LEFT")
            ->field("a.flow_id,a.flow_no,a.unit_price,a.sale_price,a.sale_qnty,a.sale_money,a.in_price,a.sell_way,a.discount_rate,a.plan_no,b.item_no,b.item_name,b.img_src")
            ->where("a.flow_no='$flow_no'")
            ->select();
        if($goods&&count($goods)>0){
            $list=$goods->toArray();
            foreach($list as $k=>$v){
                $list[$k]['sale_price']=sprintf("%.2f",$v['sale_price']);
                $list[$k]['sale_qnty']=intval($v['sale_qnty']);
            }
            return $list;
        }
        return [];
    }

    //返回收银机类型
    private function PosStatus($branch_no,$pos_id){
        $PosStatus=new PosStatus();
        $pos=$PosStatus->field("posid,postype")->where("branch_no='$branch_no' and posid='$pos_id'")->find();
        if(!empty($pos)&&$pos!=null){
            return $pos;
        }
        return ['posid'=>'','postype'=>''];
    }

    //返回订单状态
    //$order 是支付流水记录
    public function OrderStatus($order){
        $totalRefund=0;
        $totalUnpay=0;
        foreach($order as $v){
            if($v['refund_flag']==1){//其中一个支付流水退款，但其他未退款==部分退款
                $totalRefund++;
            }
            if($v['pay_way']=='GZ'&&$v['sale_way']=='E'){
                $totalUnpay++;
            }
        }

        //退款判断
        if($totalRefund>0&&count($order)==$totalRefund){
            return '已退款';
        }elseif ($totalRefund>0&&count($order)>$totalRefund){
            return '部分退款';
        }

        //未支付
        if($totalUnpay>0){
            return '未支付';
        }

        return '已完成';
    }

}
