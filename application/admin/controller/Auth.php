<?php
/**
 * 后台登录验证类
 */
namespace app\admin\controller;
use app\admin\components\OperatorIdentity;
use app\admin\components\Enumerable\ESession;
use think\captcha\Captcha;

class Auth extends Super {

	public function initialize() {
		//重写Super函数，不判断权限
		$this->cache();
	}
	
	//验证类
   	public function index() {
      $loginname = '';
      $password = '';
      $data=input("");
      if (!empty($data['loginname'])) {
         $loginname = $data['loginname'];//账号
         $password = $data['password'];//密码
         $safecode = $data['safecode'];//验证码
         $identity = new OperatorIdentity($loginname, md5($password));
         
         $captcha = new Captcha();
         if( !$captcha->check($safecode))	// 验证码失败
         {
         	$msgError = lang("vericode_error"); 
         	
         }elseif ($identity->authenticate()) {
         	//设置session
         	$this->setUserSession($identity);
         	//登录成功跳转
            $this->redirect(U('index/index'));
            return;
         } else {
            $msgError = lang("login_error");
         }

         $this->assign('msgError', $msgError);
      }
		return	$this->fetch('login');
   }
   
   //设置用户session
   private function setUserSession($identity) {
   		$data=	[
   					'rid'=> $identity->getrid(),
   					'uid'=>$identity->getId(),
   					'loginname'=>$identity->getloginname(),
   					'nickname'=>$identity->getnickname(),
   				];
   		$ESession=new ESession();
   		$ESession->setSession($data);
   		
   }

	//登录验证码
   public function captcha() {
     	$captcha = new Captcha(['length'=>4,'imageW'=>115,'imageH'=>42,'fontSize'=>16]);
        return $captcha->entry();    
   }

   //注销登录
   public function logout() {
     	session (null);
		return 1;
   }

}