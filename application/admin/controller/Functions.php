<?php
namespace app\admin\controller;
use app\admin\controller\Super;
use app\common\service\Helper;

use model\Fun;
use think\Db;
/**
 * 功能管理
 *
 */
class Functions extends Super {

	private function getNodeTree(){
		$nodes = M ( "function" )->order("orderby asc,id asc")->select ();
		$helper=new Helper();
		$list=$helper->getNodeTree($nodes);
		$this->assign ( 'total', count($nodes) );
		$this->assign ( 'list', $list );
	}
	
	//显示权限列表
	public function index(){
		$this->getNodeTree();
		return $this->fetch("function/index");
	}
	
	//显示添加功能节点
	public function addFunc(){
		$id=input("id");
		if(!empty($id)){
			$one= Fun::get($id);
			$this->assign("one",$one);
		}
		
		$this->getNodeTree();
		
		return $this->fetch("function/add");
	}
	
	//保存节点
	public function saveFunc(){
		$id=input('id');
		$parent=input('parentid');
		$name=input('name');
		$code=input('code','','trim');
		$fun_url=input('url');
		$icon=input('icon');
		$is_display=input('is_display');
		$orderby=intval(input("orderby"));
		if($name==''){
			$r['code']=false;
			$r['msg']=lang("func_name_empty");
			return $r;
		}
		if($code==''){
			$r['code']=false;
			$r['msg']=lang("func_code_empty");
			return $r;
		}
		
		//获取所有功能数组
		$funs = M ( "function" )->order("orderby asc,id asc")->select ();
		$helper=new Helper();
		$funs=$helper->keyFuncs($funs);
	
		$now=$this->_G['time'];
		$model=new Fun();
		$arr=array(
				"name"=>$name,
				"code"=>$code,
				"url"=>$fun_url,
				"icon"=>$icon,
				"parent"=>$parent,
				"add_time"=>$now,
				"orderby"=>$orderby,
				"is_display"=>$is_display,
		);
		
		if($parent>0){
			$pids=$helper->getFuncPid($funs,$parent);
			//获取上级数量
			$level=count($pids);
			$arr['level']=$level+2;
		}else{
			$arr['level']=1;
		}
	
		if(!empty($id)){
			$result=$model->save($arr,['id'=>$id]);
		}else{
			$result=$model->save($arr);
		}
		if($result){
			$r['code']=true;
			$r['msg']=lang("save_success");
			//写入换存
			cache_functions();
		}
		else{
			$r['code']=false;
			$r['msg']=lang("save_error");
		}
		return $r;
	}
	
	//删除节点
	public function del() {
	    $id = input("id");
	    $fun = new Fun();
	    $child=$fun->where("parent='$id'")->count();
	    if($child>0){
	    	return ['code'=>false,'msg'=>lang("func_child_notempty")];
	    }
	    if ($id) {
	        $Fun = Fun::get($id);
	        $Fun->delete();
	        $code=true;
	        $msg=lang("delete_success");
	    }else{
	    	$code=false;
	    	$msg=lang("delete_error");
	    }
	    return ['code'=>$code,'msg'=>$msg];
	}
}
