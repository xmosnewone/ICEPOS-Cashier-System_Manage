<?php
//pos_saleflow表
namespace model;
use think\Db;

class PosSaleFlow extends BaseModel {

	protected $pk='id';
	protected $name="pos_saleflow";
	public $branch_name;
	public $sp_name;
	public $unit_no;
	public $item_size;
	public $unit_money;
	public $in_money;
	public $item_clsname;
	public $item_brandname;
	public $oper_name;
	public $vip_no;
	public $memo;
	public $rowIndex;
	public $zk;
	public $brand_name;
	public $ret_qnty;
	public $ret_money;
	public $giv_qnty;
	public $giv_money;
	public $rl_money;
	public $sum_qnty;
	public $sum_money;
	public $old_sale_money;
	
    public function getall($content) {
        
        $where=[];
    	if ($content['flow_no'] != '') {
            $where['flow_no']=$content['flow_no'];
        }
        if ($content['item_no'] != '') {
            $where['item_no']=$content['item_no'];
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

    public function add($model) {
        try {
            if ($model->save()) {
                return TRUE;
            } else {
                return FALSE;
            }
        } catch (\Exception $ex) {
            return FALSE;
        }
    }

    public function GetModelsForDaySum($start, $end) {
        $condtion = "oper_date >= '$start' and oper_date < '$end' and over_flag='0'";
        return $this->where($condtion)->select();
    }

    public function GetModelsForVoucher($flowno) {

        $temp=$this
	        ->alias("s")
	        ->field("s.item_no,s.sale_price,s.item_name,s.sale_qnty,s.sale_money")
	        ->where(["s.flow_no"=>$flowno])
	        ->select();
        
        $result = array();
        foreach ($temp as $k => $v) {
            $tt = array();
            $tt["item_no"] = $v["item_no"];
            $tt["item_name"] = $v["item_name"];
            $tt["sale_price"] = formatMoneyDisplay($v["sale_price"]);
            $tt["sale_qnty"] = formatMoneyDisplay($v["sale_qnty"]);
            $tt["sale_money"] = formatMoneyDisplay($v["sale_money"]);
            array_push($result, $tt);
        }
        return $result;
    }

    public function SearchModelsForList($start, $end, $supcust_no, $branch_no, $pos_id, $oper_id, $item_no, $item_name, $item_clsno, $item_brand, $flow_no, $vip_no, $sale_way, $page, $rows) {
       
        $where="d.type_no='PP'";
        
        if (!empty($start) && !empty($end)) {
            if ($start == $end) {
                $where.=" and date(s.oper_date) = '$start'";
            } else {
                $end = date('Y-m-d', strtotime('+1 day', strtotime($end)));
                $where.=" and s.oper_date >= '$start' and s.oper_date < '$end'";
            }
        }
        if (!empty($branch_no)) {
            $where.=" and s.branch_no = '$branch_no'";
        }
        if (!empty($supcust_no)) {
            $where.=" and h.supcust_no = '$supcust_no'";
        }
        if (!empty($pos_id)) {
            $where.=" and s.pos_id = '$pos_id'";
        }
        if (!empty($oper_id)) {
            $where.=" and s.oper_id = '$oper_id'";
        }
        if (!empty($item_no)) {
            $where.=" and s.item_no = '$item_no'";
        }
        if (!empty($item_name)) {
            $where.=" and s.item_name like '%$item_name%'";
        }
        if (!empty($item_clsno)) {
            $where.=" and s.item_clsno like '$item_clsno%'";
        }
        if (!empty($item_brand)) {
            $where.=" and s.item_brand = '$item_brand' ";
        }
        if (!empty($flow_no)) {
            $where.=" and s.flow_no like '%$flow_no%'";
        }
        if (!empty($vip_no)) {
            $where.=" and g.vip_no like '%$vip_no%'";
        }
        if (!empty($sale_way)) {
            $where.=" and s.sell_way = '$sale_way'";
        }
        
        $order = "s.oper_date desc";
        $offset = ($page - 1) * $rows;
        
        $list=$this->alias("s")
        		->field("s.branch_no,a.branch_name,s.flow_no,s.oper_date,s.item_name," .
                "s.item_no,b.unit_no,b.item_size,b.price as cost_price," .
                 "ROUND(s.sale_price/s.unit_price,4)*100 as zk," .
                "case s.sell_way when 'A' then '销售' when 'B' then '退货' when 'C' then '赠送' else '找零' end as sell_way," .
                "s.sale_qnty,s.sale_price,s.sale_money,s.unit_price,(s.unit_price*s.sale_qnty) as unit_money," .
                "s.in_price,(s.in_price*s.sale_qnty) as in_money,s.item_subno,s.item_clsno,c.item_clsname," .
                "s.item_brand,d.code_name as item_brandname,'' as sp_name,s.pos_id,s.oper_id,e.oper_name," .
                'g.card_no as vip_no,g.score as memo,s.discount_rate')
        		->join("pos_branch_info a","s.branch_no=a.branch_no","LEFT")
        		->join("bd_item_info b","s.item_no= b.item_no","LEFT")
        		->join("bd_item_cls c","s.item_clsno=c.item_clsno","LEFT")
        		->join("bd_base_code d","s.item_brand= d.code_id","LEFT")
        		->join("pos_operator e","s.oper_id = e.oper_id","LEFT")
        		->join("pos_viplist g","s.flow_no= g.flow_no","LEFT")
        		->limit($offset,$rows)
        		->order($order)
        		->where($where)
        		->select();
        $rowCount =$this->alias("s")
        		->join("pos_branch_info a","s.branch_no=a.branch_no","LEFT")
        		->join("bd_item_info b","s.item_no= b.item_no","LEFT")
        		->join("bd_item_cls c","s.item_clsno=c.item_clsno","LEFT")
        		->join("bd_base_code d","s.item_brand= d.code_id","LEFT")
        		->join("pos_operator e","s.oper_id = e.oper_id","LEFT")
        		->join("pos_viplist g","s.flow_no= g.flow_no","LEFT")
        		->where($where)
        		->count();
        
        $result = array();
        $result["total"] = $rowCount;
        $temp = array();
        $vip_code=array();
        $footer = array();
        $footer_detail = array();
        $rowIndex = ($page - 1) * $rows + 1;
        $footer_detail["branch_no"] = "合计:";
        $footer_detail["flow_no"] = "";
        foreach ($list as $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["branch_no"] = $v["branch_no"];
            $tt["branch_name"] = $v["branch_name"];
            $tt["flow_no"] = $v["flow_no"];
            $tt["oper_date"] = $v["oper_date"];
            $tt["item_name"] = $v["item_name"];
            $tt["item_no"] = $v["item_no"];
            $tt["unit_no"] = $v["unit_no"];
            $tt["item_size"] = $v["item_size"];
            $tt["sell_way"] = $v["sell_way"];
            $tt["sale_qnty"] = formatMoneyDisplay($v["sale_qnty"]);
            $footer_detail["sale_qnty"]+=doubleval($v["sale_qnty"]);
            $tt["sale_price"] = formatMoneyDisplay($v["sale_price"]);
            $tt["sale_money"] = formatMoneyDisplay($v["sale_money"]);
            $footer_detail["sale_money"]+=doubleval($v["sale_money"]);
            $tt["unit_price"] = formatMoneyDisplay($v["unit_price"]);
            $tt["unit_money"] = formatMoneyDisplay($v["unit_money"]);
            $footer_detail["unit_money"]+=doubleval($v["unit_money"]);
            $tt["in_price"] = formatMoneyDisplay($v["in_price"]);
            $tt["zk"] = formatMoneyDisplay($v["zk"]) . "%";
            $tt["in_money"] = formatMoneyDisplay($v["in_money"]);
            $footer_detail["in_money"]+=doubleval($v["in_money"]);
            $tt["item_subno"] = $v["item_subno"];
            $tt["item_clsno"] = $v["item_clsno"];
            $tt["item_clsname"] = $v["item_clsname"];
            $tt["item_brand"] = $v["item_brand"];
            $tt["item_brandname"] = $v["item_brandname"];
            $tt["sp_name"] = $v["sp_name"];
            $tt["pos_id"] = $v["pos_id"];
            $tt["oper_id"] = $v["oper_id"];
            $tt["oper_name"] = $v["oper_name"];
            $tt["vip_no"] = $v["vip_no"];
            $tt["nickname"] = $v["vip_no"];
            $tt["mobile"] = '';
            /*if(floatval($v["in_price"])<=0&&$v["sell_way"]!='赠送'){
                $tt["in_price"] = formatMoneyDisplay($v["cost_price"]);
                $tt["in_money"] = formatMoneyDisplay($v["cost_price"]*$v["sale_qnty"]);
                $footer_detail["in_money"]+=doubleval($v["cost_price"]*$v["sale_qnty"]);
            }*/
            if(!empty($v["vip_no"])){
                $vip_code[]=trim($v["vip_no"]);
            }
            if (!empty($tt["vip_no"])) {
                $tt["memo"] = "本次积分:" . formatMoneyDisplay($v["memo"]);
            } else {
                $tt["memo"] = $v["memo"];
            }
            $tt["discount_rate"] = $v["discount_rate"];
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


    public function Summary($start, $end, $branch_no, $item_no, $item_clsno, $supcust_no, $item_subno, $item_brand, $summary_type, $page, $rows, $txtBranchNo='',$order="") {
        if ($summary_type == 1) {
            return $this->BranchItemSummary($start, $end, $branch_no, $item_no, $item_clsno, $supcust_no, $item_subno, $item_brand, $page, $rows, $txtBranchNo);
        } else {
            return $this->SumarrayByDemension($start, $end, $branch_no, $item_no, $item_clsno, $supcust_no, $item_subno, $item_brand, $page, $rows, $summary_type,$order);
        }
    }

    private function BranchItemSummary($start, $end, $branch_no, $item_no, $item_clsno, $supcust_no, $item_subno, $item_brand, $page, $rows, $txtBranchNo='') {
        $result = array();
            
        $where="1=1";
        $group = "s.branch_no,c.branch_name,a.item_no, a.item_name, a.item_clsno, b.item_clsname, " .
                "a.item_brand,  d.code_name, a.unit_no , a.item_size ,  s.unit_price,  s.sale_price";
        
        if (!empty($start) && !empty($end)) {
        	if ($start == $end) {
        		$where.=" and date(s.oper_date)='$start'";
        	} else {
        		$end = date(DATE_FORMAT, strtotime("+1 day", strtotime($end)));
        		$where.=" and s.oper_date >= '$start' and s.oper_date < '$end'";
        	}
        }
        if (!empty($branch_no)) {
        	$where.=" and s.branch_no = '$branch_no'";
        }
        if (!empty($item_no)) {
        	$where.=" and s.item_no like '%$item_no%'";
        }
        if (!empty($item_subno)) {
        	$where.=" and a.item_subno like '%$item_subno%'";
        }
        if (!empty($item_clsno)) {
        	$where.=" and a.item_clsno = '$item_clsno'";
        }
        if (!empty($item_brand)) {
        	$where.=" and a.item_brand = '$item_brand'";
        }
        if (!empty($supcust_no)) {
        	$where.=" and a.main_supcust = '$supcust_no'";
        }
        if (!empty($txtBranchNo)) {
        	$where.=" and s.branch_no='$txtBranchNo'";
        }
        
        
/*         $all = 	$this
            		->alias("s")
            		->field("s.branch_no,c.branch_name,a.item_no, a.item_name, a.item_clsno, " .
	                "b.item_clsname,a.item_brand,d.code_name as brand_name, a.unit_no ,a.item_size ," .
	                "s.unit_price, s.sale_price," .
	                "ROUND(s.sale_price/s.unit_price,4)*100 as zk," .
	                "SUM(case s.sell_way when 'A' then s.sale_qnty else 0 end) as sale_qnty, " .
	                "SUM(case s.sell_way when 'A' then s.sale_money else 0 end) as sale_money," .
	                "SUM( case s.sell_way when 'B' then s.sale_qnty else 0 end) as ret_qnty," .
	                "SUM(case s.sell_way when 'B' then s.sale_money else 0 end) as ret_money," .
	                "SUM(case s.sell_way when 'C' then s.sale_qnty else 0 end) as giv_qnty," .
	                "SUM(case s.sell_way when 'C' then s.unit_price*s.sale_qnty else 0 end) as giv_money," .
	                "SUM(case s.sell_way when 'C' then s.unit_price*s.sale_qnty  " .
	                " when 'A' then (s.unit_price-s.sale_price)*s.sale_qnty else 0 end) as rl_money, " .
	                "SUM(case s.sell_way when 'B' then -s.sale_qnty  else s.sale_qnty end ) as sum_qnty, " .
	                "SUM(case s.sell_way when 'B' then -s.sale_money else s.sale_money end ) as sum_money")
	            	->join("bd_item_info a","s.item_no = a.item_no","LEFT")
	            	->join("bd_item_cls b","a.item_clsno = b.item_clsno","LEFT")
	            	->join("pos_branch_info c","s.branch_no = c.branch_no","LEFT")
	            	->join("bd_base_code d","a.item_brand = d.code_id","LEFT")
	            	->group($group)
            		->where($where)
            		->select(); */
            
        $rowCount = $this
            		->alias("s")
            		->join("bd_item_info a","s.item_no = a.item_no","LEFT")
	            	->join("bd_item_cls b","a.item_clsno = b.item_clsno","LEFT")
	            	->join("pos_branch_info c","s.branch_no = c.branch_no","LEFT")
	            	->join("bd_base_code d","a.item_brand = d.code_id","LEFT")
	            	->group($group)
            		->where($where)
            		->count();  
        
        $offset = ($page - 1) * $rows;
        $temp = $this
            		->alias("s")
            		->field("s.branch_no,c.branch_name,a.item_no, a.item_name, a.item_clsno, " .
	                "b.item_clsname,a.item_brand,d.code_name as brand_name, a.unit_no ,a.item_size ," .
	                "s.unit_price, s.sale_price," .
	                "ROUND(s.sale_price/s.unit_price,4)*100 as zk," .
	                "SUM(case s.sell_way when 'A' then s.sale_qnty else 0 end) as sale_qnty, " .
	                "SUM(case s.sell_way when 'A' then s.sale_money else 0 end) as sale_money," .
	                "SUM( case s.sell_way when 'B' then s.sale_qnty else 0 end) as ret_qnty," .
	                "SUM(case s.sell_way when 'B' then s.sale_money else 0 end) as ret_money," .
	                "SUM(case s.sell_way when 'C' then s.sale_qnty else 0 end) as giv_qnty," .
	                "SUM(case s.sell_way when 'C' then s.unit_price*s.sale_qnty else 0 end) as giv_money," .
	                "SUM(case s.sell_way when 'C' then s.unit_price*s.sale_qnty  " .
	                " when 'A' then (s.unit_price-s.sale_price)*s.sale_qnty else 0 end) as rl_money, " .
	                "SUM(case s.sell_way when 'B' then -s.sale_qnty  else s.sale_qnty end ) as sum_qnty, " .
	                "SUM(case s.sell_way when 'B' then -s.sale_money else s.sale_money end ) as sum_money")
	            	->join("bd_item_info a","s.item_no = a.item_no","LEFT")
	            	->join("bd_item_cls b","a.item_clsno = b.item_clsno","LEFT")
	            	->join("pos_branch_info c","s.branch_no = c.branch_no","LEFT")
	            	->join("bd_base_code d","a.item_brand = d.code_id","LEFT")
	            	->group($group)
            		->where($where)
            		->limit($offset,$rows)
            		->select();
        $result["total"] = $rowCount;
        $res = array();
        $rowIndex = ($page - 1) * $rows + 1;
        foreach ($temp as $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["branch_no"] = $v["branch_no"];
            $tt["branch_name"] = $v["branch_name"];
            $tt["item_no"] = $v["item_no"];
            $tt["item_name"] = $v["item_name"];
            $tt["item_clsno"] = $v["item_clsno"];
            $tt["item_clsname"] = $v["item_clsname"];
            $tt["item_brand"] = $v["item_brand"];
            $tt["brand_name"] = $v["brand_name"];
            $tt["unit_no"] = $v["unit_no"];
            $tt["item_size"] = $v["item_size"];
            $tt["unit_price"] = formatMoneyDisplay($v["unit_price"]);
            $tt["sale_price"] = formatMoneyDisplay($v["sale_price"]);
            $tt["zk"] = formatMoneyDisplay($v["zk"]) . "%";
            $tt["sale_qnty"] = formatMoneyDisplay($v["sale_qnty"]);
            $tt["sale_money"] = formatMoneyDisplay($v["sale_money"]);
            $tt["ret_qnty"] = formatMoneyDisplay($v["ret_qnty"]);
            $tt["ret_money"] = formatMoneyDisplay($v["ret_money"]);
            $tt["giv_qnty"] = formatMoneyDisplay($v["giv_qnty"]);
            $tt["giv_money"] = formatMoneyDisplay($v["giv_money"]);
            $tt["rl_money"] = formatMoneyDisplay($v["rl_money"]);
            $tt["sum_qnty"] = formatMoneyDisplay($v["sum_qnty"]);
            $tt["sum_money"] = formatMoneyDisplay($v["sum_money"]);
            $rowIndex++;
            array_push($res, $tt);
        }
        $result["rows"] = $res;
        /* $footers = array();
        array_push($footers, $footer);
        $result["footer"] = $footers; */
        return $result;
    }

    private function SumarrayByDemension($start, $end, $branch_no, $item_no, $item_clsno, $supcust_no, $item_subno, $item_brand, $page, $rows, $summary_type,$order='') {
        $result = array();
        $sql="";
        $demension = "";
        $join = "";
        $group = "";
        
        $where=" where 1=1";
        if (!empty($start) && !empty($end)) {
        	if ($start == $end) {
        		$where.=" and date(s.oper_date)='$start'";
        	} else {
        		$end = date('Y-m-d', strtotime("+1 day", strtotime($end)));
        		$where.=" and s.oper_date >= '$start' and s.oper_date < '$end'";
        	}
        }
        if (!empty($branch_no)) {
        	$where.=" and s.branch_no = '$branch_no'";
        }
        if (!empty($item_no)) {
        	$where.=" and s.item_no like '%$item_no%'";
        }
        if (!empty($item_subno)) {
        	$where.=" and a.item_subno like '%$item_subno%'";
        }
        if (!empty($item_clsno)) {
        	$where.=" and a.item_clsno = '$item_clsno'";
        }
        if (!empty($item_brand)) {
        	$where.=" and a.item_brand = '$item_brand'";
        }
        if (!empty($supcust_no)) {
        	$where.=" and a.main_supcust = '$supcust_no'";
        }
        
        //@hj修改
        if($order!=''){
        	$where.=" and a.item_no is not null";
        }
        
        switch ($summary_type) {
            case 2:
                $demension = "s.branch_no,c.branch_name,a.item_clsno,b.item_clsname,";
                $join = " LEFT JOIN " . $this->prefix."bd_item_info" . " as a ON s.item_no = a.item_no " .
                        " LEFT JOIN " . $this->prefix."bd_item_cls" . " as b ON a.item_clsno = b.item_clsno " .
                        " LEFT JOIN " . $this->prefix."pos_branch_info" . " as c ON s.branch_no = c.branch_no ";
                $group = "s.branch_no,c.branch_name, a.item_clsno,b.item_clsname";
                break;
            case 3:
                $demension = "s.branch_no,c.branch_name,a.item_brand,b.code_name as brand_name,";
                $join = " LEFT JOIN " . $this->prefix."bd_item_info" . " as a ON s.item_no = a.item_no " .
                        " LEFT JOIN " . $this->prefix."bd_base_code" . " as b ON a.item_brand = b.code_id " .
                        " LEFT JOIN	" . $this->prefix."pos_branch_info" . " as c ON s.branch_no = c.branch_no";
                $group = "s.branch_no,c.branch_name, a.item_brand,b.code_name ";
                $where.=" and b.type_no='PP'";
                break;
            case 4:
                $demension = "a.item_no,a.item_name,a.item_clsno,b.item_clsname,a.item_brand,d.code_name as brand_name,a.unit_no,a.item_size,";
                $join = " LEFT JOIN " . $this->prefix."bd_item_info" . " as a ON s.item_no = a.item_no " .
                        " LEFT JOIN " . $this->prefix."bd_item_cls"  . " as b ON a.item_clsno = b.item_clsno " .
                        " LEFT JOIN " . $this->prefix."bd_base_code" . " as d ON a.item_brand = d.code_id";
                $group = "a.item_no, a.item_name, a.item_clsno, b.item_clsname, a.item_brand,d.code_name,a.unit_no ,a.item_size ";
                $where.=" and d.type_no='PP'";
                break;
            case 5:
                $demension = "s.branch_no,c.branch_name,b.item_clsno,b.item_clsname,";
                $join = " LEFT JOIN " . $this->prefix."bd_item_info" . " a ON s.item_no = a.item_no " .
                        " LEFT JOIN " . $this->prefix."bd_item_cls"  . " b ON left(a.item_clsno,2) = b.item_clsno " .
                        " LEFT JOIN " . $this->prefix."pos_branch_info" . " c ON s.branch_no = c.branch_no ";
                $group = "s.branch_no, c.branch_name, b.item_clsno, b.item_clsname ";
                $where.=" and b.cls_parent=''";
                break;
        }
        $field= $demension . "SUM(case s.sell_way when 'A' then s.sale_qnty else 0 end) as sale_qnty, " .
                "SUM(case s.sell_way when 'A' then s.sale_money else 0 end) as sale_money, " .
                "SUM( case s.sell_way when 'B' then s.sale_qnty else 0 end) as ret_qnty, " .
                "SUM(case s.sell_way when 'B' then s.sale_money else 0 end) as ret_money, " .
                "SUM( case s.sell_way when 'C' then s.sale_qnty else 0 end) as giv_qnty, " .
                "SUM(case s.sell_way when 'C' then s.unit_price*s.sale_qnty else 0 end) as giv_money,  " .
                "SUM(case s.sell_way when 'B' then -(s.unit_price*s.sale_qnty) " .
                "  else s.unit_price*s.sale_qnty END) as old_sale_money, " .
                "  SUM(case s.sell_way when 'C' then s.unit_price*s.sale_qnty " .
                "  when 'A' then (s.unit_price-s.sale_price)*s.sale_qnty else 0 end) as rl_money, " .
                " SUM(case s.sell_way when 'B' then -s.sale_qnty  else s.sale_qnty end ) as sum_qnty, " .
                " SUM(case s.sell_way when 'B' then -s.sale_money else s.sale_money end ) as sum_money ";
        
        if($group!=''){
        	$group=" group by ".$group;
        }
        $sql="select ".$field." from ".$this->table." as s ".$join.$where.$group;
        //$all =Db::query($sql);
        
        $sql1="select count(*) as total_num from (".$sql.") temptable";
        $count=Db::query($sql1);
        $rowCount=$count[0]['total_num'];
 
        $footer = array();
        if ($summary_type != 4) {
            //$tt["branch_no"] = "总计:";
            //$tt["branch_name"] = "";
            if ($summary_type == "2" || $summary_type == "5") {
                //$footer["item_clsno"] = "";
                //$footer["item_clsname"] = "";
            } else {
                //$footer["item_brand"] = "";
                //$footer["brand_name"] = "";
            }
        } else {
            /* $footer["item_no"] = "总计:";
            $footer["item_name"] = "";
            $footer["item_clsno"] = "";
            $footer["item_clsname"] = "";
            $footer["item_brand"] = "";
            $footer["brand_name"] = "";
            $footer["unit_no"] = "";
            $footer["item_size"] = ""; */
        }
        /* foreach ($all as $v) {
            $footer["old_sale_money"] += doubleval($v["old_sale_money"]);
            $footer["sale_qnty"] +=doubleval($v["sale_qnty"]);
            $footer["sale_money"] += doubleval($v["sale_money"]);
            $footer["ret_qnty"] += doubleval($v["ret_qnty"]);
            $footer["ret_money"] += doubleval($v["ret_money"]);
            $footer["giv_qnty"] += doubleval($v["giv_qnty"]);
            $footer["giv_money"] +=doubleval($v["giv_money"]);
            $footer["rl_money"] += doubleval($v["rl_money"]);
            $footer["sum_qnty"] += doubleval($v["sum_qnty"]);
            $footer["sum_money"] +=doubleval($v["sum_money"]);
        } */
        
        $offset = ($page - 1) * $rows;
       
        $ordersql='';
        if($order!=''){
        	$ordersql=" order by ".$order;
        }
        $sql="select ".$field." from ".$this->table." as s ".$join.$where.$group.$ordersql;
        $sql.=" limit ".$offset.",".$rows;
        
        $temp = Db::query($sql);
        
        $result["total"] = $rowCount;
        $res = array();
        $rowIndex = ($page - 1) * $rows + 1;
        foreach ($temp as $v) {
            $tt = array();
            if ($summary_type != 4) {
                $tt["branch_no"] = $v["branch_no"];
                $tt["branch_name"] = $v["branch_name"];
                if ($summary_type == "2" || $summary_type == "5") {
                    $tt["item_clsno"] = $v["item_clsno"];
                    $tt["item_clsname"] = $v["item_clsname"];
                } else {
                    $tt["item_brand"] = $v["item_brand"];
                    $tt["brand_name"] = $v["brand_name"];
                }
            } else {
                $tt["item_no"] = $v["item_no"];
                $tt["item_name"] = $v["item_name"];
                $tt["item_clsno"] = $v["item_clsno"];
                $tt["item_clsname"] = $v["item_clsname"];
                $tt["item_brand"] = $v["item_brand"];
                $tt["brand_name"] = $v["brand_name"];
                $tt["unit_no"] = $v["unit_no"];
                $tt["item_size"] = $v["item_size"];
            }
            $tt["rowIndex"] = $rowIndex;
            $tt["sale_qnty"] = formatMoneyDisplay($v["sale_qnty"]);
            $tt["sale_money"] = formatMoneyDisplay($v["sale_money"]);
            $tt["ret_qnty"] = formatMoneyDisplay($v["ret_qnty"]);
            $tt["ret_money"] = formatMoneyDisplay($v["ret_money"]);
            $tt["giv_qnty"] = formatMoneyDisplay($v["giv_qnty"]);
            $tt["giv_money"] = formatMoneyDisplay($v["giv_money"]);
            $tt["rl_money"] = formatMoneyDisplay($v["rl_money"]);
            $tt["sum_qnty"] = formatMoneyDisplay($v["sum_qnty"]);
            $tt["sum_money"] = formatMoneyDisplay($v["sum_money"]);
            $tt["old_sale_money"] = formatMoneyDisplay($v["old_sale_money"]);
            $rowIndex++;
            array_push($res, $tt);
        }
        $result["rows"] = $res;
       /*  $footers = array();
        array_push($footers, $footer);
        $result["footer"] = $footers; */
        return $result;
    }

    public function GetIsFirstSaleForPos($vip_no, $item_no, $plan_no) {
        $result = 0;
        try {
            $model = $this
            ->alias("s")
            ->field("s.flow_no,s.item_no")
            		->join("pos_viplist a","s.flow_no=a.flow_no","LEFT")
            		->where(["s.item_no"=>$item_no,'s.plan_no'=>$plan_no,'s.card_no'=>$vip_no])
            		->select();
            
            if (!empty($model)) {
                $result = 1;
            } else {
                $result = 0;
            }
        } catch (\Exception $ex) {
            $result = -2;
        }
        return $result;
    }
    
    //080 分页数据返回
    public function GetPager($page,$rows,$start,$end,$item_no,$limit) {
    	
    	$where="1=1";
    	if(!empty($start)){
    		$where.=" and oper_date >= '$start'";
    	}
    	
    	if(!empty($end)){
    		$where.=" and oper_date <= '$end'";
    	}
    	
    	if(!empty($item_no)){
    		$where.=" and item_no like '%$item_no%'";
    	}
    	
    	$offset = ($page - 1) * $rows;
    	$total = $this->where($where)->count();
    	
    	if($limit){
    		$list = $this->where($where)->limit($offset,$rows)->select()->toArray();
    	}else{
    		$list = $this->where($where)->select()->toArray();
    	}
    	
    	return ['total'=>$total,"rows"=>$list];
    }

    public function GetLastFlow($branch_no) {
        $row=$this->where(["branch_no"=>$branch_no])
                    ->where('TO_DAYS(`oper_date`) = TO_DAYS(NOW())')
                    ->order("oper_date asc")->find();
        //$row=$this->where(["branch_no"=>'004'])->order("id desc")->find();
        $result=[];
        if($row!=false&&!empty($row)){
            $result=$row->toArray();
        }
        return $result;
    }

}
