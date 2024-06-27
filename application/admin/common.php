<?php
// admin公共文件
/**
 * 返回json 列表加载的数据形式
 */
function listJson($code,$msg,$count,$data){
	return [
			'code'=>$code,
			'msg'=>$msg,
			'count'=>$count,
			'data'=>$data
			];
}
/**
 *返回树状分类数据 
 */
function treeJson($code,$msg,$data,$title=""){
	$alltype=$title!=''?$title:lang("alltypes");
	$results[]= array (
						'id' => 0,
						'title' => $alltype,
						'last' => false,
						'parentId' => 0
				);
	$results[0]['children']=$data;
	return [
			'status'=>['code'=>$code,'message'=>$msg],
			'data'=>$results
	];
}

/*
 * 循环字符串取首字母
 */
function getfirstchar($zh){
	$ret = "";
	$s1 = @iconv("UTF-8","gb2312", $zh);
	$s2 = @iconv("gb2312","UTF-8", $s1);
	if($s2 == $zh){$zh = $s1;}
	for($i = 0; $i < strlen($zh); $i++){
		$s1 = substr($zh,$i,1);
		$p = ord($s1);
		if($p > 160){
			$s2 = substr($zh,$i++,2);
			$ret .= getZhFirst($s2);
		}else{
			$ret .= $s1;
		}
	}
	return $ret;
}

/*
 * 取汉字首字母
 */

function getZhFirst($str){
	$fchar = ord($str{0});
	if($fchar >= ord("A") and $fchar <= ord("z") )return strtoupper($str{0});
	$s1 = @iconv("UTF-8","gb2312", $str);
	$s2 = @iconv("gb2312","UTF-8", $s1);
	if($s2 == $str){$s = $s1;}
	else{$s = $str;}
	$asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
	if($asc >= -20319 and $asc <= -20284) return "A";
	if($asc >= -20283 and $asc <= -19776) return "B";
	if($asc >= -19775 and $asc <= -19219) return "C";
	if($asc >= -19218 and $asc <= -18711) return "D";
	if($asc >= -18710 and $asc <= -18527) return "E";
	if($asc >= -18526 and $asc <= -18240) return "F";
	if($asc >= -18239 and $asc <= -17923) return "G";
	if($asc >= -17922 and $asc <= -17418) return "H";
	if($asc >= -17417 and $asc <= -16475) return "J";
	if($asc >= -16474 and $asc <= -16213) return "K";
	if($asc >= -16212 and $asc <= -15641) return "L";
	if($asc >= -15640 and $asc <= -15166) return "M";
	if($asc >= -15165 and $asc <= -14923) return "N";
	if($asc >= -14922 and $asc <= -14915) return "O";
	if($asc >= -14914 and $asc <= -14631) return "P";
	if($asc >= -14630 and $asc <= -14150) return "Q";
	if($asc >= -14149 and $asc <= -14091) return "R";
	if($asc >= -14090 and $asc <= -13319) return "S";
	if($asc >= -13318 and $asc <= -12839) return "T";
	if($asc >= -12838 and $asc <= -12557) return "W";
	if($asc >= -12556 and $asc <= -11848) return "X";
	if($asc >= -11847 and $asc <= -11056) return "Y";
	if($asc >= -11055 and $asc <= -10247) return "Z";
	return null;
}

/**
 * js 返回上一页
 */
function jsBack(){
	echo '<script>window.history.go(-1);</script>';
	exit();
}
/**
 * 处理功能中可能存在的符号等参数，返回正常的模块.控制器.操作
 */
function function_url($url){
	
	if($url==''){
		return	'';
	}
	
	$split=["#","?","&"];
	foreach($split as $quot){
		$pos=stripos($url, $quot);
		if($pos>=0){
			$arr=explode($quot, $url);
			$url=$arr[0];
		}
	}
	
	if($url!=''){
		$url=str_replace("/", "", $url);
	}
	
	return strtolower($url);
}
/**
 * 功能写入缓存
 */
function cache_functions(){
	$functions = M( 'function' )
				->where("is_display='1'")
				->field("id,name,icon,parent,url,level,orderby")
				->order("orderby asc,id asc")
				->select ();
	$cache=$cache_url=[];
	foreach ( $functions as $key => $value ) {
		$cache [$value ['id']] = $value;
		if($value['url']!=''){
			$url=function_url(trim($value['url']));
			$cache_url[$url]=$value['id'];
		}
	}
	unset($functions);
	cache_write ( 'function', 'function', $cache );
	cache_write ( 'function_url', 'function_url', $cache_url );
}