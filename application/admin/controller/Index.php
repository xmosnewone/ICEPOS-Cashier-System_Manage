<?php
namespace app\admin\controller;
use app\common\service\Helper;
//首页
class Index extends Super
{
	//菜单项目
	public function menu(){
		
		$helper=new Helper();
		$menu=$helper->getUserMenu();
		//输出菜单json
		$json=$helper->pearMenu($menu);
	
		return $json;
		
	}
	
    public function index() {
    	$this->menu();
    	$this->assign("username",$this->_G['username']);
        return $this->fetch();
    }
    
}
