<?php
namespace app\admin\controller;
use app\admin\controller\Super;
use model\Member as MModel;
use model\MemberLevel;
use model\BaseModel;
use model\BaseCode;
use think\Db;

/**
 * 会员管理
 */
class Member extends Super {
	
	
	//获取会员等级
	public function getLevels(){
		$MemberLevel=new MemberLevel();
		$list=$MemberLevel->order("lid asc")->select();
		$arr=[];
		foreach($list as $k=>$value){
			$arr[$value['lid']]=$value;
		}
		return $arr;
	}
	//会员列表
    public function index() {
    	$levels=$this->getLevels();
    	$this->assign("levels",$levels);
        return $this->fetch("member/index");            
    }
    
    //返回列表数据
    public function memeberList(){
    	
    	$page =input('page') ? intval(input('page')) : 1;
    	$rows =input('limit') ? intval(input('limit')) : 10;
    	
    	$where="1=1";
    	 
    	$uname=input("uname");
    	if(!empty($uname)){
    		$where.=" and uname like '%$uname%'";
    	}
    	 
    	$level=input("level",0,"intval");
    	if(!empty($level)){
    		$where.=" and level='$level'";
    	}

    	$model=new MModel();
    	$rowCount = $model->where($where)->count();
    	$offset = ($page - 1) * $rows;
    	$list = $model->where($where)->limit($offset,$rows)->select()->toArray();
    	foreach($list as $k=>$value){
    		if(isset($levels[$value['level']])){
    			$list[$k]['level']=$levels[$value['level']]['levelname'];
    		}
    		$list[$k]['regtime']=date('Y-m-d',$value['regtime']);
    	}
    	return listJson ( 0, '', $rowCount, $list);
    }

    //添加或者编辑会员
    public function editMember(){
    	
    	$levels=$this->getLevels();
    	$this->assign("levels",$levels);
    	
    	$uid=input("uid");
    	if($uid){
    		$one=MModel::get($uid);
    		$this->assign("one",$one);
    	}
    	return $this->fetch("member/edit");
    }
    
    //保存会员信息
    public function save(){
    	$ucode = input('ucode');
    	$username = input('uname');
    	$nickname = input('nickname');
    	$level = input('level');
    	$mobile = input('mobile');
    	$phone = input('phone');
    	$telphone = input('telphone');
    	$email = input('email');
    	$qq = input('qq');
    	$wechat = input('wechat');
    	$address = input('address');
    	$passwd=input("passwd");
    	$passwd2=input("passwd2");
    	$account=input("account");
    	$frozen_account=input("frozen_account");
    	$credit=input("credit");
    	$openid=input("openid");
    	$status=input("status");
    	$branch_no=input("branch_no");
    	$time=$this->_G['time'];
    	$uid=input("uid");
    	
    	if($passwd!=$passwd2&&!empty($passwd)){
    		return ['code'=>false,'msg'=>lang("member_passwd_unequal")];
    	}
    	
    	//检测会员名称
    	if(empty($uid)&&!empty($username)){
    		$user=$this->findUser(['uname'=>$username]);
    		if($user){
    			return ['code'=>false,'msg'=>lang("member_uname_occupied")];
    		}
    	}
    	
    	//检测电话号码
    	if(!empty($mobile)){
    		$map=[];
    		if(!empty($uid)){
    			$map=[
    				['uid','<>',$uid],
    				['mobile','=',$mobile]
    			];
    		}else{
    			$map=[
    				['mobile','=',$mobile]
    			];
    		}
    		$user=$this->findUser($map);
    		if($user){
    			return ['code'=>false,'msg'=>lang("member_mobile_occupied")];
    		}
    	}
    	
    	//检测用户编码
    	if(!empty($ucode)){
    		$map=[];
    		if(!empty($uid)){
    			$map=[
    					['uid','<>',$uid],
    					['ucode','=',$ucode]
    			];
    		}else{
    			$map=[
    					['ucode','=',$ucode]
    			];
    		}
    		$user=$this->findUser($map);
    		if($user){
    			return ['code'=>false,'msg'=>lang("member_ucode_occupied")];
    		}
    	}
    	
    	if ($username == '') {
    		$error = lang("member_uname_empty");
    		return ['code'=>false,'msg'=>$error];
    	}
    	
    	$model = new MModel();
    	
		$data = array (
				"uname" => $username,
				"nickname" => $nickname,
				"mobile" => $mobile,
				"ucode" => $ucode,
				"level" => $level,
				"account" => $account,
				"frozen_account" => $frozen_account,
				"credit" => $credit,
				"openid" => $openid,
				"wechat" => $wechat,
				"phone" => $phone,
				"email" => $email,
				"address" => $address,
				"status" => $status,
				"qq" => $qq,
				"branch_no" => $branch_no
		);
		
		if($uid){
			unset($data['uname']);
		}else{
			$data ['regtime'] = $time;
			$data ['addtime'] = $time;
		}
		
		if(!empty($passwd)){
			$data['passwd']=md5($passwd);
		}
		
		if(empty($uid)){
			$ok=$model->save($data);
		}else{
			$ok=$model->updateData(['uid'=>$uid],$data);
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
    
    
   	//删除会员
   	public function delMember(){
   		$uid = input('uid');
   		if(empty($uid)){
   			return ['code'=>false,'msg'=>lang("invalid_variable")];
   		}
   		
   		$arr=strToArray($uid);
   		if(count($arr)<=0){
   			return ['code'=>false,'msg'=>lang("invalid_variable")];
   		}
   		
   		$code=false;
		$model = new MModel ();
		$ok = $model->where ( "uid in (".implode(",",$arr).")" )->delete ();
		if ($ok) {
			$code = true;
			$msg = lang ( "delete_success" );
		} else {
			$msg = lang ( "delete_error" );
		}
   		
   		return ['code'=>$code,'msg'=>$msg];
   	}
   	
   	//检测会员电话号码唯一
   	//存在则返回true,不存在返回false
   	public function findUser($condition=[]){
   		$model = new MModel ();
   		$user=$model->where($condition)->find();
   		if(!$user){
   			return	false;
   		}else{
   			return	$user;
   		}
   	}
     
   	//会员等级列表
    public function level() {
       return $this->fetch("member/level");
    }
    
    //会员等级分页数据
    public function levelList(){
    	$page =input('page') ? intval(input('page')) : 1;
    	$rows =input('limit') ? intval(input('limit')) : 10;
    	
    	$model=new MemberLevel();
    	$rowCount = $model->count();
    	$offset = ($page - 1) * $rows;
    	$list = $model->limit($offset,$rows)->select()->toArray();
    	
    	//查询会员级别代码
    	$BaseCode=new BaseCode();
    	$levelCode=$BaseCode->GetBaseCode("ML");
    	$temp=[];
    	foreach($levelCode as $value){
    		$temp[$value['code_id']]=$value['code_name'];
    	}
    	
    	if($list&&count($list)>0){
    		foreach($list as $k=>$value){
    			$list[$k]['code_name']=$temp[$value['code']];
    		}
    	}
    	return listJson ( 0, '', $rowCount, $list);
    }
    
    //显示编辑会员等级
    public function levelEdit(){
    	$lid=input("lid");
    	if($lid){
    		$one=MemberLevel::get($lid);
    		$this->assign("one",$one);
    	}
    	
    	//查询会员级别代码
    	$BaseCode=new BaseCode();
    	$levelCode=$BaseCode->GetBaseCode("ML");
    	$this->assign("levelCode",$levelCode);
    	
    	return $this->fetch("member/level_edit");
    }
    
    //添加会员等级
    public function saveLevel()
    {
		$levelname = input ( 'levelname' );
		$discount = input ( 'discount' );
		$sale = input ( 'sale' );
		$lid=input("lid");
		$code = input ( 'code' );
		
		$model = new MemberLevel ();
		if (empty($levelname)) {
			return ['code'=>false,'msg'=>lang("member_levelname_empty")];
		}
		
		$data = array (
				"code" => $code,
				"levelname" => $levelname,
				"discount" => $discount,
				"sale" => $sale
		);

		if(empty($lid)){
			$ok=$model->save($data);
		}else{
			$ok=$model->save($data,['lid'=>$lid]);
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
   
    //删除会员等级
    public function delLevel(){
    	
    	$lid = input('lid');
    	if(empty($lid)){
    		return ['code'=>false,'msg'=>lang("invalid_variable")];
    	}
    	 
    	$arr=strToArray($lid);
    	if(count($arr)<=0){
    		return ['code'=>false,'msg'=>lang("invalid_variable")];
    	}
    	 
    	$code=false;
    	$model = new MemberLevel();
    	$ok = $model->where ( "lid in (".implode(",",$arr).")" )->delete ();
    	if ($ok) {
    		$code = true;
    		$msg = lang ( "delete_success" );
    	} else {
    		$msg = lang ( "delete_error" );
    	}
    	 
    	return ['code'=>$code,'msg'=>$msg];
    	
    }
    
    //导出会员
    public function export()
    {
    	$field=[
    					"uname","ucode","nickname","mobile","level",
    					"account","frozen_account","credit","openid",
    					"wechat","email","phone","address","status",
    					"qq","branch_no","regtime","logintime"
    	];
    	$title= [
    							'会员名称','会员编码','真实名称','会员手机','会员等级',
    							'用户余额','冻结余额','积分','微信openid',
    							'微信','邮箱','联系电话','地址','状态','qq','门店编号',
    							'注册时间','最后登录时间'
    			];
    	
    	$levels=$this->getLevels();
    	
    	$model=new MModel();
    	$list = $model->where($where)->select()->toArray();
    	foreach($list as $k=>$value){
    		if(isset($levels[$value['level']])){
    			$list[$k]['level']=$levels[$value['level']]['levelname'];
    		}
    		
    		$list[$k]['regtime']='';
    		if($value['regtime']>0){
    			$list[$k]['regtime']=date('Y-m-d',$value['regtime']);
    		}
    		
    		$list[$k]['logintime']='';
    		if($value['logintime']>0){
    			$list[$k]['logintime']=date('Y-m-d',$value['logintime']);
    		}
    		
    		$list[$k]['status']='正常';
    		if($value['status']==2){
    			$list[$k]['status']='冻结';
    		}
    	}
    	
    	$doc['title']='会员数据表';
    	
    	$this->export_csv($list, $field, $title, $doc);
    	
    }
 

}
