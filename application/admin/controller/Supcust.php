<?php
namespace app\admin\controller;
use app\admin\controller\Super;
use app\admin\components\Enumerable\EOperStatus;

use model\Supcust as SupModel;
use model\Item_info;
use think\Db;
/**
 * 供应商
 */
class Supcust extends Super {

    public function index() {
        return $this->fetch('supcust/index');
    }
    
    public function supcustList(){

        	$page =input('page') ? intval(input('page')) : 1;
        	$rows =input('limit') ? intval(input('limit')) : 10;
        	
        	$where="1=1";
        	 
        	$sp_name=input("sp_name");
        	if(!empty($sp_name)){
        		$where.=" and (sp_name like '%$sp_name%' or sp_no like '%$sp_name%' or sp_company like '%$sp_name%')";
        	}
        	
    		$Supcust = new SupModel();
    		$rowCount = $Supcust->where($where)->count();
    		$offset = ($page - 1) * $rows;
    		$list = $Supcust->where($where)->order("sp_id desc")->limit($offset,$rows)->select()->toArray();
    		
    		return listJson(0,lang("success_data"),$rowCount, $list);
    	
    }
	
    //新增或添加供应商
    public function edit() {
    	$model = new SupModel();
    	$sp_id = input('sp_id');
    	if($sp_id){
    		$one = $model->getbyid($sp_id);
    		$this->assign('one', $one);
    	}
    	return $this->fetch("supcust/edit");
    }
    
    //添加供应商
    public function save() {

            $sp_no = input('sp_no');
            $sp_name = input('sp_name');
            $sp_company = input('sp_company');
            $sp_cp_person = input('sp_cp_person');
            $sp_mobile = input('sp_mobile');
            $sp_id=input("sp_id");
            
            $model = new SupModel();
            $error = '';
            if ($sp_no == '') {
                $error = lang("sup_empty_no");
            } elseif ($sp_name == '') {
                $error = lang("sup_empty_name");
            } elseif ($sp_company == '') {
                $error = lang("sup_empty_company");
            } elseif ($sp_mobile == '') {
                $error = lang("sup_empty_mobile");
            }
            
            if($error!=''){
            	$return ['code'] = false;
            	$return ['msg'] = lang("update_error");
            	return $return;
            }
            
            $map=[];
            if(!empty($sp_id)){
            	$map=[
            			['sp_id','<>',$sp_id],
            			['sp_no','=',$sp_no]
            	];
            }else{
            	$map=[
            			['sp_no','=',$sp_no]
            	];
            }
            $sup=$this->findSup($map);
            if($sup){
            	return ['code'=>false,'msg'=>lang("sup_exists_no")];
            }
            
            $data = array(
            		"sp_no" => $sp_no,
            		"sp_name" => $sp_name,
            		"sp_company" => $sp_company,
            		"sp_cp_person" => $sp_cp_person,
            		"sp_mobile" => $sp_mobile
            );

            if(empty($sp_id)){
            	$ok=$model->save($data);
            }else{
            	$ok=$model->save($data,['sp_id'=>$sp_id]);
            }
            
            if ($ok) {
            	$return ['code'] = true;
            	$return ['msg'] = lang("update_success");
            } else {
            	$return ['code'] = false;
            	$return ['msg'] = lang("update_error");
            }
             
            return $return;
        
    }
    
    //检测会员电话号码唯一
    //存在则返回true,不存在返回false
    public function findSup($condition=[]){
    	$model = new SupModel();
    	$sup=$model->where($condition)->find();
    	if(!$sup){
    		return	false;
    	}else{
    		return	$sup;
    	}
    }

    //删除供应商
    public function delSup(){
    	$sp_id= input('sp_id');
    	if(empty($sp_id)){
    		return ['code'=>false,'msg'=>lang("invalid_variable")];
    	}
    	 
    	$arr=strToArray($sp_id);
    	if(count($arr)<=0){
    		return ['code'=>false,'msg'=>lang("invalid_variable")];
    	}
    	 
    	$code=false;
    	$model = new SupModel();
    	$ok = $model->where ( "sp_id in (".implode(",",$arr).")" )->delete ();
    	if ($ok) {
    		$code = true;
    		$msg = lang ( "delete_success" );
    	} else {
    		$msg = lang ( "delete_error" );
    	}
    	 
    	return ['code'=>$code,'msg'=>$msg];
    }

    //查看产品
    public function product() {
    	
    	$sp_no = input('sp_no');
    	$this->assign('sp_no', $sp_no);
    	return $this->fetch('supcust/product');
    
    }
    
    //获取产品数据
    public function prodata(){
    	$sp_no = input('sp_no');
    	$page =input('page') ? intval(input('page')) : 1;
    	$rows =input('limit') ? intval(input('limit')) : 10;
    	$Item_info=new Item_info();
    	$result = $Item_info->GetItemInfoBySupcust($sp_no, $page, $rows);
    	return listJson(0,lang("success_data"),$result['total'], $result['rows']);
    }

}
