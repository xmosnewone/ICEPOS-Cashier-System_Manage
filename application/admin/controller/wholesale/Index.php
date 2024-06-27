<?php
namespace app\admin\controller\wholesale;
use app\admin\controller\Super;
use model\BaseCode;
use model\WholesaleType;
use model\WholesaleClients;
use model\PosBranch;
use model\PosOperator;
use think\Db;
use model\BaseModel;
/**
 * 批发客户档案
 */
class Index extends Super {
	
	public function index() {
		$BaseCode=new BaseCode();
		//查找批发客户区域
		$areaList = $BaseCode->GetBaseCode ( "AA" );
		$this->assign ( "areaList", $areaList );
		return $this->fetch ( "wholesale/index" );
	}
	
	//添加客户档案窗口
	public function edit() {
		$BaseCode=new BaseCode();
		$WholesaleType=new WholesaleType();
		$areaList = $BaseCode->GetBaseCode ( "AA" );
		$entType = $BaseCode->GetBaseCode ( "CT" );
		$balanceWay = $BaseCode->GetBaseCode ( "BW" );
		$typeList = $WholesaleType->GetAllList ();
		
		$WholesaleClients=new WholesaleClients();
		$PosBranch=new PosBranch();
		$PosOperator=new PosOperator();
		$operList=$PosOperator->GetAllModelsForPos();
		$branchs=$PosBranch->GetAllBranchField("id,branch_no,branch_name");
		
		$no = input ( "no" );
		$pm = array ();
		if (! empty ( $no )) {
			$sql = "SELECT p.clients_no,p.own_code,p.company,p.status," 
					. "p.in_date,p.mobile,p.email,p.account,p.pay_password," 
					. "p.type_no,p.linkname,p.phone,p.fax,p.zip_code,p.area_no,p.enterprise_type," 
					. "p.balance_way,p.bank_name,p.bank_id,p.check_out_day,p.check_out_date," 
					. "p.register_type,p.memo,p.saleman,p.credit_status,p.business_statusxf," 
					. "p.license_no,p.tax_no,p.credibility,p.branch_no,c.type_name,p.address,b.branch_name,o.oper_name," 
					. "m.code_name as balancename" . " FROM " . $WholesaleClients->tableName () . " AS p " 
					. " LEFT JOIN " . $WholesaleType->tableName () . " AS c ON p.type_no=c.type_no" 
					. " LEFT JOIN " . $PosBranch->tableName () . " AS b ON b.branch_no=p.branch_no " 
					. " LEFT JOIN " . $BaseCode->tableName () . " AS m ON m.code_id=p.balance_way AND m.type_no='BW'" 
					. " LEFT JOIN " . $PosOperator->tableName () . " AS o ON o.oper_id=p.saleman" 
					. " WHERE p.clients_no=" . $no;
			
			$list = Db::query( $sql );
			$pm = $list[0];
		}
		
		$this->assign ( "one", $pm );
		$this->assign ( "areaList", $areaList );
		$this->assign ( "entType", $entType );
		$this->assign ( "balanceWay", $balanceWay );
		$this->assign ( "typeList", $typeList );
		$this->assign ( "operList", $operList );
		$this->assign ( "branchs", $branchs );
		return $this->fetch ( "wholesale/edit" );
	}
	
	//保存档案
	public function save() {

			$own_code = input ( "own_code" );
			$company = input ( "company" );
			$linkname = input ( "linkname" );
			$mobile = input ( "mobile" );
			$phone = input ( "phone" );
			$fax = input ( "fax" );
			$address = input ( "address" );
			$zip_code = input ( "zip_code" );
			$email = input ( "email" );
			$saleman = input ( "saleman" );
			$sltArea = input ( "area_no" );
			$credit_status = input ( "credit_status" );
			$enterprise_type = input ( "enterprise_type" );
			$business_statusxf = input ( "business_statusxf" );
			$balance_way = input ( "balance_way" );
			$bank_name = input ( "bank_name" );
			$sltLevel = input ( "type_no" );
			$bank_id = input ( "bank_id" );
			$check_out_day = input ( "check_out_day" );
			$license_no = input ( "license_no" );
			$check_out_date = input ( "check_out_date" );
			$register_type = input ( "register_type" );
			$memo = input ( "memo" );
			$taxNo = input ( "tax_no" );
			$credibility = input ( "credibility" );
			$branch_no = input ( "branch_no" );
			$clients_no = input ( "clients_no" );
			$status = input ( "status" )==1?1:0;
			
			$isCommit = true;
			Db::startTrans();
			try {
				
				if(!empty($clients_no)){
					$WholesaleClients=new WholesaleClients();
					$wc=$WholesaleClients->getOne($clients_no);
				}else{
					$wc = new WholesaleClients ();
				}
				
					$wc->own_code = $own_code;
					$wc->company = $company;
					$wc->status = $status;
					$wc->mobile = $mobile;
					$wc->email = $email;
					$wc->type_no = $sltLevel;
					$wc->in_date = date ( 'Y-m-d H:i:s', $this->_G['time']);
					$wc->linkname = $linkname;
					$wc->phone = $phone;
					$wc->fax = $fax;
					$wc->tax_no = $taxNo;
					$wc->zip_code = $zip_code;
					$wc->area_no = $sltArea;
					$wc->enterprise_type = $enterprise_type;
					$wc->balance_way = $balance_way;
					$wc->bank_name = $bank_name;
					$wc->bank_id = $bank_id;
					$wc->check_out_day = $check_out_day;
					$wc->check_out_date = $check_out_date;
					$wc->register_type = $register_type;
					$wc->memo = $memo;
					$wc->saleman = $saleman;
					$wc->credit_status = $credit_status;
					$wc->business_statusxf = $business_statusxf;
					$wc->license_no = $license_no;
					$wc->credibility = $credibility;
					$wc->branch_no = $branch_no;
					$wc->address = $address;
					$wc->clients_no = empty ( $clients_no ) ? null : $clients_no;
				
					$result=$wc->save();
					if (!$result) 
					{
						Db::rollback ();
						return [
								'code' => false,
								'msg' => lang("save_error")
						];
					}
			} catch ( \Exception $e ) {
				return [
						'code' => false,
						'msg' => lang("save_error")
				];
			}
			
			Db::commit ();
			return [
					'code' => true,
					'msg' => lang("save_success")
			];
			
		
	}
	
	//批量删除
	public function batchDelete() {
			
		$clients = input("clientsno");
			
		if (empty($clients)) {
			return ['code'=>false,'msg'=>lang("invalid_variable")];
		}
			
		$arr=strToArray($clients);
			
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
					'msg' => lang("ws_c_del_success")
			];
		}
	}
	
	//删除客户档案
	public function delete($clientsno) {

		if (empty ( $clientsno )) {
			return [
					'code' => false,
					'msg' => lang ( "invalid_variable" )
			];
		}
		
		Db::startTrans();
		try {
			
			$WholesaleClients=new WholesaleClients();
			$delRes=$WholesaleClients->where("clients_no='$clientsno'")->delete();
			if (!$delRes) {
				Db::rollback();
				return [
						'code' => false,
						'msg' => lang("ws_del_main_fail")
				];
			}
		} catch ( \Exception $e ) {
			Db::rollback();
			return [
					'code' => false,
					'msg' => lang("ws_error")
			];
		}
		
		Db::commit();
		return [
				'code' => true,
				'msg' => lang("update_success")
		];
	}
	
	//审核档案
	public function approve() {
		$clientsno = input ( "clientsno" );
		$WholesaleClients=new WholesaleClients();
		$result=$WholesaleClients->save(["status" => 1],['clients_no'=>$clientsno]);
		
		if ($result) {
			return [
					'code' => true,
					'msg' => lang("ws_approve_success")
			];
		} else {
			return [
					'code' => false,
					'msg' =>lang("ws_approve_error")
			];
		}
	}
}
