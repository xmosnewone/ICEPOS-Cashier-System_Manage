<?php
namespace app\admin\controller\pos;
use app\admin\controller\Super;
use app\admin\components\Enumerable\EOperStatus;
use model\SysManager;
use model\PosOperator;
use model\PosOperatorBreakpoint;
use model\PosBranch;

/**
 * 营业员管理
 *
 */
class Operator extends Super {
	
	//营业员列表
	public function index() {
		return $this->fetch("pos/operator");
	}
	
	public function dataList() {

			$PosOperator = new PosOperator();
			$page = input ( "page" );
			$rows = input ( "limit" );
			$page = empty ( $page ) ? 1 : intval ( $page );
			$rows = empty ( $rows ) ? 1 : intval ( $rows );
			
			$oper_id = input('oper_id');
			$branchno=input("branchno");
			$where="1=1";
			if(!empty($oper_id)){
				$where.=" and oper_id='$oper_id'";
			}
			if(!empty($branchno)){
				$where.=" and branch_no='$branchno'";
			}
			
			$rowCount = $PosOperator->where($where)->count();
			$offset = ($page - 1) * $rows;
			$limit = $rows;
			$list = $PosOperator->where($where)->field("*")->limit($offset,$limit)->order("last_time desc")->select ();
			$temp = array ();
			$rowIndex = ($page - 1) * $rows + 1;
			foreach ( $list as $v ) {
				$tt = array ();
				$tt = $v;
				$tt ["rowIndex"] = $rowIndex;
				$rowIndex ++;
				array_push ( $temp, $tt );
			}

			return listJson(0,'',$rowCount,$temp);
		
	}
	
	//添加页面
	public function add() {
		$PosBranch=new PosBranch();
		$branch = $PosBranch->select ();
		$this->assign ( "branchs", $branch );
		$this->assign("isadd",1);
		return $this->fetch("pos/operedit");
	}
	
	//编辑页面
	public function edit() {
	
		$oper_id = input( 'oper_id' );
		$PosOperator=new PosOperator();
		$one =$PosOperator->where("oper_id='$oper_id'")->find();
		$this->assign ( "one", $one );
	
		$PosBranch=new PosBranch();
		$branch = $PosBranch->select ();
		$this->assign ( "branchs", $branch );
	
		return $this->fetch("pos/operedit");
	}
	
	//执行添加或者编辑保存操作
	public function save(){
	
		$act=input("act");
		$model = new PosOperator ();
		
		$oper_id = input( 'oper_id' );
		$passwd=input( 'oper_pw' );
		if ($oper_id== '') {
			$return ['code'] = false;
			$return ['msg'] = lang("oper_no_empty");
			return $return;
		}
		if ($act == "add") {
			$num = $model->where("oper_id='{$oper_id}'")->count();
			if ($num > 0) {
				$return ['code'] = false;
				$return ['msg'] = lang("oper_no_exists");
				return $return;
			}
			
			if(empty($passwd)){
				$return ['code'] = false;
				$return ['msg'] = lang("oper_empty_passwd");
				return $return;
			}
			
		} else {
			$ppmodel=new PosOperator ();
			$model = $ppmodel->where("oper_id='{$oper_id}'")->find ();
		}
		
		$content = array ();
		$content ['oper_id']=$oper_id;
		$content ['branch_no'] = input( 'branch_no' );
		$content ['oper_name'] = input( 'oper_name' );
		$content ['oper_status'] =1;
		$content ['oper_type'] = 1;
		
		
		if(!empty($passwd)){
			$content ['oper_pw'] = md5 (input( 'oper_pw' ));
		}
		
		if($act=='add'){
			$ok=$model->save($content);
		}else{
			$ok=$model->save($content,['oper_id'=>$oper_id]);
		}
		
		if ($ok) {
			//同步到POS终端记录表
			$bdBreakPoint = new PosOperatorBreakpoint ();
			if ($act === "add") {
				$bdBreakPoint->rtype = EOperStatus::ADD;
			} else {
				$bdBreakPoint->rtype = EOperStatus::UPDATE;
			}
			$bdBreakPoint->branch_no = $content ["branch_no"];
			$bdBreakPoint->oper_id = $content ["oper_id"];
			$bdBreakPoint->updatetime = date ( DATETIME_FORMAT );
			$bdBreakPoint->save ();
			
			$return ['code'] = true;
			$return ['msg'] = lang("update_success");
		} else {
			$return ['code'] = false;
			$return ['msg'] = lang("update_error");
		}
		
			return $return;
	}
	

	// 获取对应的最新的编号
	public function getoperid() {
		$branch_no = strtoupper(input('branch_no'));
		// 自动推荐该门店下面的最新一个操作员编号
		$PosOperator=new PosOperator();
		$num = $PosOperator->where("branch_no='$branch_no'")->count();
		if ($branch_no != 'ALL') {
			$SysManager=new SysManager();
			$branch_no=strtolower ( $branch_no );
			$user = $SysManager->where("loginname='$branch_no'")->find();
			$oper_id = $user->id . ($num + 1);
			$oper_id=$PosOperator->patchId($oper_id);
		} else {
			$oper_id = "ALL" . ($num + 1);
		}
		
		echo $oper_id;
		exit();
	}
	
	// 删除营业员
	public function deleteOperator() {

		$oper_id = input( 'oper_id' );
		$branch_no = input( 'branch_no' );
		$PosOperator=new PosOperator();
		$result = $PosOperator->getone ( $branch_no, $oper_id );
		if (!$result) {
			$return ['code'] = false;
			$return ['msg']=lang("oper_not_exists");
			return $return;
		}
			
		// 新增operator_breakpoint 记录 D
		$bdBreakPoint = new PosOperatorBreakpoint ();
		$bdBreakPoint->rtype = EOperStatus::DELETE;
		$bdBreakPoint->branch_no = $branch_no;
		$bdBreakPoint->oper_id = $oper_id;
		$bdBreakPoint->updatetime = date ( DATETIME_FORMAT );
		$bdBreakPoint->save ();
		
		$ok = $PosOperator->deleteOne ( $branch_no, $oper_id );
		if ($ok) {
			$return ['code'] = true;
			$return ['msg']=lang("oper_delete_succ");
		} else {
			$return ['code'] = false;
			$return ['msg']=lang("oper_delete_fail");
		}
		
		return $return;
	}
}
