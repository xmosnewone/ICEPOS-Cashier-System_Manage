<?php
namespace app\admin\controller;
use model\Integral as IntegralDb;

/**
 * 积分设置
 */
class Integral extends Super {
	
	// 列表
	public function index() {
		return $this->fetch ( "integral/index" );
	}
	
	// 数据搜索列表
	public function search() {
		$start = input ( "start" );
		$end = input ( "end" );
		$branch_no = input ( "branch_no" );
		$status = input ( "status" );
		$page = input ( "page" );
		$rows = input ( "limit" );
		$page = empty ( $page ) ? 1 : intval ( $page );
		$rows = empty ( $rows ) ? 1 : intval ( $rows );
        //前端页面从1开始，2是启用，1是暂停
        $status=$status>0?$status-1:$status;
		$IntegralDb = new IntegralDb ();
		$res = $IntegralDb->SearchIntegral( $start, $end, $branch_no, $status, $page, $rows );
		if(is_array($res ['rows'])&&count($res ['rows'])>0){
            foreach($res ['rows'] as $k=>$value){
                $res ['rows'][$k]['status']=$value['status']=='1'?'开启':'暂停';
            }
        }
		return listJson ( 0, '', $res ['total'], $res ['rows'] );
	}
	
	//显示添加页面
	public function addIntegral(){
		$id=input("id");
		
		$act="add";
		if(!empty($id)){
			$pg=new IntegralDb ();
			$one=$pg->Get($id);
            $one['start_date']=date('Y-m-d H:i:s',$one['start_date']);
            $one['end_date']=date('Y-m-d H:i:s',$one['end_date']);
			$this->assign("one",$one);
			$act="edit";
		}
		
		$this->assign("act",$act);
		return $this->fetch("integral/add_integral");
	}
	
	// 添加
	public function add() {
        $title = input ( "title" );
		$branch_no = input ( "branch_no" );
		$status = input ( "status");
		$rate = input ( "rate" );
		$start = input ( "start_date" );
		$end = input ( "end_date" );
		$add = input ( "add" );
        $id = input ( "id" );
		
		if (empty ( $title ) ||empty ( $branch_no ) || empty ( $rate )
				|| empty ( $start ) || empty ( $end ) || empty ( $add )
			) 
		{
			return [
					'code' => false,
					'msg' => lang ( "invalid_variable" ) 
			];
		}
		
		if (! is_numeric ( $rate )) {
			return [ 
					'code' => false,
					'msg' => lang ( "rate_not_num" )
			];
		}
		
		if ($add == "add") {
			$integral = new IntegralDb ();
		} else {
			$IntegralDb = new IntegralDb ();
            $integral = $IntegralDb->Get( $id );
			if (empty ( $integral )) {
				return [ 
						'code' => false,
						'msg' => lang ( "empty_record" ) 
				];
			}
		}

        $integral->title        = $title;
        $integral->branch_no    = $branch_no;
        $integral->rate         = floatval($rate);
        $integral->status       = $status>0?$status-1:0;
        $integral->start_date   = ymktime($start);
        $integral->end_date     = ymktime($end);
        $integral->uid          = $this->_G['uid'];
        $integral->add_date     = $this->_G['time'];
	
		$IntegralDb = new IntegralDb ();
		$result = $IntegralDb->AddOrUpdate ( $integral, $add );
		
		switch ($result) {
			case 1 :
				return [ 
						'code' => true,
						'msg' => lang ( "update_success" ) 
				];
			case - 1 :
				return [ 
						'code' => false,
						'msg' => lang ( "empty_record" ) 
				];
			default :
				return [ 
						'code' => false,
						'msg' => lang ( "update_error" )
				];
		}
	}
	
	//批量删除
	public function batchDelete() {
		 
		$ids = input("ids");
		 
		if (empty($ids)) {
			return ['code'=>false,'msg'=>lang("invalid_variable")];
		}
		 
		$arr=strToArray($ids);
		 
		if(count($arr)<=0){
			return ['code'=>false,'msg'=>lang("invalid_variable")];
		}
		 
		$error=[];
		foreach($arr as $no){
			$res=$this->delete($no);
			if(!$res['code']){
				$error[]=$no.":".$res['msg'];
			}
		}
		 
		if(count($error)>0){
			$error_no=implode(",", $error);
			return [
					'code' => false,
					'msg' => $error_no
			];
		}else{
			return [
					'code' => true,
					'msg' => lang("integral_delete_success")
			];
		}
	}
	
	// 删除单记录
	public function delete($id) {
		
		$IntegralDb=new IntegralDb();
		$result = $IntegralDb->Del($id);
		switch ($result) {
			case 1 :
				return [
						'code' => true,
						'msg' => lang ( "update_success" )
				];
			case - 1 :
				return [
						'code' => false,
						'msg' => lang ( "empty_record" )
				];
			default :
				return [
						'code' => false,
						'msg' => lang ( "update_error" )
				];
		}
	}

}
