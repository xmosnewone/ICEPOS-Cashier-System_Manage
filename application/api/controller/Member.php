<?php
namespace app\api\controller;
use think\Db;
use think\facade\Request;
use think\Controller;
use model\Member as MemberDb;
use model\Integral;
use model\IntegralMember;
use model\MemberMoney;
use model\MemberLevel;
use model\BaseModel;
use model\PosPayFlow;
/**
 * 会员接口
 * @author xmos
 */
class Member extends Super {

    //门店退货，返还积分和余额
    public function usescore(){
        $res = $this->ApiConnect ( $_POST );
        if ($res != 1) {
            return $this->ajaxReturn($res);
        }

        try {
            $mem_no=trim($_POST['mem_no']);//会员编号
            $money=floatval($_POST['money']);//使用金额
            $flowno=trim($_POST['ordername']);//订单流水号

            $db = new MemberDb();
            $member=$db->getWhere(['ucode'=>$mem_no]);
            if($member){
                //更新用户的余额
                $db->updateMoney("inc",['uid'=>$member['uid']],$money);

                //添加余额变动记录-退还金额
                $MemberMoney=new MemberMoney();
                $MemberMoney->uid=$member['uid'];
                $MemberMoney->flowno=$flowno;
                $MemberMoney->type=2;//退还余额
                $MemberMoney->money=$money;
                $MemberMoney->add_date=time();
                $MemberMoney->add($MemberMoney);

                $res=[];
                $res['code']=1;
            }

        } catch ( \Exception $ex ) {
            $message=$this->getVars("方法:退货接口(usescore)");
            write_log ( "访问方法:退货接口(usescore),错误信息:" . $ex.$message, "api/member/usescore" );
            $res = "";
        }

        return $this->ajaxReturn($res);
    }

    //修改会员余额
    public function deduction(){
        $res = $this->ApiConnect ( $_POST );
        if ($res != 1) {
            return $this->ajaxReturn($res);
        }

        try {
            $mem_no=trim($_POST['mem_no']);//会员编号
            $money=floatval($_POST['money']);//使用金额
            $flowno=trim($_POST['ordername']);//订单流水号

            $db = new MemberDb ();
            $member=$db->getWhere(['ucode'=>$mem_no]);
            if($member){
                //更新用户的余额
                $db->updateMoney("dec",['uid'=>$member['uid']],$money);

                //添加消费记录
                $MemberMoney=new MemberMoney();
                $MemberMoney->uid=$member['uid'];
                $MemberMoney->flowno=$flowno;
                $MemberMoney->type=1;
                $MemberMoney->money=$money;
                $MemberMoney->add_date=time();
                $MemberMoney->add($MemberMoney);

                $res=[];
                $res['code']=1;
            }

        } catch ( \Exception $ex ) {
            $message=$this->getVars("方法:修改会员余额(deduction)");
            write_log ( "访问方法:修改会员余额(deduction),错误信息:" . $ex.$message, "api/member/deduction" );
            $res = "";
        }

        return $this->ajaxReturn($res);
    }
    // 添加会员积分
    public function addscore() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $mem_no=trim($_POST['mem_no']);//会员编号
                $score=floatval($_POST['score']);//实际销售金额（折后金额）
                $flowno=trim($_POST['ordername']);//订单流水号
                $branch_no=trim($_POST['branch_no']);//门店编号
                $memo=trim($_POST['else']);//备注
                $flows=json_decode($_POST['payflow'],true);
                $db = new MemberDb ();
                $member=$db->getWhere(['ucode'=>$mem_no]);
                if($member){
                    //查询订单的支付方式，现金/微信/等支付方式可获得积分
                    $realPayment=consume_payment();

                    $money=0;
                    if($flows&&count($flows)>0){
                        foreach($flows as $v){
                            if(in_array($v['coin_type'],$realPayment)){
                                $money+=$v['pay_amount'];
                            }
                        }
                    }

                    //查询当前门店是否有积分方案
                    $Integral=new Integral();
                    $now=time();
                    $where="branch_no='$branch_no' and start_date <= $now and end_date >= $now";
                    $plan=$Integral->getWhere($where);
                    $times=1;
                    if($plan&&$plan['rate']>0){
                        $times=floatval($plan['rate']);
                    }

                    $total_credit=$times*$money;
                    //更新用户的积分
                    $db->updateCredit("inc",['uid'=>$member['uid']],$total_credit);
                    //添加积分记录
                    $IntegralMember=new IntegralMember();
                    $IntegralMember->uid=$member['uid'];
                    $IntegralMember->flowno=$flowno;
                    $IntegralMember->credit=$total_credit;
                    $IntegralMember->add_date=$now;
                    $IntegralMember->memo=$memo;
                    $IntegralMember->add($IntegralMember);

                    $res=[];
                    $res['code']=1;
                    $res['credit']=$total_credit;
                }

            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:添加会员积分记录(addscore)");
                write_log ( "访问方法:添加会员积分记录(addscore),错误信息:" . $ex.$message, "api/member/addscore" );
                $res = "";
            }
        }

        return $this->ajaxReturn($res);
    }

    // 获取会员信息
    public function get_mem_info() {
        $res = $this->ApiConnect ( $_POST );
        if ($res == 1) {
            try {
                $mem_no=$_POST['mem_no'];
                $member = new MemberDb ();
                $res = $member->getOne ($mem_no);
                $res['code']="1";
            } catch ( \Exception $ex ) {
                $message=$this->getVars("方法:获取会员信息(get_mem_info)");
                write_log ( "访问方法:获取会员信息(get_mem_info)误,错误信息:" . $ex.$message, "api/member/get_mem_info" );
                $res = "";
            }
        }

        return $this->ajaxReturn($res);
    }


    public function get_consumer_vercode() {
        return $this->ajaxReturn("");
    }

    public function check_member_istoken() {
        return $this->ajaxReturn("");
    }

    public function login() {
        return $this->ajaxReturn("");
    }

    public function passwordchange() {
        return $this->ajaxReturn("");
    }

    public function password_reset() {
        return $this->ajaxReturn("");
    }

    //返还用户全场折扣优惠
    public function memb_recharge_order_add() {

        $res['code']=1;
        return $this->ajaxReturn($res);
    }

    public function memb_recharge_order_finish() {
        $res=[];
        $res['code']=1;
        return $this->ajaxReturn($res);
    }

}
