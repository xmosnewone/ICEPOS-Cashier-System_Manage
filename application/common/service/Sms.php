<?php
namespace app\common\service;
/**
 * 腾讯2.0版本(旧版本)发送短信类
 * 优点不用装证书
 */
use Qcloud\Sms\SmsSingleSender;
use Qcloud\Sms\SmsMultiSender;
use Qcloud\Sms\SmsVoiceVerifyCodeSender;
use Qcloud\Sms\SmsVoicePromptSender;
use Qcloud\Sms\SmsStatusPuller;
use Qcloud\Sms\SmsMobileStatusPuller;
use Qcloud\Sms\VoiceFileUploader;
use Qcloud\Sms\FileVoiceSender;
use Qcloud\Sms\TtsVoiceSender;

class Sms{
	
	/**
	 * 在controller使用范例:
	 * use app\common\service\Sms;
	 * ..
	 * $sms=new Sms();
		$config=[];
		$config['appid']='您的应用APPID';
		$config['appkey']='您的应用秘钥';
		$config['templateid']=模板ID;
		$config['sign']='方程软件';
		$mobile='15813640433';
		$content[]='1234';
		echo $result=$sms->sendSms($config, $mobile, $content);
		echo '<br/>';
		if($result){
			echo '发送成功';
		}else if($result===false){
			echo '发送失败';
		}else if($result==101){
			echo '';
		}
	 * 
	 */
	
	/**
	 * 发送短信的函数
	 * $config 是腾讯api的一些配置，如模板标签等
	 * $mobile 是手机号码
	 * $content是短信内容，短信模板{1}{2}的内容-数组['值1','值2'...]
	 */
	public function sendSms($config,$mobile,$content){
		// 短信应用SDK AppID
		$appid =$config['appid']; // 1400开头-例如:1400192060
		
		// 短信应用SDK AppKey
		$appkey =$config['appkey']; //例如:"7834e1ba99a87ba8696d2a01ffafb4e4";
		
		// 需要发送短信的手机号码
		$isMobile=$this->isMobile(trim($mobile));
		if(!$isMobile){
			return 101;
		}
		$mobile=trim($mobile);
		$phoneNumbers = [$mobile];
		// 短信模板ID，需要在短信应用中申请
		$templateId = $config['templateid'];  // NOTE: 这里的模板ID`7839` 例如:294242
		
		// 签名
		$smsSign = $config['sign']; // NOTE:例如 "方程软件"
		
		// 指定模板ID单发短信
		try {
			$ssender = new SmsSingleSender($appid, $appkey);
			$params = $content;
			$result = $ssender->sendWithParam("86", $phoneNumbers[0], $templateId,
					$params, $smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
			$result = json_decode($result);
			if($result->errmsg === 'OK'){
				return true;
			}else{
				echo($result->errmsg);exit;
			}
		
		} catch(\Exception $e) {
			return false;
		}
	}
	
	private function isMobile($phonenumber){
		if(preg_match("/^1[3456789]{1}\d{9}$/",$phonenumber)){
			return true;
		}else{
			return false;
		}
	}
	
}