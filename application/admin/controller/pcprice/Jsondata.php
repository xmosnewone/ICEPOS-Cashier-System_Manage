<?php
namespace app\admin\controller\pcprice;
use app\admin\controller\Super;

use app\admin\components\BuildTreeArray;
use model\ImSheetMaster;
use model\PosBranch;
use model\Item_cls;
use model\BaseCode;
use model\PmSheetDetail;
use model\PcPriceDetail;
use model\Item_info;
use model\PcPriceMaster;
use think\Db;

class Jsondata extends Super {
	
    //调价单列表
    public function sheetIndex() {
        $fields = "sheet_no,(case approve_flag when 1 then '已审核' when 0 then '未审核' end) approve_flag,branch_no,d_branch_no,sheet_amt,order_man,oper_date,confirm_man";
        $model = new ImSheetMaster();
        $list = $model->field($fields)->where("confirm_man='' and approve_flag=0")->select()->toArray();
        return listJson ( 0, '', count($list), $list);
    }

	//门店列表
    public function branch() {
        $fields = 'branch_no,branch_name,branch_man,branch_tel';
        $model = new PosBranch();
        $list = $model->field($fields)->select()->toArray();
        return listJson ( 0, '', count($list), $list);
    }

	//商品分类
    public function itemClass() {
        $datas = input('data');
        $Item_cls=new Item_cls();
        $sql = "select item_clsno as id, cls_parent as fid,item_clsname as text  from ".$Item_cls->tableName();
        $data = Db::query($sql);
        if ($datas == 'clsjson') {
            $aa = array();
            foreach ($data as $k => $v) {
                $aa[$k] = $v;
                $aa[$k]['text'] = $data[$k]['text'] . "(" . $aa[$k]['id'] . ")";
            }
            $data = $aa;
        }
        $bta = new BuildTreeArray($data, 'id', 'fid', 0);
        $tree = $bta->getTreeArray();
        
        $code=200;
        $message='';
        return treeJson($code, $message, $tree,lang("alltypes"));
    }

	//商品品牌
    public function itemBrand() {
    	$BaseCode=new BaseCode();
        $sql = "select code_id as id, type_no as fid, code_name as text from ".$BaseCode->tableName()." where type_no='PP'";
        $data = Db::query($sql);
        $all = array('id' => 'top', 'text' => "所有品牌", "children" => $data);
		return $data;
    }

	//调价单信息
    public function getPmSheetDetail() {
    	$PmSheetDetail=new PmSheetDetail();
    	$Item_info=new Item_info();
        $sheetNo = input("sheetno");
        $sql = "select d.item_no,d.real_qty,round(((d.real_qty-d.order_qty) / i.purchase_spec),2) as large_qty
        		,round((d.real_qty-d.order_qty),2) as order_qty,d.orgi_price as item_price,i.sale_price
        		,round((i.sale_price * (d.real_qty-d.order_qty)),2) as sale_price_amt
        		,round(((d.real_qty-d.order_qty) * d.orgi_price),2) as sub_amt,d.other1,i.purchase_spec
        		,i.item_name,i.item_subno,i.unit_no as item_unit,i.item_size from ".$PmSheetDetail->tableName()." as d
        		left join ".$Item_info->tableName()." as i on i.item_no=d.item_no 
        		where d.sheet_no='" . $sheetNo . "' and (d.real_qty-d.order_qty) > 0";
        $data = Db::query($sql);
        return $data;
    }

	//调价单详细项目
    public function getPXFlowDetail() {
        $no = input("sheetno");
        if (!empty($no)) {
			$PcPriceDetail=new PcPriceDetail();
            $model = $PcPriceDetail->GetSheetDetails($no);
            $result = array();

            if (!empty($no)) {
                $i = 0;
                foreach ($model as $k => $v) {
                    $result[$i]["item_no"] = $v["item_no"];
                    $result[$i]["item_name"] = $v["item_name"];
                    $result[$i]["item_subno"] = $v["item_subno"];
                    $result[$i]["unit_no"] = $v["unit_no"];
                    $result[$i]["price"] = sprintf("%.2f", $v["old_price"]);
                    $result[$i]["sale_price"] = sprintf("%.2f", $v["old_price1"]);
                    $result[$i]["vip_price"] = $v["old_price2"] ? sprintf("%.2f", $v["old_price2"]) : '0';
                    $result[$i]["sup_ly_rate"] = $v["old_price3"] ? sprintf("%.2f", $v["old_price3"]) : '0';
                    $result[$i]["trans_price"] = $v["old_price4"] ? sprintf("%.2f", $v["old_price4"]) : '0';
                    $result[$i]["price1"] = sprintf("%.2f", $v["new_price"]);
                    $result[$i]["sale_price1"] = sprintf("%.2f", $v["new_price1"]);
                    $result[$i]["vip_price1"] = $v["new_price2"] ? sprintf("%.2f", $v["new_price2"]) : '0';
                    $result[$i]["sup_ly_rate1"] = $v["new_price3"] ? sprintf("%.2f", $v["new_price3"]) : '0';
                    $result[$i]["trans_price1"] = $v["new_price4"] ? sprintf("%.2f", $v["new_price4"]) : '0';

                    if (doubleval($v["old_price1"]) != 0) {
                        $t_mlx = doubleval(sprintf("%.4f", ($v["old_price1"] - $v["old_price"]) / $v["old_price1"])) * 100;
                        $result[$i]["mlv"] = strval($t_mlx) . "%";
                    } else {
                        $result[$i]["mlv"] = "";
                    }
                    if (doubleval($v["new_price1"]) != 0) {
                        $t_mlx1 = doubleval(sprintf("%.4f", ($v["new_price1"] - $v["new_price"]) / $v["new_price1"])) * 100;
                        $result[$i]["new_mlv"] = strval($t_mlx1) . "%";
                    } else {
                        $result[$i]["new_mlv"] = "";
                    }
                    $result[$i]["rowIndex"] = $i + 1;
                    $i++;
                }
                return listJson ( 0, '', $i, $result );
            }
        }
    }

	//调价单分页
    public function getYHSheet() {
        $page = input('page') ? intval(input('page')) : 1;
        $rows = input('limit') ? intval(input('limit')) : 10;
        
        $countSql="select count(*) as total";
        $fieldSql="sheet_no,(case order_status when 0 then '未处理' when 1 then '部分出货' end) as order_status,branch_no,d_branch_no";
        $sql=" where approve_flag='1' and order_status < 2 and valid_date > now() and trans_no='YH'";
        
        $res=Db::query($countSql.$sql);
        $total=$res[0]['total'];
        $result["total"] = $total;
        
        $offset = ($page - 1) * $rows;
        $rowIndex = ($page - 1) * $rows + 1;
        $list = Db::query($fieldSql.$sql." limit $offset,$rows");

        $temp = array();
        $colmns = "sheet_no,order_status,branch_no,d_branch_no";
        foreach ($list as $k => $v) {
            $tt = array();
            foreach ($v as $kk => $vv) {
                if (in_array($kk, explode(',', $colmns))) {
                    $tt[$kk] = $vv;
                }
            }
            array_push($temp, $tt);
        }
        
        return listJson ( 0, '', $total, $temp );
    }

	//获取审核或者未审核调价单
    public function getPcPriceNoApproveList() {
        $page = input('page') ?input('page') : 1;
        $rows = input('limit') ? input('limit') : 10;
        $start = input("start");
        $end = input("end");
        $sheet_no = input("no");
        $approve_flag = input("approve_flag");
        $oper_id = input("oper_id");
        $PcPriceMaster=new PcPriceMaster();
        $list=$PcPriceMaster->GetPager($rows, $page, $start, $end, $sheet_no, $approve_flag, $oper_id);
        
        return listJson ( 0, '', $list['total'], $list['rows'] );
    }

	//获取调价单代码
    public function getReason() {
    	$BaseCode=new BaseCode();
        $model = $BaseCode->GetBaseCode("OO");
        return $model;
    }

}
