<?php
namespace app\admin\controller;
use app\admin\components\Enumerable\ESession;
use think\Db;
use think\Session;
use think\facade\Request;
use think\Controller;

use util\Image;
use app\common\service\Excel;

class Super extends Controller {
	
	protected $_G = array ();
	protected $moduleid;//当前模块的id
	
	//分页
	public $pageSize;
	//数据库前缀
	public $dbprefix;
	
	public function __construct(){
		header("Cache-control: private");  // history.back返回后输入框值丢失问题
		define('IS_GET',Request::instance()->isGet());
		define('IS_POST',Request::instance()->isPost());
		define('IS_AJAX',Request::instance()->isAjax());
		define('CONTROLLER_NAME',Request::instance()->controller());
		define('ACTION_NAME',Request::instance()->action());
		define('MODULE_NAME',Request::instance()->module());
		
		parent::__construct();
	}
	
	public function initialize() {

		set_time_limit (0);
		
		//权限检测
		$this->authCheck();
		//初始化
		$this->_G ['uid'] = $this->_G ['username']=$this->_G ['rid'] = $this->_G ['groupid']='';
		
		//当前时间
		$mtime = time();
		$Usession=$this->_G['session'];
		$this->_G['uid']=$Usession['uid'];
		$this->_G['rid']=$Usession['rid'];
		$this->_G['groupid']=$Usession['rid'];
		$this->_G['username']=$Usession['loginname'];
		$this->_G ['time'] = $mtime;

		$_GET=input('get.');
		$_POST=input('post.');
		
		//数据库前缀
		$this->dbprefix=config("database.prefix");
		
		//分页数量
		$this->pageSize=config("paginate.pagesize");
		$pagesize=intval(input("pagesize"));
		if(!empty($pagesize)){
			$this->pageSize=$pagesize;
		}
		
		//引入文件缓存
		$this->cache();
		
		if(!IS_AJAX){
			//页面等基础数据初始化
			$this->init();
		}
	}
	
	//导入配置等缓存信息
	public function cache(){
		//需要导入缓存的文件名称数组
		$cachearr = array ('web_config');
		
		foreach ( $cachearr as $value ) {
			$this->$value = @include_once APP_DATA . 'data_' . $value . '.php';
			if(!IS_AJAX){
				$this->assign ( $value, $this->$value );
			}
		}
	}
	
	//权限检测
	public function authCheck(){
		//初始化用户Session
		$this->_G['session']=[];
		$Esession=new ESession();
		$USession=$Esession->getSession();
		
		$uid=$USession['uid'];
		$rid=$USession['rid'];
		if(empty($uid)||empty($rid)){
			$not_login=lang("not_login");
			if(IS_AJAX){
				ajaxReturn(['status'=>-1001,'msg'=>$not_login]);
			}else{
				$this->assign("hideMain",1);
				$this->showmessage("", U('Auth/index'));
			}
		}
	
		// 用户权限检查
		if (config ( 'USER_AUTH_ON' )) {
			$rbac=new \util\Rbac();
			if (! $rbac->AccessDecision ()) {
				if(IS_AJAX){
					ajaxReturn(['status'=>-1002,'msg'=>lang("not_auth")]);
				}else{
					$this->assign("hideMain",1);
					$this->showmessage(lang("not_auth"), U('Auth/index'));
				}
			}
		}
		
		//设置全局用户session
		$this->_G['session']=$USession;
	}
	
	//基础数据-页面渲染等初始化
	public function init(){
		$this->assign ("_G", $this->_G );
		$this->assign("pageshow", config("paginate.pageshow"));
		$this->assign("pagesize", $this->pageSize);
		$this->assign("site_title", $this->web_config['web_name']);
		$this->assign("CONTROLLER_NAME", CONTROLLER_NAME);
		$this->assign("ACTION_NAME", ACTION_NAME);
		$this->assign("MODULE_NAME", MODULE_NAME);
	}
	
	/**
	 * 文件上传
	 * @access string $input 文件名称
	 */
	public function uploadFile($input)
	{
		if (!empty($_FILES[$input])) {
			$uploads=config("uploads_path");
			$file = request()->file($input);
			$info = $file->move($uploads);
			$result=[];
			if($info){
				// 成功上传后 获取上传信息
				$result['status']=1;
				$result['extension']=$info->getExtension();
				//完整上传路径
				$result['path']=$uploads."/".str_replace("\\", "/", $info->getSaveName());
				//上传后的名称
				$result['filename']=$info->getFilename();
			}else{
				// 上传失败获取错误信息
				$result['status']=0;
				$result['msg']=$file->getError();
			}
			return $result;
		}
	}
	
	/**
	 * 图片文件上传并裁剪保存
	 * @access string $input 图片文件名称
	 * @access array $thumb 输出图片参数/缩略图尺寸等二位数组[0=>['w'=>200,'h'=>300],1=>[]...]
	 */
	public function uploadImage($input,$thumb=[])
	{
		$result=$this->uploadFile($input);
		//返回上传图片成功与否数组
		$return=[];
		
		if(!$result['status']){
			return ['status'=>0,'msg'=>'upload error'];
		}
		
		
		$isWater=config("is_water");
		$waterImg=config("water_image");
		$uploads=config("uploads_path");
		
		if(!file_exists($result['path'])){
			return ['status'=>0,'msg'=>'file not exist'];
		}
		
		//先对上传图片加水印
		if($isWater){
			Image::water($result['path'],$waterImg);
		}
		
		$ext=$result['extension'];
		$thumbArray=[];
		foreach ($thumb as $k=>$value){
			$thumbName=str_replace(".".$ext,'',$result['path'])."_".$value['w']."x".$value['h'].".".$ext;
			Image::thumb2($result['path'],$thumbName,'',$value['w'],$value['h']);
			$thumbArray[$k]=$thumbName;
		}
		
		$result['thumbs']=$thumbArray;
		return $result;
	}
	
	/**
	 * 压缩图片文件
	 * @access string $path 图片文件路径
	 */
	public function compressImage($path){
		
		$max_size=config("image_size")*1024;
		$max_width=config("image_max_width");
		$max_height=config("image_max_height");
		list($width, $height, $type, $attr) = getimagesize($path);
		$fileSize=filesize($path);
		
		if($width>$max_width||$height>$max_height||$fileSize>$max_size){
			//压缩图片尺寸,直接裁减，不按比例压缩
			return Image::thumb($path,$path,'',$max_width,$max_height);
		}else{
			return $path;
		}
	}
	
	/*
	 * 前台提示跳转(成功或失败)
	 * @param $msg 信息内容
	 * @param $waittime 等待多少秒后跳转，默认2秒
	 * @param $jumpurl 跳转到哪个页面，默认返回上一步
	 * @param $array 要跳转的地址的数组 地址=>显示名称
	 */
	public function showmessage($msg, $jumpurl, $waittime=3, $array="") {
		$jumpurl = $jumpurl ? $jumpurl : "0";
		if ($msg) {
			$this->assign ( 'message', $msg );
			if (is_array ( $array )) {
				$this->assign ( 'theurl', $array );
			}
			$this->assign ( 'jumpUrl', $jumpurl );
			$this->assign ( 'waitSecond', $waittime );
			
			$this->success($msg,$jumpurl,$array,$waittime);
		} else {
			$this->redirect($jumpurl);
		}
	}
	
	//空操作定义
	Public function _empty() {
		return $this->fetch ( 'Public/404' );
	}

	//使用PHPEXCEL 读取文件插入数据库
	public function use_phpexcel($file,$isUnlink=true) {
		
		$excel=new Excel();
		return $excel->read_excel($file,$isUnlink);
	}
	
	//导出excel
	public function export_excel($doc, $list, $field, $line_title, $un_need = array(), $ex = '2007', $jumpurl = "",$merge_line=array()) {
		if(!$list||count($list)<=0){
			$jumpurl=($jumpurl!=''?$jumpurl:$_SERVER['HTTP_REFERER']);
			$this->showmessage(lang("empty_export"), $jumpurl);
		}
		$excel=new Excel();
		return $excel->export_excel($list, $field, $line_title,$doc,$un_need, $ex,$merge_line);
	}
	
	//导出可合并行数据得的excel
	public function export_merge_excel($doc, $list, $field, $line_title,$merge_line, $un_need = array(), $ex = '2007', $jumpurl = "") {
		if(!$list||count($list)<=0){
			$jumpurl=($jumpurl!=''?$jumpurl:$_SERVER['HTTP_REFERER']);
			$this->showmessage(lang("empty_export"), $jumpurl);
		}
		$excel=new Excel();
		return $excel->export_excel($list, $field, $line_title,$merge_line,$doc,$un_need, $ex);
	}
	
	//导出带图片excel
	public function export_excel_img($doc, $list, $field, $line_title, $un_need = array(), $ex = '2007', $jumpurl = "") {
		if(!$list||count($list)<=0){
			$jumpurl=($jumpurl!=''?$jumpurl:$_SERVER['HTTP_REFERER']);
			$this->showmessage(lang("empty_export"), $jumpurl);
		}
		$excel=new Excel();
		return $excel->export_excel_img($list, $field, $line_title,$doc,$un_need, $ex);
	}
	
	//导出CSV
	public function export_csv($list, $field,$title,$doc){
		if(!$list||count($list)<=0){
			$jumpurl=$_SERVER['HTTP_REFERER'];
			$this->showmessage(lang("empty_export"), $jumpurl);
		}
		$excel=new Excel();
		return $excel->export_csv($list, $field,$title,$doc);
	}
	
	//导出多个CSV压缩成ZIP包
	public function export_csv_zip($list, $field,$title,$doc){
		if(!$list||count($list)<=0){
			$jumpurl=$_SERVER['HTTP_REFERER'];
			$this->showmessage(lang("empty_export"), $jumpurl);
		}
		$excel=new Excel();
		return $excel->export_csv_zip($list, $field,$title,$doc);
	}
	
	private function https_post($url, $data)
	{
		$ch = curl_init();
		$header = array('Accept-Charset: utf-8');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$tmpInfo = curl_exec($ch);
		$errorno = curl_errno($ch);
		if ($errorno) {
			return array('rt' => false, 'errorno' => $errorno,'errormsg'=>'success11');
		} else {
			$js = json_decode($tmpInfo, 1);
			if ($js['errcode'] == '0') {
				return array('rt' => true, 'errorno' => 0,'errormsg'=>'');
			} else {
				return array($js);
			}
		}
	}
	
	
	private function getAccessToken($appid,$appsecret)
	{
		//$appids =$appid;//公众号的appid,需要配置或固定
		//$secrets =$appsecret;//公众号的appsecret 需要配置或固定
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $appsecret . '';
		$res = file_get_contents($url);
		$arr = json_decode($res, true);
		$access_token = $arr['access_token'];
		//
		return $access_token;
	}
	
	/**
	 * $openid是要发送的对象
	 * $title 是模板标题
	 * $url 是详情链接
	 * $key1 关键词1
	 * $key2 关键词2
	 * $key3 关键词3
	 */
	public function send_tplmsg($openid,$title,$url,$key1,$key2,$key3){
		$appkey=$this->web_config['appkey'];
		$appsecret=$this->web_config['appsecret'];
		$templateid=$this->web_config['templateid'];
		$access_token = $this->getAccessToken($appkey,$appsecret);
		
		$data = array(
				'touser' => $openid, // openid是发送消息的基础去公众号获取
				'template_id' =>$templateid, // 模板id，去公众号模板消息选一个适合的消息模板
				'url'=>$url,
				'topcolor' => '#FF0000', // 顶部颜色
				'data' => array(
						'first' => array('value' => $title),
						'keyword1' => array('value' => $key1),
						'keyword2' => array('value' =>$key2),
						'keyword3' => array('value' => $key3)
				)
		);
		$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token . '';
		$result = $this->https_post($url, json_encode($data));
		return $result;
	}

}