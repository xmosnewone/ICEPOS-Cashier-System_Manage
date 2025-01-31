<?php
//member表
namespace model;
use think\Db;
use model\MemberLevel;
class Member extends BaseModel{

	protected $pk='uid';
	protected $name="member";
	
    public function getall($con=[]){

       $pagesize = 30;
       $order='uid desc';
       
       $list=Db::name($this->name)
       		->where($con)
       		->order($order)
       		->paginate($pagesize);
       
       $page=$list->render();
       
       $return['result']=$list;
       $return['pages']=$page;
       return $return;
    }
    
    public function add($content){
       if ($content->save()) {
                return "OK";
        } 
        else {
                return "ERROR";
        }
    }

    //更新会员余额
    public function updateMoney($action='inc',$where,$money){
        if($action=='inc'){
            return Db::name($this->name)->where($where)->setInc('account',$money);
        }else{
            return Db::name($this->name)->where($where)->setDec('account',$money);
        }
    }

    //更新积分
    public function updateCredit($action='inc',$where,$credit){
        if($action=='inc'){
            return Db::name($this->name)->where($where)->setInc('credit',$credit);
        }else{
            return Db::name($this->name)->where($where)->setDec('credit',$credit);
        }
    }

    //更新消费金额
    public function updateConsum($action='inc',$where,$amount){
        if($action=='inc'){
            return Db::name($this->name)->where($where)->setInc('total_consu',$amount);
        }else{
            return Db::name($this->name)->where($where)->setDec('total_consu',$amount);
        }
    }

    //更新数据
    public function updateData($where,$data){
       return Db::name($this->name)->where($where)->update($data);
    }

    //条件返回会员信息
    public function getWhere($where){
        $one=Db::name($this->name)->where($where)->find();
        return $one?$one:false;
    }

    //用于Member 会员API 返回会员信息
    public function getOne($keyword){
        $one=Db::name($this->name)->where('ucode|mobile','=',"$keyword")->find();
        if ($one&&is_array($one)) {
            $level=Db::name("member_level")->where("lid='{$one['level']}'")->find();
            if($level){
                $one['levelname']=$level['levelname'];
            }
            return $this->formatMember($one);
        } else {
            return '';
        }
    }

    private $formater=[
        'mobile'=>'mobile',
        'email'=>'email',
        'mem_no'=>'ucode',
        'mem_pass'=>'passwd',
        'mem_id'=>'uid',
        'mem_type'=>'utype',
        'username'=>'uname',
        'nickname'=>'nickname',
        'balance'=>'account',//余额
        'total_consu'=>'total_consu',//消费金额
        'score'=>'credit',//积分
        'Level'=>'level',//会员等级（注意大小写）
        'levelname'=>'levelname',//会员等级（注意大小写）
    ];
    //根据POS端会员字段格式化
    //@$meminfo 数据库查询数组
    private function formatMember($meminfo){
        $data=[];
        foreach($this->formater as $key=>$field){
            if(isset($meminfo[$field])&&!empty($meminfo[$field])){
                $data[$key]=trim($meminfo[$field]);
            }
        }
        return $data;
    }
}
