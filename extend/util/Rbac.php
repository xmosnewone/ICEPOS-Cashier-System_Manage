<?php

namespace util;

use think\Db;

/**
 * +------------------------------------------------------------------------------
 * 基于角色的数据库方式验证类
 * +------------------------------------------------------------------------------
 */
class Rbac {
	
	// 认证方法
	public function authenticate($map, $model = '') {
		if (empty ( $model ))
			$model = config ( 'USER_AUTH_MODEL' );
			// 使用给定的Map进行认证
		return M ( $model )->where ( $map )->find ();
	}
	
	// 用于检测用户权限的方法,并保存到Session中
	// $authId 即用户组的id
	public function saveAccessList($authId = null) {
		//保存当前用户的访问权限列表
		$perms=$this->getAccessList ( $authId ) ;
		session ( "_ACCESS_LIST", $perms);
		return	$perms;
	}
	
	// 检查当前操作是否需要认证
	public function checkAccess() {
		// 如果项目要求认证，并且当前模块需要认证，则进行权限认证
		if (config ( 'USER_AUTH_ON' )) {
			$_module = array ();
			$_action = array ();
			if ("" != config ( 'REQUIRE_AUTH_MODULE' )) {
				// 需要认证的模块
				$_module ['yes'] = explode ( ',', strtoupper ( config ( 'REQUIRE_AUTH_MODULE' ) ) );
			} else {
				// 无需认证的模块
				$_module ['no'] = explode ( ',', strtoupper ( config ( 'NOT_AUTH_MODULE' ) ) );
			}
			// 检查当前模块是否需要认证
			if ((! empty ( $_module ['no'] ) && ! in_array ( strtoupper ( CONTROLLER_NAME ), $_module ['no'] )) || (! empty ( $_module ['yes'] ) && in_array ( strtoupper ( CONTROLLER_NAME ), $_module ['yes'] ))) {
				if ("" != config ( 'REQUIRE_AUTH_ACTION' )) {
					// 需要认证的操作
					$_action ['yes'] = explode ( ',', strtoupper ( config ( 'REQUIRE_AUTH_ACTION' ) ) );
				} else {
					// 无需认证的操作
					$_action ['no'] = explode ( ',', strtoupper ( config ( 'NOT_AUTH_ACTION' ) ) );
				}
				// 检查当前操作是否需要认证
				if ((! empty ( $_action ['no'] ) && ! in_array ( strtoupper ( ACTION_NAME ), $_action ['no'] )) || (! empty ( $_action ['yes'] ) && in_array ( strtoupper ( ACTION_NAME ), $_action ['yes'] ))) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		
		//不需要验证直接返回false
		return false;
	}
	
	// 权限认证的过滤器方法
	// 这个函数就是关键的判断当前Action 是否需要判断权限
	public function AccessDecision() {
		// 检查是否需要认证--需要验证
		if ($this->checkAccess ()) {
			// 将当前的MODULE_NAME . CONTROLLER_NAME . ACTION_NAME 链接起来md5加密生成一个唯一码，存进session，以便于以后再判断
			$current_node = strtolower(MODULE_NAME . CONTROLLER_NAME . ACTION_NAME);
			$current_node=str_replace(".", "", $current_node);

			//用于缓存当前访问路径是否已正常访问过
			$accessGuid = md5 ( $current_node );
			// 缓存文件中的权限
			$cache_list = @include APP_DATA . '/data_function_url.php';
			// 系统定义超管组id
			$super_gid = config ( "SUPPER_GID" );
			//系统定义是否验证没登记的功能
			$auth_not_list_ca=config("AUTH_NOT_LIST_CA");
			// 当前用户的组id
			$gid = session ( "rid" );
			
			if (empty ($gid)) {
				return false;
			}
			
			// 超级管理员无需认证
			if ($super_gid == $gid) {
				return true;
			}
			
			// 不在权限限制之列且配置为不验证没有设置功能权限
			if(!$auth_not_list_ca&&!isset($cache_list[$current_node])){
				return true;
			}
			
			// 加强验证和即时验证模式 更加安全 后台权限修改可以即时生效(实时验证)
			if (config ( 'USER_AUTH_TYPE' ) == 2) {
				// 通过数据库进行访问检查
				$accessList = $this->getAccessList ($gid);
			} else {
				// 如果是管理员或者当前操作已经认证过，无需再次认证
				if (session ( $accessGuid )) {
					return true;
				}
				// 登录验证模式，比较登录后保存的权限访问列表
				$accessList=session ( '_ACCESS_LIST' );
				if(empty($accessList)){
					$accessList=$this->saveAccessList($gid);
				}
			}
			// 判断当前组别是否已经添加了当前的Aciton的权限，并且当前组别有权限
			$current_action_value = $cache_list [$current_node];
			
			if (! isset ( $current_action_value )||empty($current_action_value) || ! in_array ( $current_action_value, $accessList )) 
			{
				session ( $accessGuid, false );
				return false;
			} else {
				session ( $accessGuid, true );
			}
		}
		
		return true;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 取得当前认证号的所有权限列表
	 * +----------------------------------------------------------
	 * 
	 * @param integer $authId
	 *        	用户ID
	 *        	+----------------------------------------------------------
	 * @access public
	 *         +----------------------------------------------------------
	 */
	public function getAccessList($gid) {
		
		// 查找该组的所有权限的ID
		$res = M ( "sys_operator_role" )->where ( "rid='$gid'" )->find ();
		$perms = array ();
		if ($res ['perm'] != '0') {
			$perms = json_decode ( $res ['perm'], true );
		}
		return $perms;
	}
}