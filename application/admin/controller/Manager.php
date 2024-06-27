<?php
namespace app\admin\controller;
use app\admin\controller\Super;

use model\Operator;
use model\SysManager;
use util\Image;
/**
 * 管理员/操作员
 *
 */
class Manager extends Super {

   public function index() {
         return $this->fetch('manager/index');
    }
    
    //ajax json数据
    public function dataList(){
    	$SysManager = new SysManager();
	   	$page =input('page') ? intval(input('page')) : 1;
	   	$rows =input('limit') ? intval(input('limit')) : 10;
    	
    	$rowCount = $SysManager->count();
    	$offset = ($page - 1) * $rows;
    	$list = $SysManager->limit($offset,$rows)->select()->toArray();
    	foreach($list as $k=>$value){
    		$list[$k]['status']=$value['status']==1?lang("manager_status_ok"):lang("manager_status_off");
    	}

    	return listJson(0,lang("success_data"),$rowCount, $list);
    }

    //显示添加用户页面
    public function addUser(){
    	
    	//用户角色
    	$role =new Operator();
    	$rolelist = $role->select();
    	$this->assign('rolelist', $rolelist);
    	
    	$id=input("id");
    	if(!empty($id)){
    		$one=SysManager::get($id);
    		$this->assign('one', $one);
    	}
    	
    	return $this->fetch('manager/add');
    }
    
    //执行添加或编辑操作
    public function save() {
        $msgError = '';
		$model = new SysManager ();
		$loginname = input ( 'loginname' );
		$password = input ( 'password' );
		$repassword = input ( 'repassword' );
		$username = input ( 'username' );
		$status = input ( 'status' );
		$role = input ( 'role' );
		$id=intval(input("id"));
		
		if ($loginname == '') {
			$msgError = lang("manager_loginname_empty");
		} elseif ($username == '') {
			$msgError = lang("manager_name_empty");
		} 
		
		if(empty($id)){
			if($password == ''){
				$msgError = lang("manager_pass_empty");
			}elseif ($model->checkloginname ( $loginname ) > '0') {
				$msgError = lang("manager_loginname_exist");
			}
		}
		
		if(!empty($password)&&$password != $repassword){
			$msgError = lang("manager_pass_unequal");
		}
		
		if($msgError != ''){
			return ['code'=>false,'msg'=>$msgError];
		}
		
		$roles = explode ( '|', $role );
		$manager = array (
				"loginname" => $loginname,
				"password" => md5 ($password),
				"username" => $username,
				"status" => $status,
				"rid" => $roles ['0'],
				"rname" => $roles ['1'] 
		);
		
		if(empty($id)){
			$ok=$model->save($manager);
		}else{
			if(empty($password)){
				unset($manager['password']);
			}
			unset($manager['loginname']);
			$ok=$model->save($manager,['id'=>$id]);
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

    //删除用户
    public function del() {
        $id = intval(input('id'));
        if ($id == '1') {
          
        }
        $code=false;
        if ($id) {
        	$user=SysManager::get($id);
            $ok=$user->delete();
            
        	if($ok){
         		$code=true;
         		$msg=lang("delete_success");
         	}else{
         		$msg=lang("delete_error");
         	}
        }
        return ['code'=>$code,'msg'=>$msg];
    }
    
    //显示清除缓存页面
    public function cleanup(){
    	return $this->fetch('manager/cleanup');
    }

    //清除缓存
    public function cleanCache() {
        $cachedir = ROOT_PATH."runtime";
       	$this->readDir($cachedir);
       	return ['code'=>true,'msg'=>lang("update_success")];
    }
    
    //遍历文件夹内的文件
    public function readDir($path){
    	$current_dir = opendir($path);
    	while (($file = readdir($current_dir)) !== false) {
    		$name = $path . DIRECTORY_SEPARATOR . $file;
    		if ($file != '.' && $file != '..') {
    			if(!is_dir($name)){
    				unlink($name);
    			}else{
    				$this->readDir($name);
    			}
    		}
    	}
    }
    
    //显示压缩图片页面
    public function compressImg(){
    	return $this->fetch('manager/compress');
    }
    
    //压缩产品图片
    public function doCompresImg(){
    	set_time_limit(0);
    	$targetPath =C( 'uploads_path' );
    	$list=$this->myScanDir($targetPath);
    
    	foreach($list as $value){
    		$filesize=filesize($value);
    		$size=round($filesize / 1048576 * 100) / 100;
    		if($size>=0.5){
    			//等比例压缩，不裁减
    			Image::thumb($value, $value,'',1280,1280);
    		}	
    	}
    	
    	return ['code'=>true,'msg'=>lang("update_success")];
    }
    
    //遍历目录
    private function myScanDir($dir)
    {
    	$file_arr = scandir($dir);
    	$new_arr = [];
    	$imgExt=['jpg','png','jpeg'];
    	foreach($file_arr as $item){
    		if($item!=".." && $item !="."){
    			if(is_dir($dir."/".$item)){
    				$list= $this->myScanDir($dir."/".$item);
    				$new_arr=array_merge($new_arr,$list);
    			}else{
    				$fileParts = pathinfo ( $item);
    				$extension=strtolower($fileParts["extension"]);
    				if(in_array($extension, $imgExt)){
    					$new_arr[] =$dir."/".$item;
    				}
    			}
    		}
    	}
    	return $new_arr;
    }
}
