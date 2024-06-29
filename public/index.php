<?php
// [ 应用入口文件 ]
namespace think;
define('APP_NAME','ICE开源收银系统');
define('APP_PATH','../application/');
define('APP_DATA','../cache/');
define('EXTEND_PATH', '../extension/');
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(realpath(dirname($_SERVER['SCRIPT_FILENAME']))).DS);
define('LOG_PATH','../log/');
/**
 * 加密字符串
 */
define("ENCRYPT_FOR_PASSWORD", "avooa!@#$49921");
define("ENCRYPT_FOR_CODE", "avooa%^&*()+");
/**
 * 时间定义
*/
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
/**
 * 验证码有效期
*/
define("EFFECT_TIME", "1");
/**
 * 百度地图密钥
 */
define('BAIDU_AKEY', 'igzwHKizrtVBrvneXxE7RqUn');

// 加载基础文件
require __DIR__ . '/../core/base.php';

// 支持事先使用静态方法设置Request对象和Config对象

// 执行应用并响应
Container::get('app')->run()->send();
