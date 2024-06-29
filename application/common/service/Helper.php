<?php
namespace app\common\service;

use model\Operator;
use model\Fun;

class Helper
{
	//上级id数组
	public $pid=[];
	
	/**
	 * 获取当前用户的权限菜单
	 */
	public function getUserMenu(){
		//查询当前权限
		$role= new Operator();
		$rid=session("rid");
		$res=$role->where("rid='$rid'")->find();
		$perlist='';
		if($res['perm']!='0'){
			$perm=json_decode($res['perm'],true);
			foreach($perm as $k=>$v){
				$perlist.="'".$v."',";
			}
			$perlist=rtrim($perlist,",");
		}
		
		//查询所有栏目
		$model=new Fun();
		$sql="(parent='' or parent is null or parent='0')";
		if($perlist!=''&&$res['perm']!='0'){
			$sql.=" and id in (".$perlist.")";
		}
		$sql.="  and is_display=1";
		$res=$model->where($sql)->order("orderby asc")->select()->toArray();
		
		$menus=[];
		
		//一级菜单
		foreach($res as $k=>$v){
			
			$menus[$v['id']]=$v;
			
			//查询二级菜单
			$sql="parent='".$v['id']."'";
			if($perlist!=''&&$res['perm']!='0'){
				$sql.=" and id in (".$perlist.")";
			}
			$sql.=" and is_display=1";
			$res2=$model->where($sql)->order("id asc")->select()->toArray();
			
			if(!$res2){
				continue;
			}
			
			foreach($res2 as $kk=>$vv){
				$menus[$v['id']]['children'][$vv['id']]=$vv;
				
				//查询用有权限
				$sql="parent='".$vv['id']."' and is_display=1";
				if($perlist!=''&&$res['perm']!='0'){
					$sql.=" and id in (".$perlist.")";
				}
				//查询三级级菜单
				$res3=$model->where($sql)->select()->toArray();
				
				if(!$res3){
					continue;
				}
				
				foreach($res3 as $kkk=>$vvv){
					$menus[$v['id']]['children'][$vv['id']]['children'][$vvv['id']]=$vvv;
				}
			}
			
		}
		
		return $menus;
	}
	
	/**
	 * 按照Pear的菜单json形式输出菜单
	 */
	public function pearMenu($menus){
		
		$pear=[];
		//遍历一级菜单
		foreach($menus as $v){
			$menu=[];
			$menu['id']=$v['id'];
			$menu['title']=$v['name'];
			$menu['type']=$v['url']!=''?'1':'0';
			$menu['icon']='layui-icon '.$v['icon'];
			$menu['href']=$v['url'];
			
			if(!isset($v['children'])){
				$menu['children']=[];
				$pear[]=$menu;
				continue;
			}
			
			//记录所有的二级菜单
			$children=[];
			//遍历二级菜单
			$vchildren=$v['children'];
			foreach($vchildren as $vv){
				$temp=[];
				$temp['id']=$vv['id'];
				$temp['title']=$vv['name'];
				$temp['type']=$vv['url']!=''?'1':'0';
				$temp['icon']='layui-icon '.$vv['icon'];
				$temp['href']=$vv['url'];
				
				//是否存在三级菜单
				if(!isset($v['children'][$vv['id']]['children'])){
					$temp['type']='1';
					$temp['children']=[];
					$children[]=$temp;
					continue;
				}
				
				$vvchildren=$v['children'][$vv['id']]['children'];
				
				//三级菜单
				$cate=[];
				foreach($vvchildren as $vvv){
					$ctemp=[];
					$ctemp['id']=$vvv['id'];
					$ctemp['title']=$vvv['name'];
					$ctemp['type']=$vvv['url']!=''?'1':'0';
					$ctemp['icon']='layui-icon '.$vvv['icon'];
					$ctemp['href']=$vvv['url'];
					$cate[]=$ctemp;
				}
				
				$temp['children']=$cate;
				
				$children[]=$temp;
			}
			
			$menu['children']=$children;
			
			$pear[]=$menu;
		}
		
		return $pear;
	}
	
	/**
	 * 循环获取最顶级父类id
	 * 菜单权限缓存数组 $node_cache
	 */
	public function getFuncPid($functions,$id){
		
		if(isset($functions[$id])){
			if($functions[$id]['parent']>0){
				$this->pid[]=$functions[$id]['parent'];
				self::getFuncPid($functions, $functions[$id]['parent']);
			}
		}
	
		return $this->pid;
	}
	
	/**
	 * 获取功能数组，用id做键
	 */
	public function keyFuncs(){
		$funcs=M ( "function" )->order("orderby asc,id asc")->select ();
		$return=[];
		foreach($funcs as $k=>$value){
			$return[$value['id']]=$value;
		}
		return $return;
	}
	
	/**
	 * 将数组变成多级数组并返回
	 * @param 原始数组 $data
	 * @param 父级字段英文名称 $parentField
	 * @param 父级id $pid
	 */
	public function childData($data,$parentField,$pid){
		$return=[];
		foreach($data as $value){
			if($value[$parentField]==$pid){
				$return[$value['id']]=$value;
			}
		}
		return $return;
	}
	
	/**
	 * 权限树菜单
	 */
	public function getNodeTree($nodes){
	
		$first=array();
		if ($nodes&&count($nodes)>0) {
			//四级菜单
			$first=$this->childData($nodes,'parent',0);
			if(count($first)>0){
				foreach($first as $k=>$value){
					$second=$this->childData($nodes,'parent',$value['id']);
					if(count($second)>0){
						foreach($second as $vs){
							$third=$this->childData($nodes,'parent',$vs['id']);
							if(count($third)>0){
								foreach($third as $vvs){
									$forth=$this->childData($nodes,'parent',$vvs['id']);
									if(count($forth)>0){
										foreach($forth as $vvvs){
											$fiveth=$this->childData($nodes,'parent',$vvvs['id']);
											if(count($fiveth)>0){
												$forth[$vvvs['id']]['sub_menu']=$fiveth;
											}
										}
										$third[$vvs['id']]['sub_menu']=$forth;
									}
								}
								$second[$vs['id']]['sub_menu']=$third;
							}
						}
						$first[$value['id']]['sub_menu']=$second;
					}
				}
			}
	
		}
	
		return $first;
	}
	
}