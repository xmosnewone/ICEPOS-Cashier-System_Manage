<?php
return array(
		//API服务器访问网址,http/https + 域名/ip地址
		'api_server' => 'http://www.iceposgit.cc',//读取广告图片地址或收款码等场景时候用到该地址
		//和C#端/小程序端一致的accessToken秘钥-访问接口秘钥
		'access_token'=>'c2e3c130b7040fbe18e7f9b319844b42558aeb34',
		// 默认输出类型
		'default_return_type'    => 'html',
		// 默认AJAX 数据返回格式,可选json xml ...
		'default_ajax_return'    => 'xml',
		//是否开启POS端访问接口日志记录,1是记录,0是不记录
		'pos_log'=>1,
        //上传文件根目录
        'uploads_path'=>'./uploads',
);