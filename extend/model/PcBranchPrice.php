<?php
//pc_branch_price表
namespace model;
use think\Db;
use model\Item_info;
use model\BdItemCombsplit;
use model\BdSupcustItem;
use model\PcBranchPriceBreakpoint;
use app\admin\components\Enumerable\EOperStatus;

class PcBranchPrice extends BaseModel {

	protected $pk='id';
	protected $name="pc_branch_price";

    public function GetModelsForPos($branch_no, $last_time) {
        $where="1=1";
        if (!empty($branch_no)) {
        	$where.=" and branch_no='$branch_no'";
        }
        if (!empty($last_time)) {
        	$where.=" and oper_date >= $last_time";
        }
        
        $list=Db::name($this->name)
        ->field("branch_no,item_no,price,sale_price,vip_price")
        ->where($where)
        ->select();
        return $list;
    }


    public function UpdateBranchItemPriceForPI($branch_no, $item_no, $price, $sp_no) {
        $res = 0;
        $real_itemno = $item_no;
        try {
        	$itemInfo=new Item_info();
            $item_model = $itemInfo->GetItem($item_no);
            $real_itemno = $item_model->item_no;
            $sale_price = $item_model->sale_price;//零售价
            $item_model->price = $price;
            $item_model->modify_date = date(DATETIME_FORMAT);
            if ($item_model->save()) {
                $res = 1;
            } else {
                $res = 0;
            }
            if ($res == 1) {
				$bdItemSplit=new BdItemCombsplit();
				$bdSupcustItem=new BdSupcustItem();
                $combs = $bdItemSplit->GetSingle($real_itemno);
                if ($this->AddBranchItemPrice($branch_no, $real_itemno, $price, $sp_no, $sale_price) == 1) {
                    $res = 1;
                    $res = $bdSupcustItem->UpdateLastPriceForPI($sp_no, $item_no, $price);
                }
                if ($res == 1) {
                }
            }else{
            	
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }


    public function SyncBranchItemPrice($branch_no, $item_no, $price) {
        $result = 0;
        try {
            $isok = FALSE;
            $model = $this->where("branch_no='$branch_no' and item_no='$item_no'")->find();
            if (!empty($model)) {
                $model->price = $price;
            } else {
            	$itemInfo=new Item_info();
                $isok = TRUE;
                $model = new PcBranchPrice();
                $model->branch_no = $branch_no;
                $model->item_no = $item_no;
                $model->price = $price;
                $itemModel = $itemInfo->GetOne($item_no);
                $model->supcust_no = $itemModel->main_supcust;
                $model->sale_price = $itemModel->sale_price;
                $model->updatetime = date(DATETIME_FORMAT);
            }
            if ($model->save()) {
                $result = 1;
            }
            if ($result === 1) {
                $bdBranchPrice = new PcBranchPriceBreakpoint();
                if ($isok) {
                    $bdBranchPrice->rtype = EOperStatus::UPDATE;
                } else {
                    $bdBranchPrice->rtype = EOperStatus::ADD;
                }
                $bdBranchPrice->branch_no = $branch_no;
                $bdBranchPrice->item_no = $item_no;
                $bdBranchPrice->updatetime = date(DATETIME_FORMAT);
                if ($bdBranchPrice->save()) {
                    $result = 1;
                } else {
                    $result = 0;
                }
            }
        } catch (Exception $ex) {
            $parameters = "门店编码:" . $branch_no . ",商品编码:" . $item_no . ",商品进价:" . $price;
           // write_log("新增门店商品价格(SyncBranchItemPrice)异常,参数:" . $parameters . ".异常信息:" . $ex, "PCBranchPrice");
            $result = -2;
        }
        return $result;
    }

	/**
	 * @$branch_no 	分店编码
	 * @$item_no 	商品编码
	 * @$price		进货价
	 * @$sp_no		供应商编号
	 * @$sale_price	零售价
	 * @$vip_price	会员价
	 */
    //@$branch_no 门店编码
    public function AddBranchItemPrice($branch_no, $item_no, $price, $sp_no, $sale_price,$vip_price=0,$base_price=0) {
        $res = 0;
        try {
            $model = $this->where(
                    "branch_no='$branch_no' and item_no='$item_no' and supcust_no='$sp_no'"
                    )->find();
            if (!empty($model)) {
                $model->price = $price;
                $model->sale_price = $sale_price;
                $model->oper_date = date(DATETIME_FORMAT);
                if($vip_price>0){
                	$model->vip_price = $vip_price;
                }
                if($base_price>0){
                	$model->base_price = $base_price;
                }
                if ($model->save()) {
                    $res = 1;
                }
            } else {
                $model1 = new PcBranchPrice();
                $model1->branch_no = $branch_no;
                $model1->item_no = $item_no;
                $model1->price = $price;
                $model1->supcust_no = $sp_no;
                $model1->sale_price = $sale_price;
                $model1->oper_date = date(DATETIME_FORMAT);
                $model1->vip_price = $vip_price;
                $model1->base_price = $base_price;
               
                if ($model1->save()) {
                    $res = 1;
                }
            }
            if ($res === 1) {
                $bdBranchPrice = new PcBranchPriceBreakpoint();
                if (!empty($model)) {
                    $bdBranchPrice->rtype = EOperStatus::UPDATE;
                } else {
                    $bdBranchPrice->rtype = EOperStatus::ADD;
                }
                $bdBranchPrice->branch_no = $branch_no;
                $bdBranchPrice->item_no = $item_no;
                $bdBranchPrice->updatetime = date(DATETIME_FORMAT);
                if ($bdBranchPrice->save()) {
                    $res = 1;
                } else {
                    $res = 0;
                }
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }

    //删除分店价格
    public function DelBranchPriceById($id){
    	$model = $this->where(["id"=>$id])->find();
    	$branch_no=$model->branch_no;
    	$item_no=$model->item_no;
    	$ok=$model->delete();
    	$res=0;
    	if ($ok) {
    		$bdBranchPrice = new PcBranchPriceBreakpoint();
    		$bdBranchPrice->rtype = EOperStatus::DELETE;
    		$bdBranchPrice->branch_no = $branch_no;
    		$bdBranchPrice->item_no = $item_no;
    		$bdBranchPrice->updatetime = date(DATETIME_FORMAT);
    		if ($bdBranchPrice->save()) {
    			$res = 1;
    		}
    	}
    	return $res;
    }
    
    public $rtype;
    public $rid;
    public $updatetime;

    public function GetUpdateDataForPos($branch_no, $rid = "", $updatetime = "") {

        $where="1=1";

       /**
        * EOperStatus::DELETE 是后台手动删除操作，已删除pc_branch_price记录并且在break表记录删除
        */
        if (empty($rid)) {
            $rid = 0;
        }
        $where.=" and a.rid > $rid and a.rtype!='".EOperStatus::DELETE."'";
        $where.=" and s.branch_no=a.branch_no ";
        if (!empty($updatetime)) {
            $where.=" and a.updatetime>$updatetime";
        }
        if (!empty($branch_no)) {
            $where.=" and (s.branch_no='$branch_no' or a.branch_no='ALL')";
        }
        $result=Db::name($this->name)
        ->alias('s')
        ->field("a.rid,a.rtype,a.updatetime,s.branch_no,a.item_no,s.price,s.sale_price,s.vip_price")
        ->join('pc_branch_price_breakpoint a','s.item_no=a.item_no',"RIGHT")
        ->where($where)
        ->select();
            

        
        $list = array();
        if ($rid == "-1") {
        	$bdBranchPrice = new PcBranchPriceBreakpoint();
            $r_id = $bdBranchPrice->GetMaxRidForUpdate();
        }
        foreach ($result as $v) {
            $tt = array();
            $tt["rid"] = ($rid == "-1") ? $r_id : $v["rid"];
            $tt["rtype"] = $v["rtype"];
            $tt["updatetime"] = $v["updatetime"];
            $tt["branch_no"] = $v["branch_no"];
            $tt["item_no"] = $v["item_no"];
            $tt["price"] = $v["price"];
            $tt["sale_price"] = $v["sale_price"];
            $tt["vip_price"] = $v["vip_price"];
            array_push($list, $tt);
        }
        return $list;
    }

}
