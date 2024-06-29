<?php
return [
		'/' => 'index',
		'__pattern__' => [
				'name' => '\w+',
		],
		'__domain__' => [
				"*"=> 'index',
				'api' => 'api',
				'www' => 'index',
				'admin' => 'admin',
		],
];
