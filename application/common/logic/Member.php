<?php
namespace app\common\logic;
use model\Member as MemberModel;
use model\MemberLevel;

class Member{
    private $letter=array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
    //生成会员编号
    public function generateUcode(){
        $random1=rand(0,25);
        $random2=rand(0,25);
        $Prefix=$this->letter[$random1].$this->letter[$random2];
        $suffix=rand(500,999);
        $now=time();
        return strtoupper($Prefix).substr($now,2).$suffix;
    }

    //生成临时会员昵称
    public function generateNickname(){
        $random1=rand(0,25);
        $Prefix=$this->letter[$random1];
        $suffix=rand(500,999);
        $now=time();
        return strtoupper($Prefix).substr($now,2).$suffix;
    }

    //生成密码
    //$regtype  1:手机注册  2:小程序
    //$data 是注册会员数组
    public function generatePassword($data,$regtype){
        switch ($regtype) {
            case 1:
                //手机号码的md5值
                return md5($data);
                break;
            case 2:
                //注册日期 例如: 20250101的md5值
                return md5(date('Ymd',time()));
                break;
        }
    }

    //获取最初级会员等级
    private function getLowestLevel(){
        $MemberLevel=new MemberLevel();
        $lowest=$MemberLevel->order("discount desc")->find();
        return $lowest;
    }

    //执行手机号码注册会员
    public function regMobile($memberData){
        $now=time();
        $mobile=ctrim($memberData['mobile']);
        $branch_no=$memberData['branch_no']?$memberData['branch_no']:'';
        $ucode=$this->generateUcode();
        $nickname=$memberData['nickname']?$memberData['nickname']:$this->generateNickname();
        $password=$this->generatePassword($memberData['passwd']?$memberData['passwd']:$memberData['mobile'],1);
        $level=$this->getLowestLevel();
        $levelid=0;
        if($level!=null&&!empty($level['lid'])){
            $levelid=$level['lid'];
        }

        $member=new MemberModel();
        $member->uname=$nickname;
        $member->utype='MB';//移动端手机号注册
        $member->passwd=$password;
        $member->nickname=$nickname;
        $member->mobile=$mobile;
        $member->ucode =$ucode;
        $member->level=$levelid;
        $member->phone=$mobile;
        $member->regtime=$now;
        $member->addtime=$now;
        $member->regtime=$now;
        $member->branch_no=$branch_no;
        try{
            $ok=$member->save();
        }catch (\Exception $e){

        }

        if($ok!=false){
            return $member->uid;
        }
        return false;
    }

    //小程序登录，通过openid检测用户是否已经注册
    public function checkOpenid($openid,$unionid)
    {
        $MemberModel=new MemberModel();
        $member=$MemberModel->where(['openid'=>$openid,'unionid'=>$unionid])->find();
        if(!empty($member)&&!empty($member['uid'])){
            return $member;
        }else{
            return false;
        }
    }
    //小程序注册会员
    public function regMpwechat($memberData){
        $now=time();
        $mobile=$memberData['mobile']?ctrim($memberData['mobile']):'';//小程序开启获取手机号快速验证模块
        $branch_no=$memberData['branch_no']?$memberData['branch_no']:'';
        $openid=$memberData['openid']?ctrim($memberData['openid']):'';
        $unionid=$memberData['unionid']?ctrim($memberData['unionid']):'';
        $ucode=$this->generateUcode();
        $nickname=$this->generateNickname();
        $password=$this->generatePassword('',2);
        $level=$this->getLowestLevel();
        $levelid=0;
        if($level!=null&&!empty($level['lid'])){
            $levelid=$level['lid'];
        }

        $member=new MemberModel();
        $member->uname=$nickname;
        $member->utype='MP';//移动端小程序注册
        $member->passwd=$password;
        $member->nickname=$nickname;
        $member->mobile=$mobile;
        $member->ucode =$ucode;
        $member->level=$levelid;
        $member->phone=$mobile;
        $member->openid=$openid;
        $member->unionid=$unionid;
        $member->regtime=$now;
        $member->addtime=$now;
        $member->regtime=$now;
        $member->branch_no=$branch_no;
        $ok=$member->save();
        if($ok!=false){
            return $member->uid;
        }
        return false;
    }

    //小程序刷新会员登录时间等信息
    //$key 是手机号或者openid,$type 1是openid,2手机号
    public function refreshMpLogin($key,$type,$updateData=[]){
        $now=time();
        $where=['openid'=>$key];
        if($type==2){
            $where=['mobile'=>$key];
        }
        $model=new MemberModel();
        $data=[];
        foreach($updateData as $k=>$v){
            $data[$k]=$v;
        }
        $data['logintime']=$now;
        return $model->updateMember($where,$data);
    }
}