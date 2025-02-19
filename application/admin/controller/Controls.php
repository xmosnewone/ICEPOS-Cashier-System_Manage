<?php
namespace app\admin\controller;
use app\admin\components\Enumerable\EOperStatus;
use model\PosDaysum;

class Controls extends Super {

	//商品选择的窗口
	public function items(){
        $branch_no=input("branch_no")?input("branch_no"):'';
        $this->assign("branch_no",$branch_no);
		return $this->fetch("controls/goods_item");
	}
	
	//多选商品选择的窗口
	public function multipleItems(){
        $branch_no=input("branch_no")?input("branch_no"):'';
        $this->assign("branch_no",$branch_no);
		return $this->fetch("controls/multiple_goods_item");
	}
	
	//选择商品品牌
	public function brands(){
		return $this->fetch("controls/brands");
	}
	
	//选择商品分类
	public function itemcls(){
		return $this->fetch("controls/itemcls");
	}	
	
	//选择门店
	public function branchs(){
		$branch_no=input("branch_no")?input("branch_no"):'';
		$this->assign("branch_no",$branch_no);
		return $this->fetch("controls/branchs");
	}
	
	//多选门店
	public function mulBranchs(){
		return $this->fetch("controls/mulbranchs");
	}
	
	//选择门店操作员
	public function operators(){
		return $this->fetch("controls/operators");
	}
	
	//经销商选择
	public function suppliers(){
		return $this->fetch("controls/suppliers");
	}
	
	//采购单列表
	public function polists(){
		return $this->fetch("controls/polists");
	}
	
	//POS机
	public function poslists(){
		return $this->fetch("controls/poslists");
	}
	
	//盘点批号
	public function pdnolists(){
		return $this->fetch("controls/pdnolists");
	}
	
	//门店要货单
	public function yhdlists(){
		return $this->fetch("controls/yhdlists");
	}
	
	//直调出库单
	public function modlists(){
		return $this->fetch("controls/modlists");
	}
	
	//批发客户选择
	public function wholesales(){
		return $this->fetch("controls/wholesales");
	}
	
	//支付方式
	public function payments(){
		return $this->fetch("controls/payments");
	}
	
	//批发订单
	public function wsorders(){
		return $this->fetch("controls/wsorders");
	}
	
	//批发销售订单
	public function woorders(){
		return $this->fetch("controls/woorders");
	}
}
