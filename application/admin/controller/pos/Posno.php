<?php
namespace app\admin\controller\pos;
use app\admin\controller\Super;

use model\PosStatus;
use model\PosBranch;
/**
 * 登记POS机
 */
class Posno extends Super {
	
	//列表
	public function index() {
		return $this->fetch ( "pos/posno" );
	}
	
	//ajax数据
	public function dataList() {
		$page = input ( "page" );
		$rows = input ( "limit" );
		$page = empty ( $page ) ? 1 : intval ( $page );
		$rows = empty ( $rows ) ? 1 : intval ( $rows );
		
		$posid=input("posid");
		$branchno=input("branchno");
		$where="1=1";
		if(!empty($posid)){
			$where.=" and posid='$posid'";
		}
		if(!empty($branchno)){
			$where.=" and branch_no='$branchno'";
		}

		//查询门店
        $branchInfo=new PosBranch();
		$blist=$branchInfo->GetAllBranch();
		$branchs=[];
		foreach($blist as $val){
		    $branch_no=$val['branch_no'];
            $branchs[$branch_no]=$val['branch_name'];
        }

		$field= "branch_no,posid,hostname,lasttime,lastcashier,posdesc,status,load_flag";
		$model = new PosStatus();
		$count = $model->where($where)->count();
		$offset = ($page - 1) * $rows;
		$limit = $rows;
		$list = $model->where($where)->field($field)->limit($offset,$limit)->select ();
		$rowIndex = ($page - 1) * $rows + 1;
		$listts = array ();
		foreach ( $list as $v ) {
			$tlist = array ();
			$tlist ["rowIndex"] = $rowIndex;
			$tlist ["branch_no"] = $v ["branch_no"];
            $tlist ["branch_name"] = isset($branchs[$v ["branch_no"]])?$branchs[$v ["branch_no"]].'['.$v ["branch_no"].']':$v ["branch_no"];
			$tlist ["posid"] = $v ["posid"];
			$tlist ["hostname"] = $v ["hostname"];
			$tlist ["lasttime"] = $v ["lasttime"];
			$tlist ["lastcashier"] = $v ["lastcashier"];
			$tlist ["posdesc"] = $v ["posdesc"];
			$tlist ["status"] = $v ["status"];
			$tlist ["load_flag"] = $v ["load_flag"];
			$rowIndex ++;
			array_push ( $listts, $tlist );
		}
		
		return listJson(0,'',$count,$listts);
	}
	
	//添加页面
	public function add() {
		$PosBranch=new PosBranch();
		$branch = $PosBranch->select ();
		$this->assign ( "branchs", $branch );
		$this->assign("isadd",1);
		return $this->fetch("pos/posedit");
	}
	
	//编辑页面
	public function edit() {
		
		$posid = input ( 'posid' );
		$PosStatus=new PosStatus();
		$one = $PosStatus->getone ( $posid );
		$this->assign ( "one", $one );

		$PosBranch=new PosBranch();
		$branch = $PosBranch->select ();
		$this->assign ( "branchs", $branch );
		
		return $this->fetch("pos/posedit");
	}
	
	//执行添加或者编辑保存操作
	public function save(){
		
		$act=input("act");
		$model = new PosStatus ();
		$content = array ();
		$posid = input ( 'posid' );
		if (empty($posid)) {
			$return ['code'] = false;
			$return ['msg'] = lang("pos_id_empty");
			return $return;
		}
		
		$content['branch_no'] = input('branch_no');
		$content['posdesc'] = input('posdesc');
        $content['postype'] = input('postype');
		
		$PosBranch=new PosBranch();
		$num = $PosBranch->where("branch_no='{$content ['branch_no']}'")->count();
		if ($num<=0) {
			$return ['code'] = false;
			$return ['msg'] = lang("pos_empty_branch");
			return $return;
		}
		
		if($act=='add'){

            $num = $model->where("posid='{$posid}' and branch_no='{$content['branch_no']}'")->count();
			if ($num > 0) {

				$return ['code'] = false;
				$return ['msg'] = lang("pos_id_exists");
				return $return;
			}
			
			$content['posid']=$posid;
		}
		
		if($act=='add'){
			$ok=$model->save($content);
		}else{
			$ok=$model->save($content,['posid'=>$posid]);
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
	
	//解绑机器
	public function unbind() {
		if (!IS_AJAX) {
			$return ['code'] = false;
			$return ['msg'] = lang("illegal_operate");
			return $return;
		}

		$branch_no = input ( "branch_no" );
		$pos_id = input ( "posid" );
		if (empty ( $branch_no ) || empty ( $pos_id )) {
			$return ['code'] = false;
			$return ['msg'] = lang("invalid_variable");
			return $return;
		}
		
		$PosStatus=new PosStatus();
		$result = $PosStatus->UnBind ( $branch_no, $pos_id );
		switch ($result) {
			case 1 :
				$return ['code'] = true;
				$return ['msg'] = lang("pos_unbind_success");
				return $return;
			case - 1 :
				$return ['code'] = false;
				$return ['msg'] = lang("pos_unbind_pos");
				return $return;
			default :
				$return ['code'] = false;
				$return ['msg'] = lang("pos_unbind_fail");
				return $return;
		}
	}
	
	//禁用
	public function stop() {
		if (! IS_AJAX) {
			$return ['code'] = false;
			$return ['msg'] = lang("illegal_operate");
			return $return;
		}
		
		$branch_no = input ( "branch_no" );
		$pos_id = input ( "posid" );
		if (empty ( $branch_no ) || empty ( $pos_id )) {
			$return ['code'] = false;
			$return ['msg'] = lang("invalid_variable");
			return $return;
		}
		
		$PosStatus=new PosStatus();
		$result = $PosStatus->UpdateStatus ( $branch_no, $pos_id );
		switch ($result) {
			case 1 :
				$return ['code'] = true;
				$return ['msg'] = lang("update_success");
				return $return;
			case - 1 :
				$return ['code'] = false;
				$return ['msg'] = lang("pos_un_exists");
				return $return;
			default :
				$return ['code'] = false;
				$return ['msg'] = lang("update_error");
				return $return;
		}
	}
	
	// 删除POS机记录
	public function deletePos() {
		$posid = input ( 'posid' );
        $branch_no = input ( 'branch_no' );
		$PosStatus=new PosStatus();
		$result = $PosStatus->where ( [
            "branch_no" => $branch_no,
            "posid" => $posid
        ] )->find();
		if ($result) {
			// 删除对应的POS机，做提示
			$ok = $PosStatus->deleteOne ( $posid,$branch_no );
			if ($ok) {
				$return ['code'] = true;
				$return ['msg']=lang("pos_delete_succ");
			} else {
				$return ['code'] = false;
				$return ['msg']=lang("pos_delete_fail");
			}
		} else {
				$return ['code'] = false;
				$return ['msg']=lang("pos_un_exists");
		}
		return $return;
	}
}
