<?php
//member_credit 会员积分变动表
namespace model;
use model\PosBranch;
use model\Member;
use model\PosPayFlow;
use think\Db;

class MemberCredit extends BaseModel {

	protected $pk='id';
	protected $name="member_credit";

    public function add($content){
        if ($content->save()) {
            return "OK";
        } else {
            return "ERROR";
        }
    }

    //POS端添加积分支付消费记录
    public function addCreditPay($data){

        //查询门店积分兑换现金值
        $branchModel=new PosBranch();
        $branch=$branchModel->getone($data['branch_no']);
        $credit_money=$branch['credit_money'];
        //转换成积分
        $pay_amount=$data['pay_amount'];
        //获得使用积分数
        $credit=$pay_amount*$credit_money;

        //获取会员
        $uid=0;
        $memberCredit=0;
        if(!empty($data['ucode'])){
            $memberModel=new Member();
            $member=$memberModel->getWhere(['ucode'=>$data['ucode']]);
            $uid=$member['uid'];
            $memberCredit=$member['credit'];
        }

        if($memberCredit<$credit){
            return false;
        }

        $content=new MemberCredit();
        $content->ucode=$data['ucode'];
        $content->uid=$uid;
        $content->flow_no=$data['flow_no'];//订单流水号
        $content->branch_no=$data['branch_no'];
        $content->pos_id=$data['pos_id'];
        $content->credit=$credit;
        $content->action="-";
        $content->memo="积分抵扣[".$pay_amount."]元"."使用[".$credit."]积分";
        $content->add_date=time();

        if ($content->save()) {
            $newCredit=$memberCredit-$credit;
            $memberModel=new Member();
            $memberModel->updateData(['uid'=>$uid],["credit"=>$newCredit]);
            return true;
        } else {
            return false;
        }
    }

    //退还收银流水里面积分支付使用的会员积分
    //$pay_id  是pos_payflow表的id字段值
    public function backCreditOrder($pay_id,$memo){
        $payFlowModel=new PosPayFlow();
        $payflow=$payFlowModel->where(['id'=>$pay_id])->find();
        if($payflow==null||empty($payflow)){
            return  false;
        }

        $flow_no=$payflow['flow_no'];
        $memberCredit=$this->where(["flow_no"=>$flow_no])->find();
        if($memberCredit==null||empty($memberCredit)){
            return  false;
        }
        unset($memberCredit['id']);
        $memberCredit['action']="+";
        $memberCredit['add_date']=time();
        $memberCredit['memo']=$memo."  | 收银流水退还积分[".$memberCredit['credit']."]";

        //增加会员积分
        $memberModel=new Member();
        $member=$memberModel->where(['uid'=>$memberCredit['uid']])->find();
        if($member==null||empty($member)){
            return  false;
        }

        $credit=$member->credit+$memberCredit['credit'];//返回积分
        $ok=$member->updateData(['uid'=>$member['uid']],['credit'=>$credit]);
        if($ok){
            $mc=new MemberCredit();
            return $mc->save($memberCredit);
        }
        return false;
    }

}
