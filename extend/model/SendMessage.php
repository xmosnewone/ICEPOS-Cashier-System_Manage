<?php
//send_message表
namespace model;
use think\Model;
use model\SendType;
use think\Db;

class SendMessage extends BaseModel {
	
	const EFFECT_TIME=1;
	
	protected $pk='message_id';
	protected $name="send_message";
	
    public function search() {
    }

    public function ValidateMessage($sendTo, $sendNo, $code) {
        $result = 0;
        try {
            $sendMessage = $this->GetSendMessage($sendTo, $sendNo);
            if (!empty($sendMessage)) {
                if ($sendMessage->message_code === $code) {
                    $sendMessage->message_vertify = "1";
                    if ($sendMessage->save()) {
                        $result = 1;
                    }
                }
            }
        } catch (\Exception $ex) {
            $parameter = "接受者:" . $sendTo . "发送类型:" . $sendNo;
            write_log("验证消息异常,参数" . $parameter . ",错误:" . $ex, "SendMessage");
            $result = -2;
        }
        return $result;
    }


    public function Send($sendTo, $sendNo, $message) {
        $result = 0;
        try {
        	$StModel=new SendType();
            $sendType = $StModel->GetSend($sendNo);
            $issave = true;
            if (!empty($sendType)) {
                if ($sendType->send_vertify === '1') {
                    $sendMessage = $this->GetSendMessage($sendTo, $sendNo);
                    if (!empty($sendMessage)) {
                        $message = $sendMessage->message_code;
                        $issave = false;
                    }
                }
            }

            $result1 = $this->SendMessageByInterface($sendTo, $sendType, &$message, $issave);
            if ($issave) {
                $result = $this->AddSendMessage($sendTo, $sendNo, $message, $result1);
            } else {

                $sendMessage->message_senddate = date(DATETIME_FORMAT);
                if ($sendMessage->save()) {
                    $result = $result1;
                }
            }
        } catch (\Exception $ex) {
            $parameter = "接受者:" . $sendTo . "发送类型:" . $sendNo;
            write_log("发送消息异常,参数" . $parameter . ",错误:" . $ex, "SendMessage");
            $result = -2;
        }
        return $result;
    }


    public function AddSendMessage($sendTo, $sendNo, $sendCode, $sendStatus) {
        $result = 0;
        try {
            $model = new SendMessage();
            $model->message_code = $sendCode;
            $model->message_recevier = $sendTo;
            $model->message_type = $sendNo;
            $model->message_vertify = "0";
            $model->message_send = $sendStatus;
            if ($model->save()) {
                $result = $sendStatus === 1 ? 1 : 0;
            }
        } catch (\Exception $ex) {
            $parameter = "接受者:" . $sendTo . "发送类型:" . $sendNo;
            write_log("新增消息异常,参数" . $parameter . ",错误:" . $ex, "SendMessage");
            $result = -2;
        }
        return $result;
    }


    public function SendMessageByInterface($sendTo, $sendType, &$message, $reSend) {
        $result = 0;
        try {
            if ($sendType->send_status == '1') {
                $content = $sendType->send_content;
                $position = strpos($content, "%code%");
                if ($position) {
                    $message = $reSend ? rand(100000, 999999) : $message;
                    if ($sendType->send_type === "1") {
                        $message1 = str_replace("%code%", $message, str_replace("%EFFECT_TIME%", self::EFFECT_TIME, $content));
                        //if (GobalFunc::SendSMSMessage($sendTo, $message1) === "ok") {
                            $result = 1;
                        //}
                    }
                    else if ($sendType->send_type === "2") {
                        $message1 = str_replace("%code%", $message, str_replace("%EFFECT_TIME%", self::EFFECT_TIME, $content));
                        //if (GobalFunc::SendEmailMessage($sendTo, $sendType->send_name, $message1) === "ok") {
                            $result = 1;
                        //}
                    }
                } else {
                }
            }
        } catch (\Exception $ex) {
            $parameter = "接受者:" . $sendTo . "发送类型:" . $sendType->send_no;
            write_log("发送消息异常,参数" . $parameter . ",错误:" . $ex, "SendMessage");
            $result = -2;
        }
        return $result;
    }


    public function GetSendMessage($sendTo, $sendNo) {
    	return Db::table($this->table)->where("message_recevier='$sendTo' and message_type='$sendNo' and message_vertify='0' and message_send='1' and message_senddate >= DATE_SUB(NOW(),INTERVAL 1 MINUTE)")->find();
    }

}
