<?php
namespace app\common\service;
/**
 * 腾讯2.0版本(旧版本)发送短信类
 * 优点不用装证书
 */

use model\Sms as SmsModel;

class Sms{

    /**
     * 在controller使用范例:
     * use app\common\service\Sms;
     * ..
     * $sms=new Sms();
    $config=[];
    $config['appid']='1400192050';
    $config['appkey']='7834e1ba99b87ba8396d2a01ffafb4e4';
    $config['templateid']=294242;
    $config['sign']='微元科技';
    $mobile='15813640431';
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
    public function sendSms($mobile,$content,$config){

        // 短信应用SDK AppID
        $appid =$config['sms_appid']; // 1400开头-例如:1400192050

        // 短信应用SDK AppKey
        $appkey =$config['sms_secretkey']; //例如:"7834e1ba99b87ba8396d2a01ffafb4e4";
        $path = "../extend/Qcloud/Sms/";
        require $path . 'SmsSingleSender.php';

        // 需要发送短信的手机号码
        $isMobile=$this->isMobile(trim($mobile));
        if(!$isMobile){
            return 101;
        }

        $mobile=trim($mobile);
        $phoneNumbers = [$mobile];
        // 短信模板ID，需要在短信应用中申请
        $templateId = $config['sms_tempid'];  // NOTE: 这里的模板ID`7839` 例如:294242

        // 签名
        $smsSign = $config['sms_sign']; // NOTE:例如 "xx科技公司"

        // 指定模板ID单发短信
        try {
            $ssender = new \SmsSingleSender($appid, $appkey);
            $params = $content;
            $result = $ssender->sendWithParam("86", $phoneNumbers[0], $templateId,
                $params, $smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            $result = json_decode($result);
            if($result->errmsg === 'OK'){
                //记录发送短信记录
                $this->smsRecord($mobile,$content);
                return true;
            }else{
                return false;
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

    //记录发送记录
    private function smsRecord($mobile,$code){
        $now=time();
        $Sms=new SmsModel();
        $Sms->mobile=$mobile;
        $Sms->code=$code;
        $Sms->send_time=$now;
        $Sms->expire_time=$now+30*60;//有效时间是30分钟
        $Sms->save();
    }

    //验证用户输入验证码和手机号是否对应，并且未过期
    public function verifyCode($mobile,$code)
    {
        $now=time();
        $Sms=new SmsModel();
        $record=$Sms->field("id,mobile,code,expire_time")
            ->where("mobile='$mobile' and code='$code' and expire_time>=$now")
            ->order("id desc")
            ->find();
        if(!empty($record)&&$mobile==$record['mobile']&&$code==$record['code']&&$record['expire_time']>=$now){
            //短信验证成功
            return 1;
        }

        //验证码错误或者超时
        $record=$Sms->field("id,mobile,code,expire_time")
            ->where("mobile='$mobile' and code='$code'")
            ->order("id desc")
            ->find();

        if(!empty($record)&&$record!=null&&$record['expire_time']<$now){
            //超时
            return 3;
        }else{
            //验证码错误
            return 2;
        }

    }
}