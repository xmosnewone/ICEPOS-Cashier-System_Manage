<?php
namespace app\admin\controller;
use app\admin\controller\Super;
use app\admin\components\Enumerable\EOperStatus;

use model\Item_info;
use model\Item_cls;
use model\BaseCode;
use model\Supcust;
use model\ItemBarCode;
use model\BdSupcustItem;
use model\BdItemInfoBreakpoint;
use model\BdItemPhoto;
use model\PcBranchPrice;
use model\PosBranch;
use model\SpInfos;
use model\ItemPhoto;
use model\BdItemBarcodeBreakpoint;
use model\BdItemCombsplit;
use think\Db;
use model\BaseModel;
//商品管理
class Product extends Super {

	//商品管理首页
    public function index() {
    	
        return $this->fetch('product/index');
    }
	
	//添加或者更新商品break表
	public function breakRecord($item_no,$op){
		$bdBreakPoint = new BdItemInfoBreakpoint ();
		$bdBreakPoint->rtype = $op;
		$bdBreakPoint->item_no = $item_no;
		$bdBreakPoint->updatetime = date ( DATETIME_FORMAT ,$this->_G['time']);
		$bdBreakPoint->save ();
	}
	
	//条形码break表更新记录
	public function breakBarcode($item_no,$barcode,$op){
		$bdBreakPoint = new BdItemBarcodeBreakpoint ();
		$bdBreakPoint->rtype = $op;
		$bdBreakPoint->item_no = $item_no;
		$bdBreakPoint->item_barcode = $barcode;
		$bdBreakPoint->updatetime = date(DATETIME_FORMAT,$this->_G['time']);
		$bdBreakPoint->save ();
	}
	
    //显示添加商品页面
    public function add(){
    	$prodcutcls = new Item_cls();
    	$resultcls = $prodcutcls->GetItemClses();
    	
    	//基础代码表
    	$BaseCode=new BaseCode();
    	//单位
    	$unitarr = $BaseCode->get('UN');
    	//品牌
    	$brandarr = $BaseCode->get('PP');
    	//查找供应商
    	$Supcust=new Supcust();
    	$suparr = $Supcust->get();
    	//是否已经有选择商品分类
    	$clsno=input("clsno");
    	
    	$this->assign('clsno', $clsno);
    	$this->assign('clsarr', $resultcls);
    	$this->assign('unitarr', $unitarr);
    	$this->assign('brandarr', $brandarr);
    	$this->assign('suparr', $suparr);
    	return $this->fetch('product/add');
    }

	//保存商品
    public function addPost() {
		$item_no = input ( 'item_no' );
		$item = new Item_info ();
		
		$num = $item->where ( "item_no='$item_no'" )->count ();
		if ($num > 0) {
			return [ 
					'code' => false,
					'msg' => lang ( "item_barcode_exist" ) 
			];
		}
		
		$item_barcode = new ItemBarCode ();
		$num = $item_barcode->where ( "item_barcode='$item_no'" )->count ();
		if ($num > 0) {
			return [ 
					'code' => false,
					'msg' => lang ( "item_barcode_db_exist" ) 
			];
		}
		
		$brand = input ( 'brand' );
		$brands = explode ( '|', $brand );
		$time = $this->_G ['time'];
		$content = array (
				'item_no' => $item_no,
				'item_name' => input ( 'item_name' ),
				'item_subname' => input ( 'item_subname' ),
				'item_clsno' => input ( 'item_clsno' ),
				'unit_no' => input ( 'unit_no' ),
				'price' => input ( 'price' ),
				'sale_price' => input ( 'sale_price' ),
				'item_size' => input ( 'item_size' ),
				'main_supcust' => input ( 'mian_supcust' ),
				'item_brand' => $brands ['0'],
				'item_brandname' => $brands ['1'],
				'build_date' => date ( "Y-m-d H:i:s", $time ),
				'modify_date' => date ( "Y-m-d H:i:s", $time ),
				'item_subno' => input ( 'item_subno' ),
				'item_rem' => getfirstchar ( input ( 'item_name' ) ),
				'num2' => input ( 'num2' ),
				'num3' => input ( 'num3' ),
				'purchase_spec' => input ( 'purchase_spec' ),
				'combine_sta' => input ( 'combine_sta' ),
				'sale_min_price' => input ( 'sale_min_price' ),
				'shipment_spec' => input ( 'shipment_spec' ),
				'trans_price' => input ( 'trans_price' ),
				'vip_price' => input ( 'vip_price' ),
				'base_price' => input ( 'base_price' ),
				'change_price' => input ( 'change_price' ) ? input ( 'change_price' ) : '0',
				'is_focus' => input ( 'is_focus' ) ? input ( 'is_focus' ) : '0',
				'vip_acc_flag' => input ( 'vip_acc_flag' ) ? input ( 'vip_acc_flag' ) : '0',
				"is_open" => input ( "is_open" ) ? input ( "is_open" ) : "0",
				"is_pifa" => input ( "is_pifa" ) ? input ( "is_pifa" ) : "0",
				'product_area' => input ( 'product_area' ),
				'purchase_tax' => input ( 'purchase_tax' ),
				'po_cycle' => input ( 'po_cycle' ),
				'status' => input ( 'status' ),
				'sale_tax' => input ( 'sale_tax' ),
				'vip_acc_num' => input ( 'vip_acc_num' ),
				'other1' => input ( 'status' ),
				'vip_price2' => input ( 'vip_price2' ),
				'base_price1' => input ( 'base_price1' ),
				'base_price2' => input ( 'base_price2' ),
				'base_price3' => input ( 'base_price3' ),
				'base_price4' => input ( 'base_price4' ) 
		);
		
		Db::startTrans ();
		$return = $item->save ( $content );
		// 供应商
		$mian_supcust = input ( 'mian_supcust' );
		$SupcustItem = new BdSupcustItem ();
		$nums = $SupcustItem->where ( "item_no='$item_no' and branch_no='000' and supcust_no='$mian_supcust'" )->count ();
		if ($nums == '0') {
			
			$SupcusItem = array (
					"item_no" => $item_no,
					"branch_no" => '000',
					"supcust_no" => trim ( input ( 'mian_supcust' ) ),
					"sale_way" => 'A',
					"contract_date" => date ( "Y-m-d H:i:s", $time ),
					"appointed_price" => '0.0000',
					"top_price" => '0.0000',
					"bottom_price" => '0.0000',
					"last_price" => '0.0000' 
			);
			$SupcustItem->save ( $SupcusItem );
		}
		
		if ($return != false) {
			//记录操作
			$this->breakRecord($item ["item_no"],EOperStatus::ADD);
			Db::commit ();
			return [ 
					'code' => true,
					'msg' => lang ( "item_add_success" ) 
			];
		} else {
			Db::rollback ();
			return [ 
					'code' => false,
					'msg' => lang ( "item_add_error" ) 
			];
		}
    }
    
    //显示编辑商品
    public function edit(){
    	
    	$prodcutcls = new Item_cls();
    	$resultcls = $prodcutcls->GetItemClses();
    	
    	$unit = new BaseCode();
    	$unitarr = $unit->get('UN');
    	
    	$brand = new BaseCode();
    	$brandarr = $unit->get('PP');
    	
    	$Supcust = new Supcust();
    	$suparr=$Supcust->select()->toArray();
    	
    	$item_no=input("item_no");
    	$item = new Item_info();
    	$one = $item->where("item_no='$item_no'")->find();

    	$txtMlRate = sprintf("%.2f", ($one['sale_price'] - $one['price']) / $one['sale_price']);
    	
    	//查询商品图片
    	$BdItemPhoto=new BdItemPhoto();
    	$photos=$BdItemPhoto->search(['item_no'=>$item_no]);
    	
    	//查询所有的门店
    	$PosBranch=new PosBranch();
    	$branchs=$PosBranch->GetAllBranchField("branch_no,branch_name");
    	
    	$this->assign('photos', $photos);
    	$this->assign('branchs', $branchs);
    	$this->assign('txtMlRate', $txtMlRate);
    	$this->assign('one', $one);
    	$this->assign('clsarr', $resultcls);
    	$this->assign('unitarr', $unitarr);
    	$this->assign('brandarr', $brandarr);
    	$this->assign('suparr', $suparr);
    	return $this->fetch('product/edit');
    }
    
    //保存编辑商品
    public function editPost() {
    	$item_no = input('item_no');
		$item = new Item_info();
		$result = $item->where("item_no='$item_no'")->find();
		
		$brand = explode ('|', input ('brand'));
		$content ['item_brand'] = $brand ['0'];
		$content ['item_brandname'] = $brand ['1'];
		$content ['item_clsno'] = input ( 'item_clsno' );
        $content ['item_subno'] = input ( 'item_subno' );
		$content ['item_name'] = input ( 'item_name' );
		$content ['item_subname'] = input ( 'item_subname' );
		$content ['item_rem'] = input ( 'item_rem' );
        $content ['price'] = input ( 'price' );
		$content ['vip_price'] = input ( 'vip_price' );
		$content ['base_price'] = input ( 'base_price' );
		$content ['trans_price'] = input ( 'trans_price' );
		$content ['shipment_spec'] = input ( 'shipment_spec' );
		$content ['unit_no'] = input ( 'unit_no' );
		$content ['combine_sta']=input ( 'combine_sta' );
		$content ['item_size'] = input ( 'item_size' );
		$content ['purchase_spec'] = input ( 'purchase_spec' );
		$content ['main_supcust'] = input ( 'mian_supcust' );
		$content ['num2'] = input ( 'num2' );
		$content ['num3'] = input ( 'num3' );
		$content ['sale_min_price'] = input ( 'sale_min_price' );
		$content ['change_price'] = input ( 'change_price' ) ? input ( 'change_price' ) : '0';
		$content ['is_focus'] = input ( 'is_focus' ) ? input ( 'is_focus' ) : '0';
		$content ['vip_acc_flag'] = input ( 'vip_acc_flag' ) ? input ( 'vip_acc_flag' ) : '0';
		$content ["is_open"] = input ( "is_open" ) ? input ( "is_open" ) : "0";
		$content ["is_pifa"] = input ( "is_pifa" ) ? input ( "is_pifa" ) : "0";
		$content ['product_area'] = input ( 'product_area' );
		$content ['purchase_tax'] = input ( 'purchase_tax' );
		$content ['po_cycle'] = input ( 'po_cycle' );
		$content ['status'] = input ( 'status' );
		$content ['sale_tax'] = input ( 'sale_tax' );
		$content ['vip_acc_num'] = input ( 'vip_acc_num' );
		$content ['other1'] = input ( 'other1' );
		$content ['base_price1'] = input ( 'base_price1' );
		$content ['base_price2'] = input ( 'base_price2' );
		$content ['base_price3'] = input ( 'base_price3' );
		$content ['base_price4'] = input ( 'base_price4' );
		$content ['content'] = input ( 'content','',"");
		
		$content ['modify_date'] = date ( "Y-m-d H:i:s", time () );
		
		foreach($content as $k=>$val){
			$result->$k=$val;
		}
		
		$return = $item->edit ( $result );
		switch ($return) {
			case "ERROR" :
				return ['code'=>false,'msg'=>lang("item_update_error")];
			case "OK" :
				//记录操作
				$this->breakRecord($item_no,EOperStatus::UPDATE);
				return ['code'=>true,'msg'=>lang("item_update_success")];
		 	case "REPEAT_NO" :
		 		return ['code'=>false,'msg'=>lang("item_barcode_exist")];
		}
    	
    }
    
    //删除商品
    public function delItems(){
    	
    	$item_no=input("item_no");
    	if(empty($item_no)){
    		$return['code']=false;
    		$return['msg']=lang("wrong_data");
    		return $return;
    	}
    	
    	$arr=[];
    	if(strpos($item_no, ",")!==false){
    		$arr=explode(",", $item_no);
    	}else{
    		$arr[]=$item_no;
    	}
    	
    	$barcode = new ItemBarCode();
    	$itemInfo=new Item_info();
    	
    	foreach($arr as $k=>$item_no){
    		$one=$itemInfo->where("item_no='$item_no'")->find();
    		if ($itemInfo->del ( $item_no )) {
				
				//记录删除商品操作
				$this->breakRecord($item_no,EOperStatus::DELETE);
				
				//删除条形码
				$barcodeList=$barcode->where("item_no='$item_no'")->select();
				if($barcodeList){
					foreach($barcodeList as $barone){
						$ok=$barcode->where("item_no='$item_no' and item_barcode='{$barone['item_barcode']}'")
								->delete();
						if($ok){
							//记录删除操作
							$this->breakBarcode($item_no, $barone['item_barcode'], EOperStatus::DELETE);
						}
					}
				}
				
				//删除图片
				if($one['img_src']!=''){
					@unlink(".".$one['img_src']);
				}
				$itempPhoto = new ItemPhoto ();
				$photos=$itempPhoto->where("item_no='$item_no'")->select();
				if($photos){
					foreach($photos as $value){
						if($value['photo_url']!=''&&file_exists(".".$value['photo_url'])){
							@unlink(".".$value['photo_url']);
						}
					}
					$itempPhoto->where("item_no='$item_no'")->delete();
				}
    		}
    	}
    	
    	$return['code']=true;
    	$return['msg']=lang("update_success");
    	return $return;
    }
   
	//上传
    public function upload() {
    	//商品编码
    	$itemNo=input("item_no");
    	if(empty($itemNo)){
    		return array("code" => false, "msg" =>lang("invalid_variable"));
    	}
        //上传图片
        $result=$this->uploadImage("file",[]);
        //自动压缩图片
        $this->compressImage($result['path']);
        //获取图片宽高等信息
        list($width, $height, $type, $attr) = getimagesize($result['path']);
        $result['width']=$width;
        $result['height']=$height;
        $result['path']=substr($result['path'], 1);
        
        $imgUrl=$result['path'];
        $item = new Item_info ();
        $one = $item->where("item_no='$itemNo'")->find ();
        //更新图片
        if ($one ['img_src'] == '') {
        	$content = array (
        			'img_src' =>$imgUrl,
        			'modify_date' => date (DATE_FORMAT, $this->_G['time'])
        	);
        	$one->save($content,['item_no'=>$one['item_no']]);
        	
        	//记录操作
        	$this->breakRecord($itemNo,EOperStatus::UPDATE);
        }
        //添加多张图片
        $photo = new ItemPhoto ();
        $data = array (
        		'item_no' => $itemNo,
        		'photo_url' => $imgUrl,
        		'photo' => '1',
        		'add_time' => $this->_G['time']
        );
        
        $photo->save($data);
        
        $result['item_no']=$itemNo;
        $result['photo_id']=$photo->photo_id;

        return array("code" => true, "msg" =>'success','data'=>$result);
    }
    
    //设置默认图片
    public function setDefaultImg(){
    	//商品编码
    	$itemNo=input("item_no");
    	//图片id
    	$photo_id=input("photo_id",'0','intval');
    	if(empty($itemNo)||empty($photo_id)){
    		return array("code" => false, "msg" =>lang("invalid_variable"));
    	}
    	
    	$item = new Item_info ();
    	$photo = new ItemPhoto ();
    	$one=$photo->where(['photo_id'=>$photo_id])->find();
    	
    	$data = array (
        			'img_src' =>$one['photo_url'],
        			'modify_date' => date (DATE_FORMAT, $this->_G['time'])
        );
    	
       	$item->save($data,['item_no'=>$itemNo]);
       	
       	//记录操作
       	$this->breakRecord($itemNo,EOperStatus::UPDATE);
       	
       	return array("code" => true, "msg" =>lang("item_default_img_set"));
    }

    //删除商品图片
    public function delPhotos(){
    	$photo_id = input("photo_id",'0','intval');
    	$BdItemPhoto=new BdItemPhoto();
    	$ItemInfo = new Item_info ();
    	
    	$one=$BdItemPhoto->where(['photo_id'=>$photo_id])->find();
    	if($one){
    		//清空商品主数据的图片
    		$item=$ItemInfo->where(['item_no'=>$one['item_no']])->find();
    		if($item['img_src']!=''&&$item['img_src']==$one['photo_url']){
    			$item->img_src='';
    			$item->save();
    			//记录操作
    			$this->breakRecord($one['item_no'],EOperStatus::UPDATE);
    		}
    	}
    	$ok=$one->delete();
    	@unlink(".".$one['photo_url']);
    	if($ok){
    		$code=true;
    		$msg=lang("delete_success");
    	}else {
    		$code=false;
    		$msg=lang("delete_error");
    	}
    	return ['code'=>$code,'msg'=>$msg];
    }
    
    //获取该商品所有的条形码
    public function getAllBarcode() {
        $item_no = input('item_no');
        $model = new ItemBarCode();
        $result = $model->where("item_no='$item_no'")->select()->toArray();
       	return listJson(0,'',count($result),$result);
    }

	//添加条形码
    public function addBarcode() {
        $item_no = input('item_no');
        $barcode = input('barcode');
        
        if ($item_no == '' || $barcode == '') {
        	return ['code'=>false,'msg'=>lang("invalid_variable")];
        }
        
		$item_barcode = new ItemBarCode ();
		$num = $item_barcode->where ( "item_barcode='$barcode'" )->count ();
		if ($num > 0) {
			$re ['code'] = false;
			$re ['msg'] = lang ( "item_barcode_exist" );
			return $re;
		}
		
		$content = array (
				'item_no' => $item_no,
				'item_barcode' => $barcode,
				'modify_date' => date(DATE_FORMAT)
		);

		if ($item_barcode->save ($content)) {
			//操作记录
			$this->breakBarcode($item_no,$barcode,EOperStatus::ADD);
			
			$re ['code'] = true;
			$re ['msg'] = lang ( "item_barcode_success" );
		} else {
			$re ['code'] = false;
			$re ['error'] = lang ( "item_barcode_error" );
		}
		
			return $re;
    }

	//删除条形码
    public function delbarcode() {
        $item_no = input('item_no');
        $barcode = input('barcode');
        
        if ($item_no == '' || $barcode == '') {
        	return ['code'=>false,'msg'=>lang("invalid_variable")];
        }
        
		$item_barcode = new ItemBarCode ();
		$num = $item_barcode->where("item_barcode='$barcode'")->count();
		
		if ($num <=0) {
			return ['code'=>false,'msg'=>lang("item_barcode_not_exist")];
		}
		
		$item_barcode->where("item_barcode='$barcode'")->delete();
		
		//操作记录
		$this->breakBarcode($item_no,$barcode,EOperStatus::DELETE);

		return ['code'=>true,'msg'=>lang("item_barcode_delete")];
       
    }

    //获取所有分店的价格
    public function getAllBranchPrice() {
    	
    	$BranchPrice=new PcBranchPrice();
    	$BranchInfo=new PosBranch();
        $item_no = input('item_no');
        $sql = "select a.id,a.branch_no,a.price,a.sale_price,a.base_price,a.vip_price,a.status,
        		b.branch_name from ".$BranchPrice->tableName()." as a left join ".$BranchInfo->tableName()." 
        		as b on a.branch_no=b.branch_no where a.item_no='" . $item_no . "';";
       $list=Db::query($sql);
       return listJson(0,'',count($list),$list);
    }
    
    //添加分店价格
    public function addBranchPrice(){
    	$BranchPrice=new PcBranchPrice();
    	$branch_no = input('branch_no');
    	$item_no = input('item_no');
    	$sp_no = input('sp_no');
    	$price = input('price');
    	$sale_price = input('sale_price');
    	$base_price = input('base_price');
    	$vip_price = input('vip_price');
    	$result=$BranchPrice->AddBranchItemPrice($branch_no, $item_no, $price, $sp_no, $sale_price,$vip_price,$base_price);
    	$msg=lang("item_bp_add_success");
    	if($result===1){
    		$code=true;
    	}else{
    		$code=false;
    		$msg=lang("item_bp_add_fail");
    	}
    	return ['code'=>true,'msg'=>$msg];
    }
    
    //删除分店价格--删除后不作breakpoint记录
    //@$id 自增唯一的id
    public function delBranchPrice(){
    	$BranchPrice=new PcBranchPrice();
    	$id=input("id",0,'intval');
    	$code=false;
    	$msg=lang("item_bp_del_fail");
    	if($id){
    		$res=$BranchPrice->DelBranchPriceById($id);
    		if($res===1){
    			$code=true;
    			$msg=lang("item_bp_del_success");
    		}
    	}
    	return ['code'=>$code,'msg'=>$msg];
    }

	//获取商品所有经销商价格
    public function getAllSupcust() {
    	$supcustItem=new BdSupcustItem();
    	$SpInfos=new SpInfos();
        $item_no = input('item_no');
        $sql = "select a.item_no as item_no,a.appointed_price as appointed_price,a.top_price as top_price,
        		a.bottom_price as bottom_price,a.last_price as last_price,a.sale_way as sale_way,
        		b.sp_name as sp_name,b.sp_no as sp_no from ".$supcustItem->tableName()." as a  
        		left join ".$SpInfos->tableName()." as b ON a.supcust_no = b.sp_no where a.item_no='" . $item_no 
        		. "' and a.branch_no='000' ";
        $list=Db::query($sql);
        return listJson(0,'',count($list),$list);
    }

    //添加经销商价格
    public function addSupcust() {
        $item_no = input('item_no');
        $supcust_no = input('supcust_no');
        $appointted_price = input('price');
			
        $Supcust = new Supcust();
        $num = $Supcust->where("sp_no='$supcust_no'")->count();
        if ($num == 0) {
            return ['code'=>false,'msg'=>lang("item_sup_not_exist")];
        }

        $BdSupcustItem=new BdSupcustItem();
        $numc = $BdSupcustItem->where("supcust_no='$supcust_no' and branch_no='000' and item_no='$item_no'")->count();
        if ($numc > 0) {
            return ['code'=>false,'msg'=>lang("item_sup_price_exist")];
        } else {
            $SupcustItem = new BdSupcustItem();
            $data = array(
                "item_no" => $item_no,
                "branch_no" => '000',
                "supcust_no" => $supcust_no,
                "sale_way" => 'A',
                "contract_date" => date(DATETIME_FORMAT, $this->_G['time']),
                "appointed_price" => $appointted_price,
                "top_price" => '0.00',
                "bottom_price" => '0.00',
                "last_price" => '0.00',
            );
            if ($SupcustItem->save($data)) {
                $bdBreakPoint = new BdItemInfoBreakpoint();
                $bdBreakPoint->rtype = EOperStatus::UPDATE;
                $bdBreakPoint->item_no = $item_no;
                $bdBreakPoint->updatetime = date(DATETIME_FORMAT);
                $bdBreakPoint->save();
                return ['code'=>true,'msg'=>lang("item_sup_success")];
            } else {
                return ['code'=>false,'msg'=>lang("item_sup_fail")];
            }
        }
    }
    
	//删除经销商
    public function delSupcust() {
        $item_no = input('item_no');
        $supcust_no = input('supcust_no');
        $SupcustItem = new BdSupcustItem();
        $num = $SupcustItem->where("supcust_no='$supcust_no' and branch_no='000' and item_no='$item_no'")->count();
        if ($num >0) {
            $info = $SupcustItem->where("supcust_no='$supcust_no' and branch_no='000' and item_no='$item_no'")->find();
            if ($info->delete()) {
                $bdBreakPoint = new BdItemInfoBreakpoint();
                $bdBreakPoint->rtype = EOperStatus::UPDATE;
                $bdBreakPoint->item_no = $item_no;
                $bdBreakPoint->updatetime = date(DATETIME_FORMAT);
                $bdBreakPoint->save();
                return ['code'=>true,'msg'=>lang("delete_success")];
            } else {
               	return ['code'=>false,'msg'=>lang("delete_error")];
            }
        } else {
            	return ['code'=>false,'msg'=>lang("empty_record")];
        }
    }

	//商品组合
    public function comblist() {
        return $this->fetch("product/comblist");
    }

	//商品组合详细
    public function comdetail() {
        $item_no = input("item_no");
        if (empty($item_no)) {
        	return ['code'=>false,'msg'=>lang("invalid_variable")];
        }
        
        $Item_info=new Item_info();
        $model = $Item_info->GetModel($item_no);
        $this->assign("model", $model);
        return $this->fetch("product/comdetail");
    }

	//组合列表-将所有商品类型非普通商品的商品列出来-捆绑商品\制单拆分\制单组合等
    public function searchcomb() {
        $page = isset($_GET['page']) ? (intval($_GET['page']) == 0 ? 1 : intval($_GET["page"])) : 1;
        $rows = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $item_no = input("item_no");
        $BdItemCombsplit=new BdItemCombsplit();
        $list=$BdItemCombsplit->SearchModel($page, $rows, $item_no);
        return listJson(0,'',$list['total'],$list['rows']);
     }

	//添加组合商品
    public function addcomb() {
        $combe_no = input("comb_item_no");
        $combe = input("comb");
        if (empty($combe) || empty($combe_no)) {
        	return ["code" => false, "msg" => lang("invalid_variable")];
        } else {
            if (empty($combe["rows"])) {
            	return ["code" => false, "msg" => lang("invalid_variable")];
            } else {
                $models = array();
                $arr = $combe["rows"];
                foreach ($arr as $k => $com) {
                    if (!empty($com["item_no"]) && !empty($com["item_qty"])) {
                        $model = new BdItemCombsplit();
                        $model->item_no = $com["item_no"];
                        $model->item_qty = $com["item_qty"];
                        $model->comb_item_no = $combe_no;
                        array_push($models, $model);
                    }
                }
                $BdItemCombsplit=new BdItemCombsplit();
                if (count($models)<=0) {
                	return ["code" => false, "msg" => lang("item_comb_model_empty")];
                } else {
                    $result = $BdItemCombsplit->AddModel($combe_no, $models);
                    switch ($result) {
                        case -1:
                        	return ["code" => false, "msg" => lang("item_comb_model_empty")];
                            break;
                        case -2:
                        case 0:
                            return ["code" => false, "msg" => lang("save_error")];
                            break;
                        default :
                        	return ["code" => true, "msg" => lang("save_success")];
                            break;
                    }
                }
            }
        }
    }

	//获取组合
    public function getcomb() {
        $item_no = input("item_no");
        $result=[];
        if (!empty($item_no)) {
        	$BdItemCombsplit=new BdItemCombsplit();
            $result = $BdItemCombsplit->GetCombDetail($item_no);
        }
        return listJson(0,'',count($result),$result);
    }
    
    //删除组合
    public function delComb(){
        $comb_item_no = input("comb_item_no");
        $item_no = input("item_no");
        if (!empty($comb_item_no)&&!empty($item_no)) {
            $BdItemCombsplit=new BdItemCombsplit();
            $result = $BdItemCombsplit->delComb($comb_item_no,$item_no);
            return json(["code" => true, "msg" => lang("delete_success")]);
        }
        return json(["code" => true, "msg" => lang("delete_error")]);
    }
    

}
