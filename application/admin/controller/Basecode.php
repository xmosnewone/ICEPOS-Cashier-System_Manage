<?php
namespace app\admin\controller;
use app\admin\components\Enumerable\EOperStatus;
use model\BaseCodeType;
use model\BaseModel;
use model\BdBaseCodeBreakpoint;
use model\BdBasecodeTypeBreakpoint;
use model\BaseCode as BaseCodeModel;
/**
 * 基础代码
 */
class Basecode extends Super {
	
	// 分类列表
	public function type() {
		$data = input ( 'data' );
		$baseCodeType = new BaseCodeType ();
		if ($data == 'json') {
			
			$result = $baseCodeType->getall ()->toArray ();
			$results = array ();
			foreach ( $result as $k => $v ) {
				$results [$k] = $v;
			}
			return listJson ( 0, '', count ( $results ), $results );
		} elseif ($data == 'tree_json') {
			$result = $baseCodeType->getall ()->toArray ();
			$results = array ();
			foreach ( $result as $k => $v ) {
				$results [] = array (
						'id' => $v ['type_no'],
						'title' => $v ['type_name'],
						'last' => true,
						'parentId' => 0,
						'children' => [ ] 
				);
			}
			
			$code = 200;
			$message = '';
			return treeJson ( $code, $message, $results );
		} else {
			return $this->fetch ( 'basecode/type' );
		}
	}
	
	// 编辑分类
	public function editType() {
		$baseCodeType = new BaseCodeType ();
		
		if (! IS_POST) {
			// 标记添加或修改1是添加
			$isAdd = 0;
			if (empty ( $_GET ['type_no'] )) {
				$isAdd = 1;
			} else {
				$type_no = ctrim ( $_GET ['type_no'] );
				$one = $baseCodeType->getOne ( [ 
						'type_no' => $_GET ['type_no'] 
				] );
				$this->assign ( "one", $one );
			}
			$this->assign ( "isAdd", $isAdd );
			return $this->fetch ( 'basecode/edittype' );
		}
		
		if (StrLenW ( input ( "type_no" ) ) > 2) {
			$return ['code'] = false;
			$return ['msg'] = lang ( "wrongTypeNo" );
			return $return;
		}
		$data = [ ];
		$data ['type_no'] = input ( "type_no" );
		$data ['type_name'] = input ( "type_name" );
		$ok = $baseCodeType->save ( $data );
		
		// 添加新增记录同步到POS终端
		$bdBreakPoint = new BdBasecodeTypeBreakpoint ();
		$bdBreakPoint->rtype = EOperStatus::ADD;
		$bdBreakPoint->type_no = $data ['type_no'];
		$bdBreakPoint->updatetime = date ( DATETIME_FORMAT );
		$bdBreakPoint->save ();
		
		if ($ok) {
			$return ['code'] = true;
			$return ['msg'] = lang ( "update_success" );
		} else {
			$return ['code'] = false;
			$return ['msg'] = lang ( "update_error" );
		}
		
		return $return;
	}
	
	// 删除分类
	public function typeDel() {
		$type_no = input ( 'type_no' );
		
		$model = new BaseCodeType ();
		$ok = $model->where ( "type_no='$type_no'" )->delete ();
		if ($ok) {
			// 更新同步到C#等客户端的记录
			$bdBreakPoint = new BdBasecodeTypeBreakpoint ();
			$bdBreakPoint->rtype = EOperStatus::DELETE;
			$bdBreakPoint->type_no = $type_no;
			$bdBreakPoint->updatetime = date ( DATETIME_FORMAT );
			$bdBreakPoint->save ();
			
			$return ['code'] = true;
			$return ['msg'] = lang ( "update_success" );
		} else {
			$return ['code'] = false;
			$return ['msg'] = lang ( "update_error" );
		}
		
		return $return;
	}
	
	// 基础代码
	public function codelist() {
		$data = input ( 'data' );
		$model = new BaseCodeModel ();
		if ($data == 'json') {
			
			$page = input ( 'page' );
			$limit = input ( 'limit' );
			
			$where = [ ];
			$type = input ( 'type_no' );
			if ($type != '') {
				$where ['type_no'] = $type;
			}
			$total = $model->where ( $where )->count ();
			$start = ($page - 1) * $limit;
			$list = $model->where ( $where )->limit ( $start, $limit )->select ()->toArray ();
			return listJson ( 0, '', $total, $list );
		} else {
			$type = input ( 'type' );
			if ($type) {
				$this->assign ( 'type', $type );
			}
			return $this->fetch ( 'basecode/codelist' );
		}
	}
	
	// 添加基础代码
	public function add() {
		$msgError = '';
		$type = input ( 'type' );
		$typeModel = new BaseCodeType ();
		
		if (IS_POST) {
			$model = new BaseCodeModel ();
			
			$typeData = $typeModel->getOne ( [ 
					'type_no' => $type 
			] );
			
			$code_id = input ( 'code_id' );
			$code_name = input ( 'code_name' );
			$code_name_english = input ( 'english_name' );
			$memo = input ( 'memo' );
			
			if ($code_id == '') {
				$msgError = lang ( "basecode_empty" );
			} elseif ($code_name == '') {
				$msgError = lang ( "basecode_name_empty" );
			} elseif ($model->ishave ( $type, $code_id ) > 0) {
				$msgError = lang ( "basecode_exist" );
			}
			
			if ($msgError == '') {
				$data = array (
						"type_no" => $type,
						"code_id" => $code_id,
						"code_name" => $code_name,
						"english_name" => $code_name_english,
						"memo" => $memo,
						"code_type" => $typeData ['type_name'] 
				);
				
				if ($model->save ( $data )) {
					$bdBreakPoint = new BdBaseCodeBreakpoint ();
					$bdBreakPoint->rtype = EOperStatus::ADD;
					$bdBreakPoint->type_no = $type;
					$bdBreakPoint->code_id = $code_id;
					$bdBreakPoint->updatetime = date ( DATETIME_FORMAT );
					$bdBreakPoint->save ();
					
					$return ['code'] = true;
					$return ['msg'] = lang ( "update_success" );
				} else {
					$return ['code'] = false;
					$return ['msg'] = lang ( "basecode_name_empty" );
				}
			} else {
				$return ['code'] = false;
				$return ['msg'] = $msgError;
			}
			
			return $return;
		} else {
			
			$this->assign ( 'type', $type );
			return $this->fetch ( 'basecode/add' );
		}
	}
	
	// 删除基础代码
	public function del() {
		$type_no = input ( 'type_no' );
		$code_id = input ( 'code_id' );
		if ($code_id != '' && $type_no != '') {
			$model = new BaseCodeModel ();
			if ($model->del ( $type_no, $code_id )) {
				$bdBreakPoint = new BdBaseCodeBreakpoint ();
				$bdBreakPoint->rtype = EOperStatus::DELETE;
				$bdBreakPoint->type_no = $type_no;
				$bdBreakPoint->code_id = $code_id;
				$bdBreakPoint->updatetime = date ( DATETIME_FORMAT );
				$bdBreakPoint->save ();
				
				$return ['code'] = true;
				$return ['msg'] = lang ( "update_success" );
			} else {
				$return ['code'] = false;
				$return ['msg'] = lang ( "update_error" );
			}
			
			return $return;
		}
	}
	
	// 批量删除基础代码
	public function batchDel() {
		$type = input ( 'type_no' );
		$code = input ( 'code_id' );
		if ($code != '' && $type != '') {
			
			$type_arr = explode ( ",", $type );
			$code_arr = explode ( ",", $code );
			$model = new BaseCodeModel ();
			if (count ( $type_arr ) != count ( $code_arr )) {
				$return ['code'] = false;
				$return ['msg'] = lang ( "wrong_data" );
			}
			
			foreach ( $type_arr as $k => $type_no ) {
				$code_id = $code_arr [$k];
				if ($model->del ( $type_no, $code_id )) {
					$bdBreakPoint = new BdBaseCodeBreakpoint ();
					$bdBreakPoint->rtype = EOperStatus::DELETE;
					$bdBreakPoint->type_no = $type_no;
					$bdBreakPoint->code_id = $code_id;
					$bdBreakPoint->updatetime = date ( DATETIME_FORMAT );
					$bdBreakPoint->save ();
				}
			}
			
			$return ['code'] = true;
			$return ['msg'] = lang ( "update_success" );
			
			return $return;
		}
	}
	
	// 基础代码-品牌列表
	public function pplist() {
		$data = input ( 'data' );
		
		$type = input ( 'type' );
		if ($type) {
			$this->assign ( 'type', $type );
		}
		return $this->fetch ( 'basecode/pplist' );
	}
}
