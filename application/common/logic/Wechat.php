<?php
/**
 * 微信
 */
namespace app\common\logic;

use think\Db;
class Wechat {
	
	//微信登录配置
	public $config;
	//code
	public $code;
	//accesstoken
	public $token;
	//openid
	public $openid;
	//session前缀
	private $prefix='wx_';
	//指定返回模块
	public $module='';
	
	/**
	 * 主函数
	 * @param data_web_config.php系统配置缓存 $config
	 */
	public function __construct($config){
		$this->config=unserialize($config['wechat_login_config']);
		
		$code=session($this->prefix."code");
		$token=session($this->prefix."access_token");
		$openid=session($this->prefix."openid");
		
		$this->code=$code?$code:'';
		$this->token=$token?$$token:'';
		$this->openid=$openid?$openid:'';
	}
	
	//PC版微信登录
	//跳转到微信服务器并显示二维码
	public function wxqrcode($wx_url=''){
		$ssl=config("is_https");
		$http=$ssl?'https':'http';
		$domain=$_SERVER['HTTP_HOST'].($this->module!=''?"_".$this->module:'');
		$appid = $this->config['app_key'];
		$redirect_uri=$wx_url?$wx_url:config("wx_auth_url");
		$redirect_uri=urlencode($redirect_uri);
		$state="$http|$domain";
		$url="https://open.weixin.qq.com/connect/qrconnect?appid=$appid&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_login&state=$state#wechat_redirect";
	    header ( "Location:" . $url );
	    exit();
	}
	
	//手机版微信登录
	//手机WAP版跳转到授权页面
	//参数:$codeUrl获取code之后回调地址
	public function wapAuth($action='dologin',$wx_url=''){
		$ssl=config("is_https");
		$http=$ssl?'https':'http';
		$domain=$_SERVER['HTTP_HOST'].($this->module!=''?"_".$this->module:'');
		$appid = $this->config['appkey'];
		$redirect_uri=$wx_url?$wx_url:config("wx_wap_auth_url");//手机授权地址
		$redirect_uri=urlencode($redirect_uri);
		$state="$http|$domain|$action";//带上是登录或绑定会员信息
		$url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=".$state."#wechat_redirect";
		header("Location:".$url);
	}
	
	//回调返回code
	public function getCode(){
		$code = ctrim ( input ('code') );
		$state = ctrim ( input ('state') );
		
		if(!$code){
			return ['status'=>0,'msg'=>'参数错误'];
		}
		
		session($this->prefix."code",$code);
		$this->code=$code;
		
		return ['status'=>1,'msg'=>'获取成功','data'=>$code];
	}
	
	/**
	 * 获取微信用户的accesstoken 和openid
	 * @param $code 是授权成功后微信服务返回的code值
	 */
	public function getAccessToken($ispc=true){
		
		if($ispc){
			$appid = $this->config['app_key'];
			$appsecret = $this->config['app_secret'];
		}else{
			$appid = $this->config['appkey'];
			$appsecret = $this->config['appsecret'];
		}

		$code=$this->code;
		
		if($code=='')	return ['status'=>0,'msg'=>'code为空'];
		
		// 跳转获取accesstoken
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
		try{
			
			$result = file_get_contents( $url );
			$json = json_decode($result,true);
			if($json&&$json['access_token']!=''){
				
				$this->token=$json['access_token'];
				$this->openid=$json['openid'];
				
				//设置session
				session($this->prefix."access_token",$this->token);
				session($this->prefix."openid",$this->openid);
				
				return ['status'=>1,'msg'=>'success','data'=>$json];
			}else{
				return ['status'=>0,'msg'=>'获取access_token失败'];
			}
			
		}catch(\Exception $e){
				return ['status'=>0,'msg'=>'获取access_token的接口读取失败'];
		}

	}
	
	/**
	 * 获取微信用户信息-PC+手机可共用
	 * @param $code 是授权成功后微信服务返回的code值
	 */
	public function getWxUser(){
		
		$token = $this->token;
		$openid = $this->openid;
		
		if($token=='')	return ['status'=>0,'msg'=>'token为空'];
		
		if($openid=='')	return ['status'=>0,'msg'=>'openid为空'];
		
		//通过accesstoken和openid获取微信用户信息
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $token . "&openid=" . $openid . "&lang=zh_CN";
		
		try{
			
			$result = file_get_contents($url);
			$json = json_decode($result, true);
			if(is_array($json)){
				return ['status'=>1,'msg'=>'success','data'=>$json];
			}else{
				return ['status'=>0,'msg'=>'获取微信用户信息失败'];
			}
			
		}catch(\Exception $e){
			
			return ['status'=>0,'msg'=>'获取微信用户信息的接口读取失败'];
			
		}
		
	}
	
	

}