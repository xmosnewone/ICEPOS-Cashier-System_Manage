<?php
/**
 * 微信
 */
namespace app\common\logic;

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

    //小程序服务端access_token名称
    const MP_ACCESS_TOKEN="mp_access_token";

    /**
     * 主函数
     * @param data_web_config.php系统配置缓存 $config
     */
    public function __construct($config){
        $this->config=$config;

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
        // $url="https://open.weixin.qq.com/connect/qrconnect?appid=wxb4d1823e1bdec5d8&scope=snsapi_login&redirect_uri=https://mall.chinafsl.com/wechat.php?action=dologin&state=https|test1.fslmall.cn";
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

    //2025/02
    //GET 请求
    public function GetRequest($url, $params = [])
    {
        $queryString = http_build_query($params);
        $fullUrl = $url . '?' . $queryString;

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            return $response;
        } else {
            throw new Exception("GET request failed with HTTP code: " . $httpCode);
        }
    }

    //POST 请求
    public function PostRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            return $response;
        } else {
            throw new Exception("POST request failed with HTTP code: " . $httpCode);
        }
    }

    //小程序接口调用-------------------------------------------------------------------------
    //从远程接口获取并缓存access_token
    public function getRemoteMpAccessToken(){
        $now=time();
        $config=$this->config;//web_config 读取系统配置微信小程序appid等配置信息
        $url="https://api.weixin.qq.com/cgi-bin/token";
        $params['appid']=$config['mp_appid'];
        $params['secret']=$config['mp_appsecret'];
        $params['grant_type']="client_credential";
        $result=$this->GetRequest($url,$params);
        $json=json_decode($result,true);
        if(!empty($json["access_token"])){
            $access_token=$json['access_token'];
            $expires_in=$now+($json['expires_in']-1500);//7200内的值
            $cahce=['access_token'=>$access_token,'expires_in'=>$expires_in];
            cache_write(self::MP_ACCESS_TOKEN,self::MP_ACCESS_TOKEN,$cahce);
            return ['access_token'=>$access_token,'expires_in'=>$expires_in];
        }else{
            return false;
        }
    }

    //从本地文件获取access_token
    public function getMpAccessToken()
    {
        $now=time();
        $mp_access_token=@include_once APP_DATA . 'data_'.self::MP_ACCESS_TOKEN.'.php';
        if($mp_access_token&&isset($mp_access_token['access_token'])&&!empty($mp_access_token['access_token'])){
            if($mp_access_token['expires_in']>$now){
                return ['access_token'=>$mp_access_token['access_token']];
            }
        }
        //重新获取并缓存最新的access_token
        return $this->getRemoteMpAccessToken();
    }

    //使用微信小程序用户授权的code获取openid登录
    public function mpLogin($code){
        $config=$this->config;//web_config 读取系统配置微信小程序appid等配置信息
        $url='https://api.weixin.qq.com/sns/jscode2session';
        $params=[];
        $params['appid']=$config['mp_appid'];
        $params['secret']=$config['mp_appsecret'];
        $params['js_code']=$code;//微信小程序登录返回的code
        $params['grant_type']="authorization_code";
        $result=$this->GetRequest($url,$params);
        $json=json_decode($result,true);
        $openid="";
        $unionid="";
        $code=0;
        switch ($json['errcode']) {
            case 0: //正常返回
                $openid=$json['openid'];
                $unionid=$json['unionid'];
                $code=1;
                $msg="success";
                break;
            case 40029://js_code无效
                $code=40029;
                $msg='js_code无效';
                break;
            default:
                $code=-1;
                $msg='OPENID接口获取失败'.$json['errcode'];
                break;
        }

        return ['code'=>$code,'msg'=>$msg,'openid'=>$openid,'unionid'=>$unionid];
    }

    //授权获取手机号码
    public function getPhoneNumber($code)
    {
        $token=$this->getMpAccessToken();
        $url="https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=".$token['access_token'];
        $params=[];
        $params['code']=$code;//微信小程序获得用户授权获取手机号返回的code
        $result=$this->PostRequest($url,json_encode($params));
        $json=json_decode($result,true);
        if($json['errcode']==0&&$json['errmsg']=='ok'&&!empty($json['phone_info'])){
            $purePhone=$json['phone_info']['purePhoneNumber'];//不带区号的号码
            $phoneNumber=$json['phone_info']['phoneNumber'];//带区号的号码
            $countryCode=$json['phone_info']['countryCode'];//国家区号
            return ['purePhone'=>$purePhone,'phoneNumber'=>$phoneNumber,'countryCode'=>$countryCode];
        }else{
            return false;
        }
    }


}