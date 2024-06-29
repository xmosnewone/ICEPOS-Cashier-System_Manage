<?php
//pos_operator表
namespace model;
use think\Db;
use model\PosOperatorBreakpoint;

class PosOperator extends BaseModel {

    public $rowIndex;
    
    protected $pk='oper_id';
    protected $name="pos_operator";

    public function GetPosOperator($branchno, $operid=""){

        $where="(branch_no='$branchno' or branch_no='ALL')";
        if(!empty($operid)){
        	$where .= " and oper_id='$operid'";
        }
        
        return $this->where($where)->select();
    }

    //验证登录用户和密码
    public function verifyOperator($oper_id,$passwd){
        $where='';
        if(!empty($oper_id)){
            $where .= "oper_id='$oper_id'";
        }
        if(!empty($passwd)){
            $where .= " and oper_pw='".md5($passwd)."'";
        }
        return $this->where($where)->find();
    }
    
    public function getone($branchno, $operid) {
    	$list = $this->where("`oper_id`='" . $operid . "' and "."`branch_no`='" . $branchno . "'")->find();
    	return $list;
    }
    

    public function GetAllModelsForPos() {
        return $this->select();
    }


    public function UpdatePwd($operid, $pwd) {
        try {
            if (!empty($operid) && !empty($pwd)) {
                $attr = array("oper_pw" => $pwd);
                $count = $this->save($attr,['oper_id'=>$operid]);
                if ($count > 0) {
                    return 1;
                } else {
                    return 0;
                }
            }
            return 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    public $rtype;
    public $rid;
    public $updatetime;

    public function GetUpdateDataForPos($branch_no, $rid = "", $updatetime = "") {
    	
        $where="1=1";
        if (!empty($branch_no)) {
        	$where.=" and (s.branch_no='$branch_no' or s.branch_no='ALL')";
        }
        
        if ($rid == "-1") {
            
            $result=Db::name($this->name)
            ->alias('s')
            ->field("0 as rid,'I' as rtype,now() as updatetime," .
                    "s.oper_id,s.oper_name," .
                    "s.oper_pw,s.oper_status,s.oper_type," .
                    "s.last_time,s.output_rate,s.branch_no,s.data_grant,s.confirm_pw," .
                    "s.save_discount,s.cash_grant,s.discount_rate,s.Oper_flag,s.group_id," .
                    "s.cashier_status,s.num1")
            ->where($where)
            ->select();
            
        } else {
            
            if (empty($rid)) {
            	$rid = 0;
            }
            
            $where.=" and a.rid > '$rid'";
            if (!empty($updatetime)) {
            	$where.=" and a.updatetime > '$updatetime'";
            }
            
            $result=Db::name($this->name)
            ->alias('s')
            ->field("a.rid,a.rtype,a.updatetime,a.oper_id,s.oper_name," .
                    "s.oper_pw,s.oper_status,s.oper_type," .
                    "s.last_time,s.output_rate,s.branch_no,s.data_grant,s.confirm_pw," .
                    "s.save_discount,s.cash_grant,s.discount_rate,s.Oper_flag,s.group_id," .
                    "s.cashier_status,s.num1")
            ->join("pos_operator_breakpoint a","s.oper_id=a.oper_id","RIGHT")
           	->where($where)
           	->select();
            
        }

        
        $list = array();
        if ($rid == "-1") {
        	$PosOperatorBreakpoint=new PosOperatorBreakpoint();
            $r_id = $PosOperatorBreakpoint->GetMaxRidForUpdate();
        }
        foreach ($result as $v) {
            $tt = array();
            $tt["rid"] = ($rid == "-1") ? $r_id : $v["rid"];
            $tt["rtype"] = $v["rtype"];
            $tt["updatetime"] = $v["updatetime"];
            $tt["oper_id"] = $v["oper_id"];
            $tt["oper_name"] = $v["oper_name"];
            $tt["oper_pw"] = $v["oper_pw"];
            $tt["oper_status"] = $v["oper_status"];
            $tt["oper_type"] = $v["oper_type"];
            $tt["last_time"] = $v["last_time"];
            $tt["output_rate"] = $v["output_rate"];
            $tt["branch_no"] = $v["branch_no"];
            $tt["data_grant"] = $v["data_grant"];
            $tt["confirm_pw"] = $v["confirm_pw"];
            $tt["save_discount"] = $v["save_discount"];
            $tt["cash_grant"] = $v["cash_grant"];
            $tt["discount_rate"] = $v["discount_rate"];
            $tt["Oper_flag"] = $v["Oper_flag"];
            $tt["group_id"] = $v["group_id"];
            $tt["cashier_status"] = $v["cashier_status"];
            $tt["num1"] = $v["num1"];
            array_push($list, $tt);
        }
        return $list;
    }

    //更新操作时间
    public function updateLastime($oper_id){
        $attr = array("last_time" => date('Y-m-d H:i:s'));
        return $this->save($attr,['oper_id'=>$oper_id]);
    }
    //删除
    public function deleteOne($branch_no,$posid){
    	$delNum=$this->where("oper_id='$posid' and branch_no='$branch_no'")->delete();
    	if ($delNum>0) {
    		return true;
    	}else{
    		return false;
    	}
    }
    
    //生成oper_id,最少5位数
    public function patchId($id){
    	$len=strlen($id);
    	if($len<5){
    		$miss=5-$len;
    		$patch='';
    		for($i=0;$i<$miss;$i++){
    			$patch.="0";
    		}
    		$id=$patch.$id;
    	}
    	return $id;
    }
   
}
