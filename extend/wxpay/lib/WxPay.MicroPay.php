<?php
class WxPayConfig extends WxPayConfigInterface
{
	public $appid;
	public $merchantid;
	public $paykey;
	public $appsecret;
    public $apiclient_cert;
    public $apiclient_key;
	//=======【基本信息设置】=====================================
	/**
	 * TODO: 修改这里配置为您自己申请的商户信息
	 * 微信公众号信息配置
	 *
	 * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
	 *
	 * MCHID：商户号（必须配置，开户邮件中可查看）
	 *
	 */
	public function __construct($appid,$merchantid,$appsecret='',$paykey='',$apiclient_cert='',$apiclient_key=''){
		$this->appid=$appid;
		$this->merchantid=$merchantid;
		if(!empty($paykey)){
			$this->paykey=$paykey;
		}
		if(!empty($appsecret)){
			$this->appsecret=$appsecret;
		}
        if(!empty($apiclient_cert)){
            $this->apiclient_cert=$apiclient_cert;
        }
        if(!empty($apiclient_key)){
            $this->apiclient_key=$apiclient_key;
        }
	}
	public function GetAppId()
	{
		return $this->appid;
	}
	public function GetMerchantId()
	{
		return $this->merchantid;
	}

	//=======【支付相关配置：支付成功回调地址/签名方式】===================================
	/**
	 * TODO:支付回调url
	 * 签名和验证签名方式， 支持md5和sha256方式
	 **/
	public function GetNotifyUrl()
	{
		return "";
	}
	public function GetSignType()
	{
		return "HMAC-SHA256";
	}

	//=======【curl代理设置】===================================
	/**
	 * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
	 * @var unknown_type
	 */
	public function GetProxy(&$proxyHost, &$proxyPort)
	{
		$proxyHost = "0.0.0.0";
		$proxyPort = 0;
	}


	//=======【上报信息配置】===================================
	/**
	 * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
	 * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
	 * 开启错误上报。
	 * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
	 * @var int
	 */
	public function GetReportLevenl()
	{
		return 1;
	}


	//=======【商户密钥信息-需要业务方继承】===================================
	/*
	 * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）, 请妥善保管， 避免密钥泄露
	 * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
	 *
	 * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置）， 请妥善保管， 避免密钥泄露
	 * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
	 * @var string
	 */
	public function GetKey()
	{
		return $this->paykey;
	}
	public function GetAppSecret()
	{
		return $this->appsecret;
	}


	//=======【证书路径设置-需要业务方继承】=====================================
	/**
	 * TODO：设置商户证书路径
	 * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
	 * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
	 * 注意:
	 * 1.证书文件不能放在web服务器虚拟目录，应放在有访问权限控制的目录中，防止被他人下载；
	 * 2.建议将证书文件名改为复杂且不容易猜测的文件名；
	 * 3.商户服务器要做好病毒和木马防护工作，不被非法侵入者窃取证书文件。
	 * @var path
	 */
	public function GetSSLCertPath(&$sslCertPath, &$sslKeyPath)
	{
		$sslCertPath = $this->apiclient_cert;
		$sslKeyPath = $this->apiclient_key;
	}
}
/**
*
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
 * 
 * 刷卡支付实现类
 * 该类实现了一个刷卡支付的流程，流程如下：
 * 1、提交刷卡支付
 * 2、根据返回结果决定是否需要查询订单，如果查询之后订单还未变则需要返回查询（一般反复查10次）
 * 3、如果反复查询10订单依然不变，则发起撤销订单
 * 4、撤销订单需要循环撤销，一直撤销成功为止（注意循环次数，建议10次）
 * 
 * 该类是微信支付提供的样例程序，商户可根据自己的需求修改，或者使用lib中的api自行开发，为了防止
 * 查询时hold住后台php进程，商户查询和撤销逻辑可在前端调用
 * 
 * @author widy
 *
 */
class MicroPay
{
	public $appid;
	public $merchantid;
	public $paykey;
	public $appsecret;
	
	public function __construct($appid,$merchantid,$appsecret='',$paykey=''){
		$this->appid=$appid;
		$this->merchantid=$merchantid;
		if(!empty($paykey)){
			$this->paykey=$paykey;
		}
		if(!empty($appsecret)){
			$this->appsecret=$appsecret;
		}
	}
	/**
	 * 
	 * 提交刷卡支付，并且确认结果，接口比较慢
	 * @param WxPayMicroPay $microPayInput
	 * @throws WxpayException
	 * @return 返回查询接口的结果
	 */
	public function pay($microPayInput)
	{
		//①、提交被扫支付
		$config = new WxPayConfig($this->appid,$this->merchantid,$this->appsecret,$this->paykey);
		$result = WxPayApi::micropay($config, $microPayInput, 5);
		//如果返回成功
		if(!array_key_exists("return_code", $result)
			|| !array_key_exists("result_code", $result))
		{
			echo "接口调用失败,请确认是否输入是否有误！";
			throw new WxPayException("接口调用失败！");
		}
		
		//取订单号
		$out_trade_no = $microPayInput->GetOut_trade_no();
		
		//②、接口调用成功，明确返回调用失败
		if($result["return_code"] == "SUCCESS" &&
		   $result["result_code"] == "FAIL" && 
		   $result["err_code"] != "USERPAYING" && 
		   $result["err_code"] != "SYSTEMERROR")
		{
			return false;
		}

		//③、确认支付是否成功
		$queryTimes = 10;
		while($queryTimes > 0)
		{
			$succResult = 0;
			$queryResult = $this->query($out_trade_no, $succResult);
			//如果需要等待1s后继续
			if($succResult == 2){
				sleep(2);
				continue;
			} else if($succResult == 1){//查询成功
				return $queryResult;
			} else {//订单交易失败
				break;
			}
		}
		
		//④、次确认失败，则撤销订单
		if(!$this->cancel($out_trade_no))
		{
			throw new WxpayException("撤销单失败！");
		}

		return false;
	}
	
	/**
	 * 
	 * 查询订单情况
	 * @param string $out_trade_no  商户订单号
	 * @param int $succCode         查询订单结果
	 * @return 0 订单不成功，1表示订单成功，2表示继续等待
	 */
	public function query($out_trade_no, &$succCode)
	{
		$queryOrderInput = new WxPayOrderQuery();
		$queryOrderInput->SetOut_trade_no($out_trade_no);
		$config = new WxPayConfig($this->appid,$this->merchantid,$this->appsecret,$this->paykey);
		try{
			$result = WxPayApi::orderQuery($config, $queryOrderInput);
		} catch(\Exception $e) {
			//Log::ERROR(json_encode($e));
		}
		if($result["return_code"] == "SUCCESS" 
			&& $result["result_code"] == "SUCCESS")
		{
			//支付成功
			if($result["trade_state"] == "SUCCESS"){
				$succCode = 1;
			   	return $result;
			}
			//用户支付中
			else if($result["trade_state"] == "USERPAYING"){
				$succCode = 2;
				return false;
			}
		}
		
		//如果返回错误码为“此交易订单号不存在”则直接认定失败
		if($result["err_code"] == "ORDERNOTEXIST")
		{
			$succCode = 0;
		} else{
			//如果是系统错误，则后续继续
			$succCode = 2;
		}
		return false;
	}
	
	/**
	 * 
	 * 撤销订单，如果失败会重复调用10次
	 * @param string $out_trade_no
	 * @param 调用深度 $depth
	 */
	public function cancel($out_trade_no, $depth = 0)
	{
		try {
			if($depth > 10){
				return false;
			}
			
			$clostOrder = new WxPayReverse();
			$clostOrder->SetOut_trade_no($out_trade_no);

			$config = new WxPayConfig($this->appid,$this->merchantid,$this->appsecret,$this->paykey);
			$result = WxPayApi::reverse($config, $clostOrder);

			
			//接口调用失败
			if($result["return_code"] != "SUCCESS"){
				return false;
			}
			
			//如果结果为success且不需要重新调用撤销，则表示撤销成功
			if($result["result_code"] != "SUCCESS" 
				&& $result["recall"] == "N"){
				return true;
			} else if($result["recall"] == "Y") {
				return $this->cancel($out_trade_no, ++$depth);
			}
		} catch(\Exception $e) {
			//Log::ERROR(json_encode($e));
		}
		return false;
	}
}