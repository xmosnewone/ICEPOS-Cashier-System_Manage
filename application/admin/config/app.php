<?php
return array(
		//跳转提示页面
		'dispatch_success_tmpl' => 'public/jumpaction',
		//上传文件根目录
		'uploads_path'			=>'./uploads',
		//开启图片上传水印
		'is_water'				=>0,
		//水印文件路径
		'water_image'			=>'./static/images/admin/water.png',
		//图片限制大小,单位是KB
		'image_size'			=>20480,
		//超出图片大小之后，限制图片宽度
		'image_max_width'		=>1920,
		//超出图片大小之后，限制图片高度
		'image_max_height'		=>1080,
		// 异常页面的模板文件
		//'exception_tmpl'         => APP_PATH .'admin/view/public/404.html',
		// 视图输出字符串内容替换
		/**
		 * 角色登录权限验证配置
		 */
		/*RBAC 用户权限验证设置*/
		'USER_AUTH_ON'              =>  true,
		'AUTH_NOT_LIST_CA'          =>  false,	//验证没有登记的控制器或页面，false不验证，true验证（系统管理->用户管理->功能管理 并且在角色设置新增的功能）
		'USER_AUTH_TYPE'			=>  2,		// 默认认证类型 1 登录认证 2 实时认证
		'USER_AUTH_KEY'             =>  'authId',	// 用户认证SESSION标记
		'ADMIN_AUTH_KEY'			=>  'administrator',
		'USER_AUTH_MODEL'           =>  'sys_manager',	// 默认验证数据表模型
		'AUTH_PWD_ENCODER'          =>  'md5',	// 用户认证密码加密方式
		'USER_AUTH_GATEWAY'         =>  'Auth/index',// 默认认证网关
		'NOT_AUTH_CONTROLLER'       =>  'Auth',	// 默认无需认证模块
		'REQUIRE_AUTH_CONTROLLER'  	=>  '',		// 默认需要认证模块
		'SUPPER_GID'				=>	'66688',//超级管理员组RID(登录后该组权限无限大),不想被猜解请修改sys_operator_role超级管理员rid
		/*RBAC 用户权限验证设置*/
);