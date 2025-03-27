<?php
// 应用公共文件
error_reporting(0);
use phpqrcode\QRcode;

/**
 * 打印数组
 */
 function ppr($array){
 	echo '<pre>';
 	print_r($array);
 	echo '</pre>';
 }
/**
 * 检验会员名的长度4-16位
 */
function isUsername($username) {
	$userlen = StrLenW ( $username );
	return $userlen < 4 || $userlen > 50 ? false : true;
}
/**
 * 检验密码长度
 */
function isPassword($pass) {
	$passLen = strlen( $pass );
	return $passLen < 6 || $passLen > 16 ? false : true;
}
/**
 * 判断是否移动端
 * @return boolean
 */
function isWap(){
	$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	$is_pc = (stripos($agent, 'windows nt')) ? true : false;
	$is_iphone = (stripos($agent, 'iphone')) ? true : false;
	$is_ipad = (stripos($agent, 'ipad')) ? true : false;
	$is_android = (stripos($agent, 'android')) ? true : false;
	$is_winphone = (stripos($agent, 'windows phone')) ? true : false;
	$is_ipod = (stripos($agent, 'iPod')) ? true : false;
	//$host_name=$_SERVER['HTTP_HOST'];
	if(($is_iphone||$is_ipad||$is_android)){
		return true;
	}
	return false;
}
/*
 * 检验EMAIL的准确性
 * @param $email
 */
function isEmail($email) {
	return strlen ( $email ) > 6 && preg_match ( "/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email );
}
/*
 * 检验手机号码的准确性
 * @param $mobile
 */
function isMobile($mobile) {
	return strlen ( $mobile ) == 11 && preg_match ( "/^1[3|4|5|7|8|9](\d){9}$/", $mobile );
}
/*
 * 连接字符
 */
function simplode($ids) {
	return "'" . implode ( "','", $ids ) . "'";
}
//逗号转数组
function strToArray($string){
	if(empty(trim($string))){
		return [];
	}
	$return=[];
	if(strpos($string, ",")!==false){
		$return=explode(",",$string);
	}else{
		$return[]=$string;
	}
	return $return;
}
// 循环创建目录
function mk_dir($dir, $mode = 0777) {
	if (is_dir($dir) || @mkdir($dir, $mode))
		return true;
	if (!mk_dir(dirname($dir), $mode))
		return false;
	return @mkdir($dir, $mode);
}
//检测目录读写权限
function check_dir_iswritable($dir_path)
{
	// 目录路径
	$dir_path = str_replace("\\", "/", $dir_path);
	// 是否可写
	$is_writale = 1;
	// 判断是否是目录
	if (!is_dir($dir_path)) {
		$is_writale = 0;
		return $is_writale;
	} else {
		$fp = fopen("$dir_path/test.txt", 'w');
		if ($fp) {
			fclose($fp);
			unlink("$dir_path/test.txt");
			$writeable = 1;
		} else {
			$writeable = 0;
		}
	}
	return $is_writale;
}
//数组转换成字串
function arrayeval($array, $level = 0) {
	$space = '';
	for($i = 0; $i <= $level; $i ++) {
		$space .= "\t";
	}
	$evaluate = "Array\n$space(\n";
	$comma = $space;
	foreach ( $array as $key => $val ) {
		$key = is_string ( $key ) ? '\'' . addcslashes ( $key, '\'\\' ) . '\'' : $key;
		$val = ! is_array ( $val ) && (! preg_match ( "/^\-?\d+$/", $val ) || strlen ( $val ) > 12 || substr ( $val, 0, 1 ) == '0') ? '\'' . addcslashes ( $val, '\'\\' ) . '\'' : $val;
		if (is_array ( $val )) {
			$evaluate .= "$comma$key => " . arrayeval ( $val, $level + 1 );
		} else {
			$evaluate .= "$comma$key => $val";
		}
		$comma = ",\n$space";
	}
	$evaluate .= "\n$space)";
	return $evaluate;
}

/**
 * 删除图片文件
 *
 * @param 图片路径 $img_path
 */
function removeImageFile($img_path)
{
    // 检查图片文件是否存在
    if (file_exists($img_path)) {
        return unlink($img_path);
    } else {
        return false;
    }
}
//将缓存写进数据的方法
function cache_write($name, $var, $values) {
	$cachefile = APP_DATA . '/data_' . $name . '.php';
	$char='$';
	$char1='=';
	$cachetext = "<?php\r\n" . "if(!defined('APP_PATH')) exit('Access Denied');\r\n"
                    . 'return '.$char
                    . $var . $char1 . arrayeval ( $values ) . "\r\n?>";
	if (! swritefile ( $cachefile, $cachetext )) {
		exit ( "File: $cachefile write error." );
	}
}
//写入文件
function swritefile($filename, $writetext, $openmod = 'w') {
	if (@$fp = fopen ( $filename, $openmod )) {
		flock ( $fp, 2 );
		fwrite ( $fp, $writetext );
		fclose ( $fp );
		return true;
	}
	return false;
}
//获取字符串的长度
function StrLenW($str) {
	$count = 0;
	$len = strlen ( $str );
	for($i = 0; $i < $len; $i ++, $count ++)
		if (ord ( $str [$i] ) >= 128)
			$i ++;
		return $count;
}
/*
 * 去除字符串的所有空格,跨站脚本过滤，Html字符转义
 *
 */
function ctrim($str) {
	return trim(remove_xss($str));
}
/**
 * 去除字符串中间空格
 */
function trimSpace($str){
	return preg_replace ( '/\s*?/i', '', $str );
}
/*
 * (数组)去除字符串的所有空格,跨站脚本过滤，Html字符转义
 *
 */
function array_ctrim($array) {
	foreach($array as $k=>$v){
     $array[$k]=ctrim($v);
    }
    return $array;
}
/*
 * 跨站脚本安全过滤
 */
function remove_xss($val) {
	// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
	// this prevents some character re-spacing such as <java\0script>
	// note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
	$val = preg_replace('/([\x0e-\x19])/', '', $val);

	// straight replacements, the user should never need these since they're normal characters
	// this prevents like <IMG SRC=@avascript:alert('XSS')>
	$search = 'abcdefghijklmnopqrstuvwxyz';
	$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$search .= '1234567890!@#$%^&*()';
	$search .= '~`";:?+/={}[]-_|\'\\';
	for ($i = 0; $i < strlen($search); $i++) {
		// ;? matches the ;, which is optional
		// 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

		// @ @ search for the hex values
		$val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
		// @ @ 0{0,7} matches '0' zero to seven times
		$val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
	}

	// now the only remaining whitespace attacks are \t, \n, and \r
	$ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'base');
	$ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint',
			'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged',
			'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 
			'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave',
			'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 
			'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	$ra = array_merge($ra1, $ra2);

	$found = true; // keep replacing as long as the previous round replaced something
	while ($found == true) {
		$val_before = $val;
		for ($i = 0; $i < sizeof($ra); $i++) {
			$pattern = '/';
			for ($j = 0; $j < strlen($ra[$i]); $j++) {
				if ($j > 0) {
					$pattern .= '(';
					$pattern .= '(&#[xX]0{0,8}([9ab]);)';
					$pattern .= '|';
					$pattern .= '|(&#0{0,8}([9|10|13]);)';
					$pattern .= ')*';
				}
				$pattern .= $ra[$i][$j];
			}
			$pattern .= '/i';
			$replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
			$val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
			if ($val_before == $val) {
				// no replacements were made, so exit the loop
				$found = false;
			}
		}
	}
	return $val;
}
//UTF8中文字符串截取
function cut_str($string, $sublen, $start = 0, $code = 'UTF-8') {
	if ($code == 'UTF-8') {
		$pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
		preg_match_all ( $pa, $string, $t_string );
		if (count ( $t_string [0] ) - $start > $sublen)
			return join ( '', array_slice ( $t_string [0], $start, $sublen ) );
		return join ( '', array_slice ( $t_string [0], $start, $sublen ) );
	} else {
		$start = $start * 2;
		$sublen = $sublen * 2;
		$strlen = strlen ( $string );
		$tmpstr = '';
		for($i = 0; $i < $strlen; $i ++) {
			if ($i >= $start && $i < ($start + $sublen)) {
				if (ord ( substr ( $string, $i, 1 ) ) > 129) {
					$tmpstr .= substr ( $string, $i, 2 );
				} else {
					$tmpstr .= substr ( $string, $i, 1 );
				}
			}
			if (ord ( substr ( $string, $i, 1 ) ) > 129)
				$i ++;
		}
		if (strlen ( $tmpstr ) < $strlen)
			$tmpstr .= "...";
		return $tmpstr;
	}
}
/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @return mixed
 */
function get_client_ip($type = 0) {
	$type       =  $type ? 1 : 0;
	static $ip  =   NULL;
	if ($ip !== NULL) return $ip[$type];
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$pos    =   array_search('unknown',$arr);
		if(false !== $pos) unset($arr[$pos]);
		$ip     =   trim($arr[0]);
	}elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ip     =   $_SERVER['HTTP_CLIENT_IP'];
	}elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ip     =   $_SERVER['REMOTE_ADDR'];
	}
	// IP地址合法验证
	$long = sprintf("%u",ip2long($ip));
	$ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
	return $ip[$type];
}
/**
 * 服务器端IP
 */
function serverIP(){
	return gethostbyname($_SERVER["SERVER_NAME"]);
}
/**
 * Ajax方式返回数据到客户端
 * @param mixed $data 要返回的数据
 * @param String $type AJAX返回数据格式
 * @param int $json_option 传递给json_encode的option参数
 * @return void
 */
function ajaxReturn($data,$type='',$json_option=0) {
	if(empty($type)) $type  =   config("default_ajax_return");
	switch (strtoupper($type)){
		case 'JSON' :
			// 返回JSON数据格式到客户端 包含状态信息
			header('Content-Type:application/json; charset=utf-8');
			exit(json_encode($data,$json_option));
		case 'XML'  :
			// 返回xml格式数据
			header('Content-Type:text/xml; charset=utf-8');
			exit(xml_encode($data));
		case 'JSONP':
			// 返回JSON数据格式到客户端 包含状态信息
			header('Content-Type:application/json; charset=utf-8');
			$handler  =   isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
			exit($handler.'('.json_encode($data,$json_option).');');
		case 'EVAL' :
			// 返回可执行的js脚本
			header('Content-Type:text/html; charset=utf-8');
			exit($data);
	}
}
//获取某段时间内的开始和结束时间戳
//$when 是指某个时间段，1是今天，2是本周，3是本月，4是三月内，5半年内，6是今年
//7昨天，8上个星期，9上个月，10去年,11是本季度
function timezone_get($when=1){

	$now = time();
	switch ($when){
		case 1:
			//今天
			$beginTime=mktime(0,0,0,date('m'),date('d'),date('Y'));
			$endTime=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
			break;
		case 2:
			//本周
			$time = '1' == date('w') ? strtotime('Monday', $now) : strtotime('last Monday', $now);
			$beginTime =  $time;
			$endTime =  strtotime('Sunday', $now)+24*60*60-1;
			break;
		case 3:
			//本月
			$beginTime =  mktime(0, 0, 0, date('m', $now), '1', date('Y', $now));
			$endTime = mktime(23, 59, 59, date('m', $now), date('t', $now), date('Y', $now));
			break;
		case 4:
			//三个月内
			$time = strtotime('-2 month', $now);
			$beginTime =mktime(0, 0,0, date('m', $time), 1, date('Y', $time));
			$endTime = mktime(23, 59, 59, date('m', $now), date('t', $now), date('Y', $now));
			break;
		case 5:
			//半年内
			$time = strtotime('-5 month', $now);
			$beginTime = mktime(0, 0,0, date('m', $time), 1, date('Y', $time));
			$endTime = mktime(23, 59, 59, date('m', $now), date('t', $now), date('Y', $now));
			break;
		case 6:
			//今年
			$beginTime = mktime(0, 0,0, 1, 1, date('Y', $now));
			$endTime = mktime(23, 59, 59, 12, 31, date('Y', $now));
			break;
		case 7:
			//昨天
			$beginTime= strtotime(date('Y-m-d',strtotime('-1 day')));
			$endTime= strtotime(date('Y-m-d'))-1;
			break;
		case 8:
			//上个星期
			$beginTime= strtotime(date('Y-m-d',strtotime('-2 week Monday')));
			$endTime=strtotime(date('Y-m-d',strtotime('-1 week Sunday +1 day')))-1;
			break;
		case 9:
			//上个月
			$beginTime= strtotime(date('Y-m-01',strtotime('-1 month')));
			$endTime= strtotime(date('Y-m-01'))-1;
			break;
		case 10:
			//去年
			$beginTime= strtotime(date('Y-01-01',strtotime('-1 year')));
			$endTime= strtotime(date('Y-12-31',strtotime('-1 year')))+24*60*60-1;
			break;
		case 11:
			//本季度
			$quarter = empty($param) ? ceil((date('n'))/3) : $param;
			$beginTime = mktime(0, 0, 0,$quarter*3-2,1,date('Y'));
			$endTime= mktime(0, 0, 0,$quarter*3+1,1,date('Y'))-1;
			break;
	}

	return array('begin'=>$beginTime,'end'=>$endTime);

}
/*
 * (Y-m-d H:i:s)时间转为时间戳
 *
 */
function ymktime($time) {
	$time = explode ( ' ', $time );
	$time_b = explode ( '-', $time [0] ); //年月日

	if ($time [1]) {
		$time_h = explode ( ':', $time [1] ); //时分秒
	}
	return mktime ( $time_h [0], $time_h [1], $time_h [2], $time_b [1], $time_b [2], $time_b [0] );
}
/**
 * 过滤文本空值
 * 以防数据库操作IN报错
 * @param $str 文本
 */
function textExplode($str){
	$arr=array();
	$arr=explode(',',$str);
	foreach ($arr as $k => $v){
		if(!empty($k)){
			unset($arr[$k]);
		}
	}
	$str=implode(',',$arr);
	return $str;
}
/**
 * 加密&解密
 * @param $string 要加密或解密的字符串
 * @param $operation DECODE为解密,ENCODE为加密
 * @param $key 密钥
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5 ( $key != '' ? $key : C ( 'UC_KEY' ) );
	$keya = md5 ( substr ( $key, 0, 16 ) );
	$keyb = md5 ( substr ( $key, 16, 16 ) );
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr ( $string, 0, $ckey_length ) : substr ( md5 ( microtime () ), - $ckey_length )) : '';

	$cryptkey = $keya . md5 ( $keya . $keyc );
	$key_length = strlen ( $cryptkey );

	$string = $operation == 'DECODE' ? base64_decode ( substr ( $string, $ckey_length ) ) : sprintf ( '%010d', $expiry ? $expiry + time () : 0 ) . substr ( md5 ( $string . $keyb ), 0, 16 ) . $string;
	$string_length = strlen ( $string );

	$result = '';
	$box = range ( 0, 255 );

	$rndkey = array ();
	for($i = 0; $i <= 255; $i ++) {
		$rndkey [$i] = ord ( $cryptkey [$i % $key_length] );
	}

	for($j = $i = 0; $i < 256; $i ++) {
		$j = ($j + $box [$i] + $rndkey [$i]) % 256;
		$tmp = $box [$i];
		$box [$i] = $box [$j];
		$box [$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i ++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box [$a]) % 256;
		$tmp = $box [$a];
		$box [$a] = $box [$j];
		$box [$j] = $tmp;
		$result .= chr ( ord ( $string [$i] ) ^ ($box [($box [$a] + $box [$j]) % 256]) );
	}

	if ($operation == 'DECODE') {
		if ((substr ( $result, 0, 10 ) == 0 || substr ( $result, 0, 10 ) - time () > 0) && substr ( $result, 10, 16 ) == substr ( md5 ( substr ( $result, 26 ) . $keyb ), 0, 16 )) {
			return substr ( $result, 26 );
		} else {
			return '';
		}
	} else {
		return $keyc . str_replace ( '=', '', base64_encode ( $result ) );
	}
}
/**
 * 获取QRcode
 * @param QRcode内容 $url
 * @param 保存路径 $path
 * @param 图片名称 $qrcode_name
 * @param 图片大小 $size
 */
function getQRcode($url, $path, $qrcode_name,$size=2)
{
	$qrcode = new QRcode();
	$level = 'L'; //容错级别
	ob_start();
	if($path!=""){
		if (!is_dir($path)) {
			$mode = intval('0777', 8);
			mkdir($path, $mode, true);
			chmod($path, $mode);
		}
		$path = $path . '/' . $qrcode_name . '.png';
		if (file_exists($path)) {
			unlink($path);
		}
		$qrcode->png($url,$path,$level,$size,2);
		ob_end_clean();
		return '/'.$path;
	}else{
		$qrcode->png($url,false,$level,$size,2);
		$data=ob_get_contents();
		ob_end_clean();
		return "data:image/jpeg;base64,".base64_encode($data);
	}
}
//返回按数组某个字段做键的数组-适用二维数组
function key_array($data,$keyName){
	$return=array();
	foreach($data as $value){
		$return[$value[$keyName]]=$value;
	}
	return $return;
}
/**
 * 图片路径拼装(用于完善用于外链的图片)
 *
 */
function imgPath($img_path)
{
	$path = "";
	if (!empty($img_path)) {
		if (stristr($img_path, "http://") === false && stristr($img_path, "https://") === false) {
			$path = $img_path;
		} else {
			$path = $img_path;
		}
	}
	return $path;
}
/**
 * 时间戳转时间
 */
function timeStamp2Time($time_stamp)
{
	if ($time_stamp > 0) {
		$time = date('Y-m-d H:i:s', $time_stamp);
	} else {
		$time = "";
	}
	return $time;
}
/**
 * 时间转时间戳
 */
function time2TimeStamp($time)
{
	$time_stamp = strtotime($time);
	return $time_stamp;
}
function arrayTwoFilter($data) {
    if (is_array($data)) {
        $data = array_filter($data);
        foreach ($data as $k => $v) {
            if (is_array($data[$k])) {
                $data[$k] = array_filter($v);
            }
        }
    }
    return $data;
}
/**
 * 字符串长度
 */
function getStrlen($str) {
	return mb_strlen($str, "utf8");
}
/**
 * 写入日志文件
 * $msg 是日志内容，$classname 是日志发起类对象
 */
function write_log($msg,$classname){
	$path=LOG_PATH;
	if(!file_exists($path)){
		mk_dir($path);
	}
	$logFile=$path.date("Ymd").".log";
	swritefile($logFile,$msg."||发起对象：".$classname."\r\n");
}
function formatMoneyDisplay($amount) {
	return sprintf("%.2f", $amount);
}
/**
 * 返回可以记录个人消费金额的支付方式
 */
function consume_payment(){
    //参考ice_bd_payment_info表
    return ['RMB','BCD','CHA','CHQ','CRD','HF','PTZ','WXQR','ZFBQR','WECHAT','ZFB'];
}