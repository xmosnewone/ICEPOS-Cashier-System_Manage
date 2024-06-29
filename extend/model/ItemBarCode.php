<?php
//bd_item_barcode表
namespace model;
use think\Db;
use model\BdItemBarcodeBreakpoint;

class ItemBarCode extends BaseModel {

	protected $pk='item_barcode';
	protected $name="bd_item_barcode";
	
    public function GetAllModels($last_time) {
     
        $where="1=1";
        if (!empty($last_time)) {
            $where.="modify_date >= '$last_time'";
        }
        
        $list=Db::table($this->table)
        		->field('item_no,item_barcode,modify_date')
        		->where($where)
        		->select();
        return $list;
    }


    public function GetOnce($barcode) {
        $aryRes = array();
        $res = $this->where("item_barcode='$barcode'")->find();
        return empty($res) ? $aryRes : array_filter($res);
    }

    public $rtype;
    public $rid;
    public $updatetime;

    public function GetUpdateDataForPos($rid = "", $updatetime = "") {
        if ($rid == "-1") {
            $result=Db::name($this->name)
            ->alias('s')
            ->field("0 as rid,'I' as rtype,now() as updatetime,s.item_no,s.item_barcode")
            ->select();
        } else {

            if (empty($rid)) {
            	$rid = 0;
            }
            
            $where="a.rid > $rid";
            if (!empty($updatetime)) {
            	$where.=" and a.updatetime > '$updatetime'";
            }
            
            $result=Db::name($this->name)
            ->alias('s')
            ->join("bd_item_barcode_breakpoint a","s.item_no=a.item_no and s.item_barcode=a.item_barcode","RIGHT")
            ->field("a.rid,a.rtype,a.updatetime,a.item_no,a.item_barcode")
            ->where($where)
            ->select();
        }
        
        $list = array();
        if ($rid == "-1") {
        	$BdItemBarcodeBreakpoint=new BdItemBarcodeBreakpoint();
            $r_id = $BdItemBarcodeBreakpoint->GetMaxRidForUpdate();
        }
        foreach ($result as $v) {
            $tt = array();
            $tt["rid"] = $rid == "-1" ? $r_id : $v["rid"];
            $tt["rtype"] = $v["rtype"];
            $tt["updatetime"] = $v["updatetime"];
            $tt["item_no"] = $v["item_no"];
            $tt["item_barcode"] = $v["item_barcode"];
            array_push($list, $tt);
        }
        return $list;
    }

}
