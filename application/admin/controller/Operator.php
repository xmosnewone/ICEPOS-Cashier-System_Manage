<?php
namespace app\admin\controller;
use app\admin\controller\Super;
use app\common\service\Helper;
use model\Operator as OperatorModel;
/**
 * 角色管理
 *
 */
class Operator extends Super {
	
	private function getNodeTree(){
		$nodes = M ( "function" )->order("orderby asc,id asc")->select ();
		$helper=new Helper();
		$list=$helper->getNodeTree($nodes);
		$this->assign ( 'funcList', $list );
	}
	//角色列表页
   public function index() {
       return $this->fetch('operator/index');
   }
   
   //json数据
   public function roleList(){
   	
	   	$operator = new OperatorModel();
	   	$page =input('page') ? intval(input('page')) : 1;
	   	$rows =input('limit') ? intval(input('limit')) : 10;
	   	$type = input('type');
	
	   	$rowCount = $operator->count();
	   	$offset = ($page - 1) * $rows;
	   	$list = $operator->limit($offset,$rows)->select();
	   	
	   	return listJson(0,lang("success_data"),$rowCount, $list);
   }
   
   //添加角色
   public function addRole(){
   		//功能列表
   		$this->getNodeTree();
   		//本角色的数据
   		$id=input("id");
   		if($id){
   			$one=OperatorModel::get($id);
   			if($one['perm']=='0'){
   				$perms=array($one['perm']);
   			}else{
   				$perms=json_decode($one['perm'],true);
   			}
   			$this->assign("one",$one);
   			$this->assign("perms",$perms);
   		}
   		
   		return $this->fetch('operator/add');
   }
   
   //权限列表树
   public function save(){
   		$id=intval(input("id"));
	   	$name = trim(input("name"));
	   	$perms =input("perms/a");
	   	
	   	if (empty($name)) {
	   		return ['code'=>false,'error'=>lang("role_name_empty")];
	   	}
	   	
	   	if (strlen($name) > 64) {
	   		return ['code'=>false,'error'=>lang("role_name_long")];
	   	}
	   	
	   	if (empty($perms)||count($perms)<=0) {
	   		$strPerms = "0";
	   	}else {
	   		sort($perms);
	   		$strPerms =json_encode($perms);
	   	}
	   	
	   	$Operator = new OperatorModel();
	   	if(empty($id)){
	   		$num=$Operator->where("name='$name'")->count();
	   		if($num>0){
	   			return ['code'=>false,'error'=>lang("role_name_exist")];
	   		}
	   	}
	  
   		$uid = rand(1, 999999 * microtime());
   		$role = array(
   				'rid' => $uid,
   				'name' => $name,
   				'add_time' => date("Y-m-d H:i:s", $this->_G['time']),
   				'last_time' => date("Y-m-d H:i:s", $this->_G['time']),
   				'perm' => $strPerms,
   		);
   	
   		if(empty($id)){
   			$ok=$Operator->save($role);
   		}else{
   			unset($role['rid']);
   			$ok=$Operator->save($role,['id'=>$id]);
   		}
   		if($ok){
   			$code=true;
   			$msg=lang("save_success");
   			
   		}else{
   			$code=false;
   			$msg=lang("save_error");
   		}
   		
   		return ['code'=>$code,'msg'=>$msg];
	   	
   }

	//删除角色
   public function del() {
      $id = input("id");
      if ($id) {
        	
            $role = OperatorModel::get($id);
            if(!$role){
            	return ['code'=>false,'msg'=>lang("role_empty")];
            }
            
            //角色下面有用户
            $manager=M("sys_manager")->where("rid='$id'")->count();
            if($manager>0){
            	return ['code'=>false,'msg'=>lang("role_manager_exist")];
            }
            
            $ok=$role->delete();
         	if($ok){
         		$code=true;
         		$msg=lang("delete_success");
         	}else{
         		$code=false;
         		$msg=lang("delete_error");
         	}
      }
    	return ['code'=>$code,'msg'=>$msg];
   }

}
