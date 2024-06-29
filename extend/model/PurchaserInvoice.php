<?php
//purchaser_invoice表
namespace model;
use think\Db;

class PurchaserInvoice extends BaseModel {

    public $invoice_type_name;
    public $invoice_tt_name;
    public $invoice_content_name;
   
    protected $pk='invoice_id';
    protected $name="purchaser_invoice";

    public function GetInvoicesByPurNo($pur_no) {
        
        $where="c.type_no='IC' AND c1.type_no='IT' AND c2.type_no='ID' AND pur_no='" . $pur_no . "' ";
        $result=Db::table($this->table)
        ->alias('in')
        ->field("invoice_id,pur_no,invoice_type,c.code_name as invoice_type_name,invoice_tt,c1.code_name as invoice_tt_name,invoice_name,invoice_content,c2.code_name invoice_content_name")
        ->join('bd_base_code c','c.code_id=in.invoice_type',"LEFT")
        ->join('bd_base_code c1','c1.code_id=in.invoice_tt',"LEFT")
        ->join('bd_base_code c2','c2.code_id=in.invoice_content',"LEFT")
        ->where($where)
        ->select();

        $arrget = array();
        if (count($result) > 0) {
        	for ($i = 0; $i < count($result); $i++) {
        		$arrget1 = $result[$i];
        		$arrget1['invoice_type_name'] = $result[$i]['invoice_type_name'];
        		$arrget1['invoice_tt_name'] = $result[$i]['invoice_tt_name'];
        		$arrget1['invoice_content_name'] = $result[$i]['invoice_content_name'];
        		array_push($arrget, $arrget1);
        	}
        };
        return $arrget;
        
    }

}
