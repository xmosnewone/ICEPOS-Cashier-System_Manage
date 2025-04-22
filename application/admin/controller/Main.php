<?php
namespace app\admin\controller;
use model\PosStatus;
use model\PosSaleFlow;
use model\SysManager;
use model\PosBranch;
use model\BaseModel;

class Main extends Super {
    
    //数据统计
    public function data(){
    	
    	/*
    	 * 获取POS机终端安装数量/门店数量
    	 */
    	$model = new PosStatus();
    	$count = $model->count();
    	$this->assign("posnum",$count);
    	/*
    	 * 获取POS机终端安装数量/门店数量
    	 */
    	
    	/*
    	 * 获取门店库存预警商品信息
    	 */
    	/*
    	$prefix=$this->dbprefix;
    	$sql = "select f.branch_no, f.branch_name,s.item_no,i.item_name,i.item_brand,i.purchase_spec,item_rem,i.item_brandname,i.price,i.item_clsno,c.item_clsname,i.sale_price,i.unit_no,i.item_size,b.stock_qty,p.sp_company,b.max_qty,b.min_qty from "
    			.$prefix."im_stock_target" . " as s "
    			. "left join " .$prefix."pos_branch_stock" . " as b on b.item_no=s.item_no and b.branch_no=s.branch_no "
    			. "left join " .$prefix."bd_item_info" . " as i on s.item_no=i.item_no "
    			. "left join " .$prefix."bd_item_cls" . " as c on i.item_clsno=c.item_clsno "
    			. "left join " .$prefix."pos_branch_info" . " as f on f.branch_no=b.branch_no "
    			. "left join " .$prefix."sp_infos" . " as p on i.main_supcust=p.sp_no where b.stock_qty <= b.min_qty";
	
    	$list = Db::query($sql);
    	if($list!==false){
    		$warning = count($list);
    	}else{
    		$warning = 0;
    	}
    	$this->assign("warning",$warning);
    	*/
    	/*
    	 * 获取门店库存预警商品信息
    	 */
    	
    	/*
    	 *获取留言信息数量 
    	 */
    	//$guestbook =M("portal_guestbook")->count();
    	//$this->assign("guestbook",$guestbook);
    	/*
    	 *获取留言信息数量
    	 */
    	
    	/*
    	 *销售流水
    	 */
    	$saleamount = M("pos_payflow")->sum("sale_amount");
    	$this->assign("saleamount",$saleamount);
    	/*
    	 *销售流水
    	 */
    	
    	/*
    	 *收银金额
    	 */
    	$payamount =M("pos_payflow")->sum("pay_amount");
    	$this->assign("payamount",$payamount);
    	/*
    	 *收银金额
    	 */
    	
    	/*
    	 *物料数量 
    	 */
    	$iteminfo = M("bd_item_info")->count();
    	$this->assign("iteminfo",$iteminfo);
    	
    	/**
    	 * 单品排行
    	 */
    	$goods=$this->goodRange();
    	$this->assign("goods",$goods['rows']);
    	
    	/**
    	 * 销售业绩趋势
    	 */
    	$sales=$this->salesStatic();
    	$salesMonth=simplode($sales['month']);
    	$salesData=simplode($sales['data']);
    	$this->assign("salesMonth",$salesMonth);
    	$this->assign("salesData",$salesData);
    	
    	return $this->fetch();
    }
    
    //单品销售排行
    public function goodRange(){
    	
    	$start = '';
    	$end = '';
    	$branch_no = '';
    	$item_no ='';
    	$item_clsno = '';
    	$supcust_no = '';
    	$item_subno ='';
    	$item_brand ='';
    	$summary_type =4;
    	$page = 1;
    	$rows = 7;
    	
    	$model=new PosSaleFlow();
    	return $model->Summary($start, $end, $branch_no, $item_no, $item_clsno, $supcust_no, $item_subno, $item_brand, $summary_type, $page, $rows,'',"sale_money desc");
    	
    }
    
    /**
     * 获取某月的最后一天
     */
    private function getMonthLastDay($year, $month){
    	$t = mktime(0, 0, 0, $month + 1, 1, $year);
    	$t = $t - 60 * 60 * 24;
    	return $t;
    }
    
    //销售业绩统计
    public function salesStatic(){
    	
    	$return=array();
    	//数据表前缀
    	$prefix=$this->dbprefix;
    	 
    	//获得当前月份
    	$now=time();
    	$year=date("Y",$now);
    	$month=date("m",$now);
    	$day=date("d",$now);
    	 
    	//$monthLable=array(1=>'一月',2=>'二月',3=>'三月',4=>'四月',5=>'五月',6=>'六月',7=>'七月',8=>'八月',9=>'九月',10=>'十月',11=>'十一月',12=>'十二月');
    	 
    	for($i=5;$i>=0;$i--){
    
    				$countDown=$i>0?'-'.$i:$i;
    				$year_ago=strtotime($countDown." month",$now);
    				$y_a_year=date('Y',$year_ago);
    				$y_a_month=date('m',$year_ago);
    				$y_a_monthn=date('n',$year_ago);
    				$y_a_day=date('d',$year_ago);
    				
    				$return['month'][]=$y_a_year."-".$y_a_month;
    				
    				$time_stamp_start=mktime(0,0,0,$y_a_month,1,$y_a_year);
    				$time_stamp_start=date('Y-m-d',$time_stamp_start);
    				$month_last_day=date("d",$this->getMonthLastDay($y_a_year,$y_a_month));//计算每个月最后一日
    				$time_stamp_end=mktime(23,59,59,$y_a_month,$month_last_day,$y_a_year);
    				$time_stamp_end=date('Y-m-d',$time_stamp_end);
   
    				$amount=M("pos_saleflow")
    						->where("oper_date>='$time_stamp_start' and oper_date<='$time_stamp_end'")
    						->sum("sale_money");
    				$return['data'][]=$amount?$amount:0;
    	}
    	//输出JSON
    	return $return;
    }
    
    //获取会员和pos同步记录信息
    public function syncData(){
    	$member=$this->members();
    	$pos=$this->posSync();
    	
    	return ['member'=>$member?$member:'null','pos'=>$pos?$pos:'null'];
    }

    //获取当天内注册会员信息
    public function members(){
        $today=timezone_get(2);
        $start=$today['begin'];
        $end=$today['end'];
        $members=M("member")
            ->alias("a")
            ->where("a.addtime>=$start and a.addtime<$end")
            ->join("pos_branch_info b","b.branch_no=a.branch_no","LEFT")
            ->field("a.uname,a.nickname,a.phone,a.addtime,a.utype,b.branch_name")
            ->limit(20)
            ->order("addtime desc")
            ->select();

        if($members){
            foreach($members as $key=>$value){
                $members[$key]['branch_name']=!empty($value['branch_name'])?$value['branch_name']:$value['utype'];
                $members[$key]['addtime']=date('Y-m-d H:i:s',$value['addtime']);
            }
        }
        return $members;
    }
    
    
    //POS终端同步记录
    public function posSync(){

        $today=timezone_get(2);
        $start=$today['begin'];
        $end=$today['end'];
        //读取前10记录
        $pos=M("pos_sync")
            ->alias("a")
            ->where("a.synctime>=$start and a.synctime<$end and a.branch_no!=''")
            ->join("pos_branch_info b","b.branch_no=a.branch_no","LEFT")
            ->field("a.*,b.branch_name")
            ->limit(10)
            ->select();

        if($pos){
            foreach($pos as $key=>$value){
                $pos[$key]['synctime']=date('Y-m-d H:i:s',$value['synctime']);
            }
        }

        return $pos;
    }
    
    
    //门店地图
    public function shopMap(){
    	//查询所有的门店信息
    	$posBranch=new PosBranch();
    	$list=$posBranch->GetAllBranchField("branch_no,branch_name,baidu_location");
    	$this->assign("baiduak",BAIDU_AKEY);
    	$this->assign("totals",count($list));
    	$str='';
    	foreach($list as $value){
    		if($value['baidu_location']!=''){
    			$latlng=explode(",",$value['baidu_location']);
    			$lng=$latlng[0];
    			$lat=$latlng[1];
    			$str.=" \n lngs.push(".$lng.");";
    			$str.=" \n lats.push(".$lat.");";
    			$str.=" \n titles.push('".$value['branch_name']."');";
    		}
    	}
    	$this->assign("mapstr",$str);
    	return $this->fetch('main/shopmap');
    }
    
    //全景展示
    public function shopPanorama(){
    	
    	$lng=input('lng');
    	$lat=input('lat');
    	$title=input('title');
    	
    	$this->assign("lng",$lng);
    	$this->assign("lat",$lat);
    	$this->assign("title",str_replace("<br/>", " ",$title));
    	$this->assign("baiduak",BAIDU_AKEY);
    	
    	return $this->fetch('main/shopmap2');
    }
    
    //修改个人信息
    public function personal(){
    	
    	//查询本人信息
    	$loginname=session("loginname");
    	$SysManager = new SysManager();
    	$one=$SysManager->where("loginname='$loginname'")->find();
    	$this->assign("one",$one);
    	return $this->fetch();
    }
    
    public function updatePersonal(){
    	
    	$loginname=session('loginname');
    	$SysManager=new SysManager();
    	$result = $SysManager->where("loginname='$loginname'")->find();
    	
    	$username=input('username');
    	$mobile=input('mobile');
    	$telephone=input('telephone');
    	$email=input('email');
    	$qq=input('qq');
    	$wechat=input('wechat');
    	$address=input('address');
    	
    	$arr=array(
    			"username"=>$username,
    			"mobile"=>$mobile,
    			"telephone"=>$telephone,
    			"email"=>$email,
    			"qq"=>$qq,
    			"wechat"=>$wechat,
    			"address"=>$address
    	);
    	
    	 
    	if($result->save($arr,['id'=>$result->id])){
    		$r['code']='1';
    	}
    	else{
    		$r['code']='-1';
    		$r['error']='修改失败';
    	}
    	
    	return $r;
    }
    
    //修改密码
    public function passwd(){
    	 
    	return $this->fetch();
    }
    
    //修改密码
    public function updatePassword(){
    	$loginname=session('loginname');
    	$SysManager=new SysManager();
    	$result = $SysManager->where("loginname='$loginname'")->find();
    	
    	$password=input('password');
    	$password2=input('password2');
    	if($password!=$password2){
    		$r['code']='-1';
    		$r['error']='两次输入密码不相同';
    		return $r;
    	}
		//更新密码
    	$arr=array(
    			"password"=>md5($password),
    	);
    	
    	if($result->save($arr,['id'=>$result->id])){
    		$r['code']='1';
    	}
    	else{
    		$r['code']='-1';
    		$r['error']='更新密码失败，请重试';
    	}
    	return $r;
    }
}
