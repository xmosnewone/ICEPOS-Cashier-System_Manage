<?php
namespace app\common\service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer{

    public $config;

    /**
     * 构造函数
     */
    public function __construct($web_config){
        $this->config=$web_config;
    }

    /**
     * 发送邮件
     */
    public function sendMail($data){

        if($this->config['email_is_use']!=1){
            return false;
        }

        require '../vendor/autoload.php';

        $mail = new PHPMailer(true);

        try {

            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = $this->config['email_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['email_addr'];
            $mail->Password   = $this->config['email_password'];
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = $this->config['email_port'];

            //设置发送人信息
            $mail->setFrom($this->config['email_addr'], $this->config['email_from']);
            //receiver 是收件人邮箱,nickname 收件人名称
            $mail->addAddress($data['receiver'], $data['nickname']);

            //发送内容
            $mail->isHTML(true);
            $mail->Subject = $data['subject'];
            $mail->Body    = $data['body'];

            //Server settings
            /*$mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = 'smtp.163.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lovey@163.com';
            $mail->Password   = 'LPGUJFFF111-1';
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;
            //LGAGMGHTETRLUCRQ

            //Recipients
            $mail->setFrom('lovey@163.com', 'XX');
            $mail->addAddress('1428071793@qq.com', 'xx');
            //$mail->addReplyTo('info@example.com', 'Information');


            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Here is the subject';
            $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
            */
            return $mail->send();

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 发送邮箱绑定验证码
     * $
     */
    public function sendVericode($receiver,$nickname,$uid=0){
        $code=$this->createCode();
        $subject="邮箱绑定验证码";
        $body="您的验证码:".$code."        <br/>30分钟内有效!";

        $data=[];
        $data['receiver']=$receiver;
        $data['nickname']=$nickname;
        $data['subject']=$subject;
        $data['body']=$body;
        $ok=$this->sendMail($data);
        if($ok!==false){
            //记录验证码
            $rs=[];
            $rs['email']=$receiver;
            $rs['code']=$code;
            $rs['uid']=$uid;
            $this->record($rs);
            return true;
        }

        return false;
    }

    /**
     * 生成验证码
     */
    public function createCode(){
        $code=rand(100005,999998);
        return $code;
    }

    /**
     * 记录发送信息，之后验证
     */
    public function record($data){
        $res=[];
        $res['email']=$data['email'];
        $res['code']=$data['code'];
        $res['uid']=$data['uid'];
        $res['create_time']=time();

        M("email_verify")->where(['email'=>$data['email']])->delete();
        return M("email_verify")->insertGetId($res);
    }

    /**
     *验证发送代码和用户填写的代码
     */
    public function verifyCode($code,$email){
        $res=M("email_verify")->where("code='$code' and email='$email'")->find();
        $now=time();
        $timeLimit=30*60;
        if(!$res||!$res['id']){
            return ['status'=>2,'msg'=>'邮箱验证失败'];
        }else{
            if(($now-$res['create_time'])>$timeLimit){
                return ['status'=>2,'msg'=>'验证码过期'];
            }else{
                return ['status'=>1,'msg'=>'邮箱验证成功'];
            }
        }
    }


}