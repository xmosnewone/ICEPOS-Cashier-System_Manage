<?php
namespace app\api\controller;
use app\admin\components\BuildTreeArray;
use think\facade\Request;
use think\Db;
use model\NewsType;
use model\PosSaleFlow;
use model\SystemConfig;
use model\Member as MemberDb;
use model\MemberLevel;
use model\PosPayFlow;
use model\News as NewsModel;
use model\PortalAd;
use model\PortalAdAttr;
use model\PortalContentExt;
use model\PosBranch;
use model\IntegralMember;
use model\MemberCredit;
use model\MemberMoney;
use model\ItemInfo;
use app\common\service\Sms as SmsNew;
use app\common\logic\Member as MemberService;
use app\common\logic\Wechat;
use think\captcha\Captcha;

/**
 * 小程序会员中心接口
 * @author xmos
 */
class Mcenter extends Super {

    //验证accesstoken
    //$posttoken 是小程序端POST过来的access_token
    private function verify_token($post){
        $token=config("access_token");
        if($post['access_token']!=$token){
            return -10;
        }
        return 1;
    }

    // 通过账号/手机号 +密码登录
    public function account_login() {
        $res = $this->verify_token ( $_POST );
        if ($res == 1) {
            try {
                $account=$_POST['account'];//账号
                $passwd=$_POST['passwd'];//密码
                $member = new MemberDb ();
                $res = $member->getWhere (['mobile'=>$account,'passwd'=>md5($passwd)]);
                if($res!=false&&is_array($res)){
                    $MemberService=new MemberService();
                    $MemberService->refreshMpLogin($account,2,[]);
                    $res['code']="1";
                }else{
                    $res['code']="0";
                }
            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取会员信息(accountLogin)");
                write_log ( "访问方法:获取会员信息(accountLogin)误,错误信息:" . $ex.$message, "api/mcenter/accountLogin" );
                $res['code'] = "-1000";
            }
        }

        return $this->ajaxReturn($res);
    }

    //H5手机号注册新用户
    public function register()
    {
        $res = $this->verify_token($_POST);
        if ($res != 1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $result=$this->Reqmobile($_POST);

        if($result>0){
            return $this->ajaxReturn(['code'=>1,'uid'=>$result]);
        }else{
            return $this->ajaxReturn(['code'=>$result,'uid'=>0]);
        }
    }

    //H5显示验证码
    public function verifyimg(){
        $captcha = new Captcha(['length'=>4,'imageW'=>115,'imageH'=>42,'fontSize'=>16]);
        return $captcha->entry();
    }

    //H5手机号注册
    private function Reqmobile($infos)
    {
        //手机号码检测
        $mobile=$infos['mobile'];
        if(!isMobile($mobile)){
            return -1;//格式错误
        }
        if(!$this->legalMobile($mobile)){
            return -2;//已占用
        }

        //图片验证
        if( !captcha_check($infos['captcha']))
        {
            // 验证失败
            return -9;//图片验证码错误
        }

        //****---------需要短信验证码登录----------------
        $code=$infos['code'];
        $verify=$this->checkSmsCode($mobile,$code);
        //验证成功或者不需要验证都可以跳过
        if($verify!=1){
            return $verify;
        }
        //****---------需要短信验证码登录END----------------

        //执行会员注册
        $MemberService=new MemberService();
        $res=$MemberService->regMobile($infos);
        if($res!==false){
            return $res;
        }else{
            return -5;
        }
    }

    //检测手机号唯一
    private function legalMobile($mobile){
        $Member=new MemberDb();
        $count=$Member->where("mobile='$mobile'")->count();
        if($count>0){
            return false;
        }else{
            return true;
        }
    }

    //检测短信验证码
    //$code 客户收到的验证码
    private function checkSmsCode($mobile,$code){
        $web_config=@include_once APP_DATA . 'data_web_config.php';
        if($web_config&&isset($web_config['sms_verify'])&&$web_config['sms_verify']==1){
            //开启短信验证
            $smsNew=new SmsNew();
            $result=$smsNew->verifyCode($mobile,$code);
            if($result!=1){
                if($result==2){
                    return -3;//验证码错误
                }else{
                    return -4;//短信验证超时
                }
            }
        }
        return 1;
    }

    //H5或微信公众号发送短信验证码
    public function sendSmsCode(){

        $res = $this->verify_token($_POST);
        if ($res != 1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        //需要验证用户端填写的验证码以及UID（必须登录）
        //$verifyCode=$_POST['verifyCode'];//用户页面显示的验证码
        $mobile=$_POST['mobile'];
        //开源版 $captcha = new Captcha();
        //开源版 if( !$captcha->check($safecode))

        /*if( !captcha_check($verifyCode))	// 验证码失败
        {
            return 2;
        }*/

        $web_config=@include_once APP_DATA . 'data_web_config.php';
        if($web_config&&isset($web_config['sms_verify'])){
            if($web_config['sms_verify']==1){
                //检测手机号码是否已注册
                $model=new MemberDb();
                $num=$model->where("mobile='$mobile'")->count();
                if($num>0){
                    //手机号已占用
                    return $this->ajaxReturn(['code'=>6]);
                }

                $code=$this->generateCode();
                //开启短信验证
                $smsNew=new SmsNew();
                if($smsNew->sendSms($mobile,$code,$web_config)===true){
                    return $this->ajaxReturn(['code'=>1]);
                }
            }else{
                    return $this->ajaxReturn(['code'=>3]);//未开启短信验证
            }
        }else{
                    return $this->ajaxReturn(['code'=>3]);//未开启短信验证
        }
    }

    //用户提交修改手机号
    public function modifyMobile()
    {
        $res = $this->verify_token($_POST);
        if ($res != 1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $mobile=$_POST['mobile'];
        $code=$_POST['code'];
        $uid=intval($_POST['uid']);
        if(empty($mobile)||empty($code)){
            return $this->ajaxReturn(['code'=>-2]);
        }

        //****---------需要短信验证码登录----------------
        $verify=$this->checkSmsCode($mobile,$code);
        //验证成功或者不需要验证都可以跳过
        if($verify!=1){
            //验证失败则返回错误 -3/-4
            return $this->ajaxReturn(['code'=>$verify]);
        }
        //****---------需要短信验证码登录END----------------

        //修改用户手机号码
        $model=new MemberDb();
        $num=$model->where("mobile='$mobile'")->count();
        if($num>0){
            //手机号已占用
            return $this->ajaxReturn(['code'=>-6]);
        }
        $res=$model->updateMember(['uid'=>$uid],["mobile"=>$mobile]);
        $code=0;
        if($res!==false){
            $code=1;
        }
        return $this->ajaxReturn(['code'=>$code]);
    }

    //生成短信验证码
    private function generateCode(){
        return rand(1000,9999);
    }

    private function findMember($where){
        $Member=new MemberDb();
        return $Member->where($where)->find();
    }

    //微信小程序用户通过code获取openid\unionid
    public function code2Openid(){
        $res = $this->verify_token($_POST);
        if ($res != 1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $web_config=@include_once APP_DATA . 'data_web_config.php';
        $Wechat=new Wechat($web_config);
        $code=$_POST['code'];//wx_login获取的code
        $result=$Wechat->mpLogin($code);//获取openid、unionid
        if($result['code']==1){
            return $this->ajaxReturn(['code'=>1,'msg'=>$result['msg'],'openid'=>$result['openid'],'unionid'=>$result['unionid']]);
        }else{
            return $this->ajaxReturn(['code'=>0,'msg'=>$result['msg']]);
        }
    }

    //小程序普通登录openid
    public function mplogin(){

        $res = $this->verify_token($_POST);
        if ($res != 1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $web_config=@include_once APP_DATA . 'data_web_config.php';
        $Wechat=new Wechat($web_config);
        $code=$_POST['code'];//wx_login获取的code
        $mobile=$_POST['mobile'];//有可能缓存了手机号
        $result=$Wechat->mpLogin($code);//获取openid、unionid
        if($result['code']==1){
            $isNew=0;
            $passwd=date("Ymd");
            //判断是否已经存在openid
            $member=$this->findMember(['openid'=>$result['openid']]);
            $MemberService=new MemberService();
            if($member==null||empty($member['uid'])){//需要注册
                $isNew=1;
                $memberData=[];
                $memberData['mobile']=!empty($mobile)?ctrim($mobile):'';
                $memberData['openid']=$result['openid'];
                $memberData['unionid']=$result['unionid'];
                $res=$MemberService->regMpwechat($memberData);
            }else{      //已经存在openid，刷新登录日期等
                $res=$MemberService->refreshMpLogin($result['openid'],1,[]);
            }
            $userinfo=[];
            if($res!==false){
                $memberDb=new MemberDb();
                $userinfo = $memberDb->getWhere (['openid'=>$result['openid']]);
                $code=1;
            }else{
                $code=5;
            }
            return $this->ajaxReturn(['code'=>$code,'msg'=>$result['msg'],'userinfo'=>$userinfo,'passwd'=>$passwd,'isNew'=>$isNew]);
        }else{
            return $this->ajaxReturn(['code'=>$result['code'],'msg'=>$result['msg']]);
        }
    }

    //小程序通过手机号登录和注册
    public function mpPhoneLogin()
    {
        $res = $this->verify_token($_POST);
        if ($res != 1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        //小程序快速手机号验证组件获取的code
        $code=$_POST['code'];
        $iv=$_POST['iv'];
        $encryptedData=$_POST['encryptedData'];
        $openid=$_POST['openid'];//openid
        $unionid=$_POST['unionid'];//unionid
        if(empty($code)||empty($iv)||empty($encryptedData)){
            return $this->ajaxReturn(['code'=>4]);
        }

        //判断是否已开启小程序手机号验证登录
        $web_config=@include_once APP_DATA . 'data_web_config.php';
        if($web_config&&isset($web_config['mp_mobile_verify'])&&$web_config['mp_mobile_verify']==1){
            //通过微信小程序接口获取手机号码
            $Wechat=new Wechat($web_config);
            $result=$Wechat->getPhoneNumber($code);
            if($result!=false){
                $isNew=0;
                $passwd=date("Ymd");
                //不带区号的号码
                $mobile=$result['purePhone'];
                //查询是否已有该电话号码的会员
                $member=$this->findMember(['mobile'=>$mobile]);
                $MemberService=new MemberService();
                if($member!=null&&!empty($member['uid'])){
                    //更新登录时间和openid等
                    $res=$MemberService->refreshMpLogin($mobile,2,['openid'=>$openid,'unionid'=>$unionid]);
                }else{
                    $isNew=1;
                    $memberData=[];
                    $memberData['mobile']=$mobile;
                    $memberData['openid']=$openid;
                    $memberData['unionid']=$unionid;
                    $res=$MemberService->regMpwechat($memberData);
                }
                $userinfo=[];
                if($res!==false){
                    $code=1;
                    $msg="success";
                    $memberDb=new MemberDb();
                    $userinfo = $memberDb->getWhere (['mobile'=>$mobile]);
                }else{
                    $code=5;
                    $msg="fail";
                }
                return $this->ajaxReturn(['code'=>$code,'msg'=>$msg,'userinfo'=>$userinfo,'passwd'=>$passwd,'isNew'=>$isNew]);
            }else{
                //获取手机号失败
                return $this->ajaxReturn(['code'=>2,'msg'=>"验证手机号失败"]);
            }
        }else{
            return $this->ajaxReturn(['code'=>3,'msg'=>"系统未开放手机号码注册登录"]);
        }

    }

    //修改用户信息
    public function updateMember()
    {
        $res = $this->verify_token($_POST);
        if ($res != 1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $field=$_POST['field'];//要更新的字段名称
        $value=$_POST['value'];//字段值
        $uid=intval($_POST['uid']);//uid
        if(empty($uid)||empty($field)||!isset($value)){
            //缺少必要值
            return $this->ajaxReturn(['code'=>-2]);
        }
        if($field=='passwd'){
            $MemberService=new MemberService();
            $value=$MemberService->generatePassword($value,1);
        }
        $model=new MemberDb();
        $res=$model->updateMember(['uid'=>$uid],[$field=>$value]);
        $code=0;
        if($res!==false){
            $code=1;
        }
        return $this->ajaxReturn(['code'=>$code]);
    }

    //解除微信绑定
    public function unbindmp(){
        $res = $this->verify_token($_POST);
        if ($res != 1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }
        $uid=intval($_POST['uid']);//uid
        $model=new MemberDb();
        $res=$model->updateMember(['uid'=>$uid],['openid'=>'','unionid'=>'','headimg'=>'']);
        $code=0;
        if($res!==false){
            $code=1;
        }
        return $this->ajaxReturn(['code'=>$code]);
    }

    //上传头像
    public function uploadHeadimg() {

        $res = $this->verify_token($_POST);
        if ($res != 1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $fileName=$_POST['filename'];
        try{
            $result=$this->uploadFile($fileName);
        }catch (\Exception $e){
            return $this->ajaxReturn(['code'=>-2]);
        }


        //返回上传图片成功与否数组
        if(!$result['status']){
            return $this->ajaxReturn(['status'=>-2]);//上传失败
        }
        $pics=$this->api_server.substr($result['path'],1);
        return $this->ajaxReturn(['status'=>1,'url'=>$pics]);//返回图片路径
    }


    //查询积分和优惠券信息/订单/消费总额等信息
    public function userinfo() {
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn($res);
        }

        //获取用户uid和会员编号
        $uid=intval($_POST['uid']);
        $ucode=ctrim($_POST['mem_no']);
        if(empty($uid)||empty($ucode)){
            //-2 缺少用户信息
            return $this->ajaxReturn(-2);
        }

        //查询会员是否存在
        $member = new MemberDb ();
        $res = $member->getWhere (['uid'=>$uid,'ucode'=>$ucode]);
        if($res==false){
            //-3 会员不存在
            return $this->ajaxReturn(-3);
        }

        $MemberLevel=new MemberLevel();
        $level=$MemberLevel->getLevel($res['level']);
        if($level&&isset($level['expire_date'])){
            $res['levelname']=$level['levelname'];
            $res['expire']=date('Y-m-d',$level['expire_date']);
        }else{
            $res['levelname']=lang("common_vip");
            $res['expire']=lang("long_vip");
        }

        //格式化输出
        $res['total_consu']=floatval($res['total_consu']);
        $res['account']=floatval($res['account']);
        //查询优惠券,订单等信息
        $order=$this->getOrderCount($res['ucode']);
        $res['year_consume']=floatval($order['amount']);
        $res['order_count']=$order['num'];

        //不返回密码
        unset($res['passwd']);
        return $this->ajaxReturn($res);
    }


    //订单信息
    private function getOrderCount($ucode) {
        $PosPayFlow=new PosPayFlow();
        $orderNum=$PosPayFlow->getUserOrderCount($ucode);
        $consumeAmount=$PosPayFlow->getUserYearConsume($ucode);
        return ['num'=>$orderNum,'amount'=>$consumeAmount];
    }

    //返回新闻
    public function newslist(){
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn($res);
        }

        $page=!empty($_POST['page'])&&$_POST['page']>0?intval($_POST['page']):1;//当前页面数
        $limit=!empty($_POST['limit'])&&$_POST['limit']>0?intval($_POST['limit']):10;//每页显示10条订单
        $start=($page-1)*$limit;
        $News = new NewsModel();
        $list = $News->where(['is_enabled'=>1])
                    ->field("id,title,headimg,headimg_small,time,link")
                    ->order("time desc")
                    ->limit($start,$limit)
                    ->select()
                    ->toArray();

        //统计总记录数量
        $count=$News->where(['is_enabled'=>1])->count();

        $domain=Request::domain();
        if(is_array($list)&&count($list)>0){
            foreach($list as $k=>$v){
                if(strpos(strtolower($v['headimg_small']),"http")==false){
                    $list[$k]['headimg_small']=$domain.$v['headimg_small'];
                }
                if(strpos(strtolower($v['headimg']),"http")==false){
                    $list[$k]['headimg']=$domain.$v['headimg'];
                }
            }
        }
        return $this->ajaxReturn(['data'=>$list,'count'=>$count]);
    }
    //返回新闻详细
    public function newsdetail(){

        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn($res);
        }
        $id=intval($_POST['id']);
        if(empty($id)){
            return $this->ajaxReturn(-2);
        }
        $News = new NewsModel();
        $detail=$News->where(['id'=>$id])->find();
        if($detail){
            $NewsType=new NewsType();
            $type=$NewsType->where("id='{$detail['type']}'")->find();
            $News->where(['id'=>$id])->inc("visit",1);
            $detail['catename']=$type['name'];
        }
        return $this->ajaxReturn($detail);
    }
    //返回广告图片
    public function advlist(){
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn($res);
        }
        $branch_no = $_POST ['branch_no'];//当前门店
        $space_id = $_POST ['space_id']?intval($_POST ['space_id']):0;//广告位
        $now=date('Y-m-d H:i:s',time());
        $where="a.start_time < '$now' and a.end_time > '$now' and a.is_enabled='1' 
                and a.category='image' and t.attr_key='attr_image_url' and t.attr_value!=''";
        if(!empty($branch_no)){
            $where.=" and a.branch_no='ALL'";
        }else{
            $where.=" and (a.branch_no='ALL' or a.branch_no='$branch_no')";
        }
        if(!empty($space_id)){
            $where.=" and a.ad_space_id='$space_id'";
        }

        $PortalAd = new PortalAd ();
        $adAttrTableModel=new PortalAdAttr();
        $table=$PortalAd->tableName();
        $adAttrTable=$adAttrTableModel->tableName();

        $sql = "SELECT a.ad_id, a.ad_name, a.category, a.ad_code, a.link, a.news_id,a.start_time,a.end_time, a.is_enabled,t.attr_key,t.attr_value"
            . " FROM " . $table . " AS a"
            . " LEFT JOIN " .$adAttrTable. " AS t ON a.ad_id=t.ad_id"
            . " WHERE $where";

        $list=Db::query($sql);

        $domain=Request::domain();
        //返回带域名的图片地址
        foreach($list as $k=>$v){
            $src = $v ['attr_value'];
            $image=$domain.$src;
            $list[$k]['image']=$image;
            $list[$k]['url']=$v ['link'];
            if($v ['news_id']>0){
                $list[$k]['url']='/pages/news/newsdetail?id='.$v ['news_id'];
            }
        }

        return $this->ajaxReturn($list);
    }

    //返回个人订单列表
    public function orderList(){
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $uid=$_POST['uid']?$_POST['uid']:0;//uid
        $vip_no=$_POST['vip_no']?$_POST['vip_no']:"";//vip_no会员编号
        $status=$_POST['order_status']?$_POST['order_status']:0;
        $page=!empty($_POST['page'])&&$_POST['page']>0?intval($_POST['page']):1;//当前页面数
        $limit=!empty($_POST['limit'])&&$_POST['limit']>0?intval($_POST['limit']):10;//每屏滚动显示条数

        if(empty($uid)||empty($vip_no)){
            //-2
            return $this->ajaxReturn(['code'=>-2]);
        }

        //返回以订单号分组的个人订单列表
        $PosPayFlow=new PosPayFlow();
        $start=($page-1)*$limit;
        $orders=$PosPayFlow->getUserOrderList($vip_no,$status,$start,$limit);
        if(count($orders['list'])<=0){
            return $this->ajaxReturn(['code'=>0]);
        }

        $domain=Request::domain();
        $list=$orders['list'];
        foreach($list as $k=>$v){
            if(trim($v['branch_logo'])!=""){
                //带域名的绝对地址
                $list[$k]['branch_logo']=$domain.$v['branch_logo'];
            }
            if(count($v['sales_info'])>0){
                $sales_info=$v['sales_info'];
                foreach($sales_info as $kv=>$vv){
                    if($vv['img_src']!=''){
                        $sales_info[$kv]['img_src']=$domain.$vv['img_src'];
                    }
                }
                $list[$k]['sales_info']=$sales_info;
            }
        }

        return $this->ajaxReturn(['code'=>1,"orders"=>$list,'total'=>$orders['total']]);
    }

    //订单查询
    public function orderDetail(){
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $flow_no=$_POST['flow_no']?$_POST['flow_no']:"";//订单编号
        if(empty($flow_no)){
            //-2
            return $this->ajaxReturn(['code'=>-2]);
        }

        $PosPayFlow=new PosPayFlow();
        $flows=$PosPayFlow->getFlowItems($flow_no);
        if(empty($flows)||count($flows)<=0){
            return $this->ajaxReturn(['code'=>2]);
        }

        $order=[];

        //备注
        $memo=[];
        //所有支付方式
        $payways=[];
        //积分支付
        $credit_pay=0;
        //优惠券支付
        $coupon_pay=0;
        //实际支付
        $real_pay=0;//通过 consume_payment() 获取到实际现金或微信登支付方式的支付金额
        $real_payment=consume_payment();
        $real_payment[]='GZ';
        foreach($flows as $k=>$v){
            $payways[]=$v['pay_name'];
            $memo[]=$v['memo'];
            if($v['coin_type']=='CRED'){//积分支付
                $credit_pay+=$v['pay_amount'];
            }elseif($v['coin_type']=='COUPON'){//优惠券支付
                $coupon_pay+=$v['pay_amount'];
            }elseif(in_array($v['coin_type'],$real_payment)){
                $real_pay+=$v['pay_amount'];//统计实际支付金额
            }
        }
        $order['pay_name']=$payways;
        $order['credit_pay']=$credit_pay;
        $order['coupon_pay']=$coupon_pay;
        $order['real_pay']=sprintf("%.2f",$real_pay);
        $order['oper_date']=$flows[0]['oper_date'];
        $order['memo']=$memo;
        //订单状态
        $status=$PosPayFlow->OrderStatus($flows);
        $order['order_status']=$status;


        //获取商品信息
        $sale_qnty=0;
        $discount=0;
        $goods_amount=0;
        //记录所有促销
        $plans=[];
        $salesInfo=$PosPayFlow->SalesInfo($flow_no);
        foreach($salesInfo as $sv){
            //计算优惠
            $sum=$sv['sale_qnty']*$sv['unit_price'];
            $goods_amount+=$sum;
            $discount+= $sum-($sv['sale_qnty']*$sv['sale_price']);
            $sale_qnty+=$sv['sale_qnty'];

        }

        $order['flow_no']=$flows[0]['flow_no'];
        $order['sales_info']=$salesInfo;
        $order['goods_amount']=sprintf("%.2f",$goods_amount);
        $order['discount']= $discount>=0?sprintf("%.2f",$discount):0;
        $order['sale_qnty']= $sale_qnty;
        $order['plans']= $plans;

        //获取门店信息
        $domain=Request::domain();
        $PosBranch=new PosBranch();
        $branch_no=$flows[0]['branch_no'];
        $branch=$PosBranch->getone($branch_no);
        $order['branch_no']="";
        $order['branch_name']="";
        $order['branch_logo']="";
        if($branch!==false){
            $order['branch']=$branch['branch_no'];
            $order['branch_name']=$branch['branch_name'];
            if($branch['logo']!=''){
                $order['branch_logo']=$domain.$branch['logo'];
            }
        }

        //获取会员等级信息
        $vip_no=$flows[0]['vip_no'];
        $order['levelname']="";
        if(!empty($vip_no)){
            $MemberDb=new MemberDb();
            $member=$MemberDb->getOne($vip_no);
            if(!empty($member)){
                $order['levelname']=$member['levelname'];
            }else{
                $order['levelname']=lang("common_vip");
            }
        }

        return $this->ajaxReturn(['order'=>$order,'code'=>1]);
    }

    //barcode订单条码
    public function barcodeDetail(){
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $flow_no=$_POST['flow_no']?$_POST['flow_no']:"";//订单编号
        if(empty($flow_no)){
            //-2
            return $this->ajaxReturn(['code'=>-2]);
        }

        $PosPayFlow=new PosPayFlow();
        $flows=$PosPayFlow->getFlowItems($flow_no);
        if(!empty($flows)&&count($flows)>0){
            return $this->ajaxReturn(['code'=>1,"flow_no"=>$flow_no,'id'=>$flows[0]['id']]);
        }

        return $this->ajaxReturn(['code'=>2]);
    }

    //积分列表
    public function creditList(){
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $uid=$_POST['uid']?$_POST['uid']:0;//uid
        $vip_no=$_POST['vip_no']?$_POST['vip_no']:"";//vip_no会员编号
        $condition=$_POST['condition']?$_POST['condition']:0;
        $page=!empty($_POST['page'])&&$_POST['page']>0?intval($_POST['page']):1;//当前页面数
        $limit=!empty($_POST['limit'])&&$_POST['limit']>0?intval($_POST['limit']):20;//每屏滚动显示条数

        if(empty($uid)||empty($vip_no)){
            //-2
            return $this->ajaxReturn(['code'=>-2]);
        }

        //返回列表
        $total=0;
        //$condtion  0 = 积分收入  1=积分支出 2 已过期
        $list=[];
        if($condition==0){
            list($list,$total)=$this->creditIncome($uid,$page,$limit);
        }else if($condition==1){
            list($list,$total)=$this->creditOut($uid,$page,$limit);
        }else{
            list($list,$total)=$this->creditExpire($uid,$page,$limit);
        }

        foreach($list as $k=>$v){
            $list[$k]['flow_no']=isset($v['flow_no'])?$v['flow_no']:$v['flowno'];
            $list[$k]['add_date']=date('Y-m-d H:i:s',$v['add_date']);
            if($condition==0){
                $list[$k]['act']='+';
            }else if($condition==1){
                $list[$k]['act']='-';
            }else{
                $list[$k]['act']='';
            }
        }

        return $this->ajaxReturn(['code'=>1,"list"=>$list,'total'=>$total]);
    }

    private function getExpireWhere()
    {
        $web_config=@include_once APP_DATA . 'data_web_config.php';
        $expire_year=1;
        if($web_config&&isset($web_config['credit_expire'])&&!empty($web_config['credit_expire'])){
            $expire_year=$web_config['credit_expire'];
        }
        $expire_year=intval($expire_year);

        $where="";
        if($expire_year>=1) {
            $yearDate = strtotime("-" . $expire_year . " year");
            $where.=" or add_date<$yearDate ";
        }
        return $where;
    }
    //积分收入
    private function creditIncome($uid,$page,$limit){
        $start=($page-1)*$limit;
        $IntegralMember=new IntegralMember();
        $where="is_expire='0'";
        $where.=$this->getExpireWhere();
        $list=$IntegralMember->where("uid='$uid' and refund_flag!='1' and ($where)")
                            ->order("add_date desc")
                            ->limit($start,$limit)
                            ->select();
        $total=$IntegralMember->where("uid='$uid' and refund_flag!='1' and ($where)")->count();
        if($total>0&&count($list)>0){
            $list=$list->toArray();
        }
        return [$list,$total];
    }

    //积分支出
    private function creditOut($uid,$page,$limit){
        $start=($page-1)*$limit;
        $memberCredit=new MemberCredit();
        $list=$memberCredit->where("uid='$uid' and action='-'")
                            ->order("add_date desc")
                            ->limit($start,$limit)
                            ->select();
        $total=$memberCredit->where("uid='$uid' and action='-'")->count();
        if($total>0&&count($list)>0){
            $list=$list->toArray();
        }
        return [$list,$total];
    }

    //已过期
    private function creditExpire($uid,$page,$limit){
        $start=($page-1)*$limit;
        $IntegralMember=new IntegralMember();
        //返回系统设置有效期的积分
        $where="is_expire='1'";
        $where.=$this->getExpireWhere();

        $list=$IntegralMember->where("uid='$uid' and refund_flag!='1' and ($where) ")
                            ->order("add_date desc")
                            ->limit($start,$limit)
                            ->select();
        $total=$IntegralMember->where("uid='$uid' and refund_flag!='1' and ($where) ")->count();
        if($total>0&&count($list)>0){
            $list=$list->toArray();
        }
        return [$list,$total];
    }

    //余额变动列表
    public function memberAccount(){
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $uid=$_POST['uid']?$_POST['uid']:0;//uid
        $page=!empty($_POST['page'])&&$_POST['page']>0?intval($_POST['page']):1;//当前页面数
        $limit=!empty($_POST['limit'])&&$_POST['limit']>0?intval($_POST['limit']):10;//每屏滚动显示条数
        $offset=($page-1)*$limit;
        if(empty($uid)){
            //-2
            return $this->ajaxReturn(['code'=>-2]);
        }

        //返回列表
        $total=0;
        //type字段  1:消费 2:充值 3:余额支付退款
        $list=[];
        $mmDb=new MemberMoney();
        $rlist=$mmDb->where("uid='$uid'")->order("add_date desc")->limit($offset,$limit)->select();
        $total=$mmDb->where("uid='$uid'")->count();
        if($rlist!==false&&count($rlist)>0){
            $list=$rlist->toArray();
            foreach($list as $k=>$v){
                $list[$k]['add_date']=date('Y-m-d H:i:s',$v['add_date']);
                $type="";
                $act="";
                switch ($v['type']) {
                    case 1:
                        $type="消费";
                        $act="-";
                        break;
                    case 2:
                        $type="充值";
                        $act="+";
                        break;
                    case 3:
                        $type="退款";
                        $act="+";
                        break;
                }
                $list[$k]['type']=$type;
                $list[$k]['act']=$act;
            }
        }

        return $this->ajaxReturn(['code'=>1,"list"=>$list,'total'=>$total]);
    }

    //统计年报
    public function yearReport(){
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $uid=$_POST['uid']?$_POST['uid']:0;//uid
        $vip_no=$_POST['vip_no']?$_POST['vip_no']:"";//vip_no会员编号

        if(empty($uid)||empty($vip_no)){
            //-2
            return $this->ajaxReturn(['code'=>-2]);
        }

        $PosPayflow=new PosPayFlow();
        $PosSaleflow=new PosSaleFlow();
        $memberDb=new MemberDb();

        //称号
        $member=$memberDb->where("uid='$uid' and ucode='$vip_no'")->find();
        if(!$member||empty($member)){
            //用户不存在
            return $this->ajaxReturn(['code'=>-3]);
        }

        //$lastYear=date('Y',time())-1;
        $lastYear=date('Y',time());
        $yearStart=$lastYear."-01-01 00:00:00";
        $yearEnd=$lastYear."-12-31 23:59:59";
        //$yearTimeStamp=timezone_get(10);
        $yearTimeStamp=timezone_get(6);
        $yearTimeStart=$yearTimeStamp['begin'];
        $yearTimeEnd=$yearTimeStamp['end'];
        //总支出金额

        //总消费金额
        $totalConsume=0;

        //现金或其他实际货币支付方式
        $realPayment=consume_payment();
        $cp=simplode($realPayment);

        //总订单数 有退款记录的不统计在内
        $totalOrder=$PosPayflow->where("vip_no='$vip_no' and coin_type in ($cp) and refund_flag!=1 and oper_date>='$yearStart' and oper_date<='$yearEnd'")
                                 ->group("flow_no")
                                 ->count();
        if($totalOrder>0&&$totalConsume<=0){
            //重新统计一次消费金额
            $totalConsume=$PosPayflow->where("vip_no='$vip_no' and coin_type in ($cp) and refund_flag!=1 and oper_date>='$yearStart' and oper_date<='$yearEnd'")
                                     ->sum("pay_amount");
        }

        //查询积分支付金额
        $creditDiscount=$PosPayflow->where("vip_no='$vip_no' and coin_type='CRED' and refund_flag!=1 and oper_date>='$yearStart' and oper_date<='$yearEnd'")
                                ->sum("pay_amount");
        $couponDiscount=$PosPayflow->where("vip_no='$vip_no' and coin_type='COUPON' and refund_flag!=1 and oper_date>='$yearStart' and oper_date<='$yearEnd'")
                                ->sum("pay_amount");


        //消费总排名
        /*$totalMember=$memberDb->count();
        $behindMember=$memberDb->where("total_consu<$totalConsume")->count();
        $percent=100;
        if($totalMember>0&&$behindMember>0){
            $percent=round($behindMember/$totalMember,2)*100;
        }*/

        //总商品金额
        $goodsAmount=0;
        $salesAmount=0;
        $goods=[];
        //查询有效支付流水
        $ids=$PosPayflow->where("vip_no='$vip_no' and coin_type in ($cp) and refund_flag!=1 and oper_date>='$yearStart' and oper_date<='$yearEnd'")->column("flow_no");
        if(!empty($ids)&&count($ids)>0){
                $flow_no=simplode($ids);
                //查询下单商品总金额---此处可能出现慢查询
                $goodsAmount=$PosSaleflow->where("flow_no in ($flow_no)")->sum("goods_money");
                $salesAmount=$PosSaleflow->where("flow_no in ($flow_no)")->sum("sale_money");

                //查询商品--饼图数据分析用
                $goods = $PosSaleflow->alias("a")
                                        ->join("bd_item_info b", "a.item_no=b.item_no","LEFT")
                                        ->where("a.flow_no in ($flow_no)")
                                        ->field("a.item_no,a.sale_qnty,b.item_clsno")
                                        ->select();
                if($goods!=false&&count($goods)>0){
                    $goods=$goods->toArray();
                }

        }else{
            if($totalConsume>0){
                $goodsAmount=$totalConsume;
                $salesAmount=$totalConsume;
            }
        }


        //商品折扣优惠总节省金额
        $goodsDiscount=($goodsAmount-$salesAmount)>0?($goodsAmount-$salesAmount):0;
        //总节省
        $totalDiscount=$goodsDiscount+$creditDiscount+$couponDiscount;

        //获得积分
        $IntegralMember=new IntegralMember();
        $totalCredit=$IntegralMember->where("uid='$uid' and types='0' and refund_flag!='1' and add_date>=$yearTimeStart and add_date<=$yearTimeEnd")->sum("credit");

        //账单概况
        $sumary=[
            'totalconsume'=>round($totalConsume,2),//总支出
            'totalorder'=>$totalOrder,//订单数
            'totalcredit'=>round($totalCredit,2),//总积分
            'goodsdiscount'=>round($goodsDiscount,2),//商品总节省
            'creditdiscount'=>round($creditDiscount,2),//积分抵扣节省
            'coupondiscount'=>round($couponDiscount,2),//优惠券优惠节省
            'totaldiscount'=>round($totalDiscount,2),//全部节省金额
            //'percent'=>$percent,//超过百分之几用户
        ];

        //饼图按照商品分类查询
        $pieCharts=[];
        if(count($goods)>0){
            $data = M ( "bd_item_cls" )->field ( "item_clsno as id, cls_parent as fid,item_clsname as text" )->select ();
            $aa = array ();
            foreach ( $data as $k => $v ) {
                $aa [$k] = $v;
                $aa [$k] ['title'] = $data [$k] ['text'] . "(" . $aa [$k] ['id'] . ")";
            }
            $data = $aa;
            unset($aa);
            $bta = new BuildTreeArray ( $data, 'id', 'fid', 0 );
            $allcates = $bta->getTreeArray ();//所有分类

            //查找三层
            $charts=[];
            foreach($goods as $k=>$v){
                $father=$this->getFatherPid($allcates,$v['item_clsno']);
                if($father!==false&&isset($father['id'])&&isset($father['text'])){
                    if(!isset($charts[$father['id']])){
                        $charts[$father['id']]['name']=$father['text'];
                        $charts[$father['id']]['value']=intval($v['sale_qnty']);
                    }else{
                        $charts[$father['id']]['value']+=intval($v['sale_qnty']);
                    }
                }

            }
            if(count($charts)>0){
                foreach($charts as $v){
                    $pieCharts[]=$v;
                }
            }
        }

        //最爱单品
        $favorite=[];
        //$dbconfig=config("database");
        //$dbprefix=$dbconfig['connections']['mysql']['prefix'];
        //TP5
        $dbprefix=config("database.prefix");
        $saleWhere="";
        if(!empty($ids)&&count($ids)>0){
            $flow_no=simplode($ids);
            $saleWhere.=" and flow_no in ($flow_no)";
            $sql="select `item_no`,count(`item_no`) num from `".$dbprefix."pos_saleflow` where oper_date>='$yearStart' and oper_date<='$yearEnd' $saleWhere group by `item_no` order by count(`item_no`) desc,oper_date desc limit 3;";
            $fa_itemno=Db::query($sql);
        }else{
            $fa_itemno=[];
        }

        if(!empty($fa_itemno)&&count($fa_itemno)>0){
            $items=[];
            foreach($fa_itemno as $k=>$v){
                if(!isset($items[$v['item_no']])){
                    $items[$v['item_no']]=$v['num'];
                }
            }

            if(count($items) > 0){
                $where=simplode(array_keys($items));
                $ItemInfo=new ItemInfo();
                $iteminfos=$ItemInfo->field("item_no,item_name")->where("item_no in ($where)")->select();
                if(!empty($iteminfos)){
                    foreach($iteminfos as $k=>$v){
                        $t=$v;
                        $t['num']=$items[$v['item_no']];
                        $favorite[]=$t;
                    }
                }
                if(count($favorite)>0){
                    //根据下单数量从高到低排序
                    $buynum=[];
                    foreach ($favorite as $key => $row) {
                        $buynum[$key] = $row['num'];
                    }
                    array_multisort($buynum, SORT_DESC, $favorite);
                }
            }
        }//end of if

        //消除内存占用
        unset($ids);
        unset($goods);

        //称呼
        $nick=lang("spirit_boy");
        if($member['sex']!=0){
            $nick=lang("god_lady");
        }

        return $this->ajaxReturn(['code'=>1,'lastyear'=>$lastYear,'summary'=>$sumary,'piecharts'=>$pieCharts,'favorite'=>$favorite,'nick'=>$nick]);
    }

    //查询商品所属大分类
    private function getFatherPid($cates,$goods_type){
        //一级
        foreach($cates as $v){
            if($v['id']==$goods_type){
                return $v;
            }
            //二级
            if(isset($v['children'])&&count($v['children'])>0) {
                foreach ($v['children'] as $vv) {
                    if ($vv['id'] == $goods_type) {
                        return $v;
                    }
                    //三级
                    if (isset($vv['children']) && count($vv['children']) > 0) {
                        foreach ($vv['children'] as $vvv) {
                            if ($vvv['id'] == $goods_type) {
                                return $v;
                            }
                        }
                    }
                }
            }
        }//end of foearch
        return false;
    }

    //系统配置信息
    public function systeminfo(){
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }
        $configs=['web_name','mp_mobile_verify','sms_verify'];
        $keys=simplode($configs);

        $data=[];
        foreach($configs as $v){
            $data[$v]="";
        }

        $configDb=new SystemConfig();
        $systeminfo=$configDb->where("`key` in ($keys)")->select();
        if($systeminfo!==false&&count($systeminfo)>0){
            $systeminfo=$systeminfo->toArray();
            foreach($systeminfo as $v){
                if(in_array($v['key'],$configs)){
                    $data[$v['key']]=$v['value'];
                }
            }
        }
        return $this->ajaxReturn(['code'=>1,"content"=>$data]);
    }

    //获取积分使用协议\用户协议\隐私协议
    public function portocalContent(){
        $res = $this->verify_token ( $_POST );
        if ($res!=1) {
            //-10 token 验证失败
            return $this->ajaxReturn(['code'=>-10]);
        }

        $content_id=intval($_POST['cid']);
        $Db=new PortalContentExt();
        $portocal=$Db->where("content_id='$content_id'")->find();
        return $this->ajaxReturn(['code'=>1,"content"=>$portocal->txt]);
    }
}
