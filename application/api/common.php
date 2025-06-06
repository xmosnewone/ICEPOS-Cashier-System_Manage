<?php
/**
 * api公共函数
 */
 
/**
 * 判断当日日志是否已创建
 * @param $posid	pos机id
 */
function pos_log($posid,$content=''){
	if(empty($posid)){
		return;
	}
	
	$path=LOG_PATH;
	if(!file_exists($path)){
		mk_dir($path);
	}
	$now=date("Y-m-d H:i:s",time());
	$date=date("Ymd",time());
	$logFile=$path."POS_".$posid."_".$date.".log";
	$content="\r\n".$now."\r\n".$content."\r\n";
	if(!file_exists($logFile)){
		swritefile($logFile,$content);
		return false;
	}else{
		swritefile($logFile,$now.$content,"a");
	}
	return true;
}