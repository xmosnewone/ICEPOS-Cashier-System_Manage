<?php
namespace app\admin\controller\portal;
use app\admin\controller\Super;
use app\admin\controller\portal\Jsondata;
use model\PortalAd;
use model\PortalAdSpace;
use model\PortalAdAttr;
use think\Db;

class Ad extends Super{

	//广告列表
    public function index() {
    	$PortalAdSpace=new PortalAdSpace();
        $this->assign("space", $PortalAdSpace->GetEnabledAdSpace());
        return $this->fetch("portal/ad/index");
    }

	//编辑广告
    public function view() {

       	 	$adId = input("id");
       	 	
       	 	$PortalAdSpace=new PortalAdSpace();
       	 	$this->assign("space", $PortalAdSpace->GetEnabledAdSpace());
       	 	$this->assign("category", $this->GetCategory());
       	 	
           	if (!empty($adId)) {
	                $PortalAd=new PortalAd();
	                $ad = $PortalAd->GetOneById($adId);
	                $this->assign("one", $ad);
	                $PortalAdAttr=new PortalAdAttr();
	                $attr = $PortalAdAttr->GetAdAttrByAdid($adId);
	                $attrAry = array();
	                if($ad['category']=='image'){
                        foreach ($attr as $k => $v) {
                            $attrAry[$v['pid']][$v['attr_key']] = $v['attr_value'];//二维数组
                        }
                    }else{
                        foreach ($attr as $k => $v) {
                            $attrAry[$v['attr_key']] = $v['attr_value'];//二维数组
                        }
                    }
	                $this->assign("attr", $attrAry);
             }
            
            return $this->fetch("portal/ad/view");
    }

	//保存广告
    public function save() {
        $adId = input("txtAdno");
        $adName = input("txtAdName");
        $spaceno = input("sltSpace");
        $isenabled = input("isEnabled")==1?1:0;
        $startTime = input("txtStartTime");
        $endTime = input("txtEndTime");
        $clickCount = input("txtClickCount");
        $category = input("sltType");
        $option = input("option");
        $branch_no=input("branch_no");
        if(strtoupper($branch_no)=='ALL'){
            $branch_no='ALL';
        }
        if (empty($adName)) {
            return array("code" => false, "msg" => lang("ad_name_empty"));
        }
        if (empty($spaceno)) {
            return array("code" => false, "msg" =>lang("ad_space_empty"));
        }
        if (empty($category)) {
            return array("code" => false, "msg" => lang("ad_no_category"));
        }
        
        $isOption = array("add", "update");

        $attrAry = array();
        $adCode = "";
        $attrval=[];
        switch ($category) {
            case "image":
                $inputs = input('');
                foreach($inputs as $k=>$value){
                        if(strpos($k,'attrval')!==false){
                            $attrval[]=$value;
                        }
                }
                if (empty($attrval)||count($attrval)<=0) {
                    return array("code" => false, "msg" =>lang("ad_image_empty"));
                }
                //更新
                break;
            case "text":
                $attrTextTitle = input("attr_text_title");
                $attrTextLink = input("attr_text_link");
                $attrTextFont = input("attr_text_font");
                $attrTextTarget = input("attr_text_target");
                $attrTextColor = input("attr_text_color");
                if (empty($attrTextTitle)) {
                    return array("code" => false, "msg" =>lang("ad_text_empty"));
                }
                $attrAry["attr_text_title"] = $attrTextTitle;
                $attrAry["attr_text_link"] = $attrTextLink;
                $attrAry["attr_text_color"] = $attrTextColor;
                $attrAry["attr_text_font"] = $attrTextFont;
                $attrAry["attr_text_target"] = $attrTextTarget;
                break;
            case "code":
                $adCode = input("code");
                if (empty($adCode)) {
                    return array("code" => false, "msg" =>lang("ad_code_empty"));
                }
                break;
        }

        if (empty($adId)) {
            $ad = new PortalAd();
            $ad->branch_no = $branch_no;
            $ad->ad_name = $adName;
            $ad->ad_space_id = $spaceno;
            $ad->category = $category;
            $ad->click_count = $clickCount;
            $ad->start_time = $startTime;
            $ad->ad_code = $adCode;
            $ad->end_time = $endTime;
            $ad->is_enabled = $isenabled;

            if ($ad_id=$ad->Add($ad, $attrAry)) {
                if(count($attrval)>0&&$category=='image'){
                    foreach($attrval as $v){
                        $PaDb = new PortalAdAttr();
                        $PaDb->save([
                            'ad_id'  => $ad_id
                        ],['pid'=>$v]);
                    }
                }

            	return array("code" => true, "msg" => lang("save_ad_success"));
            } else {
                return array("code" => false, "msg" =>lang("save_ad_error"));
            }
        }
        else{
           	$PortalAd=new PortalAd();
            $ad = $PortalAd->GetOneById($adId);
            if (empty($ad)) {
                return array("code" => false, "msg" => lang("ad_not_exists"));
            }
            $ad->ad_name = $adName;
            $ad->branch_no = $branch_no;
            $ad->ad_space_id = $spaceno;
            $ad->category = $category;
            $ad->click_count = $clickCount;
            $ad->start_time = $startTime;
            $ad->ad_code = $adCode;
            $ad->end_time = $endTime;
            $ad->is_enabled = $isenabled;
            if ($ad->Add($ad, $attrAry)) {
                if(count($attrval)>0&&$category=='image'){
                    foreach($attrval as $v){
                        $PaDb = new PortalAdAttr();
                        $PaDb->save([
                            'ad_id'  => $ad->ad_id
                        ],['pid'=>$v]);
                    }
                }
                return array("code" => true, "msg" => lang("save_ad_success"));
            } else {
                return array("code" => false, "msg" => lang("save_ad_error"));
            }
        }
    }
    
    //是否显示广告
    public function updateEnable() {
    
    	$id = input("id");
    	$is_enabled=is_numeric(input("is_enabled")) ? input("is_enabled") : 0;
    	if (empty($id)) {
    		return array("code" => false, "msg" =>lang("ad_id_empty"));
    	}
    
    	$PortalAd=new PortalAd();
    	$ad=$PortalAd->GetOneById($id);
    	$ad->is_enabled=$is_enabled;
    	$flag=$PortalAd->updateAd($ad);
    	if ($flag) {
    		return array("code" => true, "msg" => lang("save_data_success"));
    	} else {
    		return array("code" => false, "msg" => lang("save_data_error"));
    	}
    }

    //删除广告
    public function delete() {
    	
        $adId = input("id");

        if (empty($adId)) {
           return array("code" => "-1", "msg" => lang("ad_id_empty"));
        }
        
        $PortalAd=new PortalAd();
        $ad = $PortalAd->GetOneById($adId);

        if (empty($ad)) {
            return array("code" => "-1", "msg" => lang("ad_not_exists"));
        }
        if ($ad->Del($adId)) {
            return array("code" => "1", "msg" => lang("ad_delete_success"));
        } else {
            return array("code" => "-1", "msg" => lang("ad_delete_error"));
        }
    }
    
    //批量删除广告
    public function batchDelete() {
    	 
    	$adId = input("id");
    
    	if (empty($adId)) {
    		return array("code" => false, "msg" => lang("ad_id_empty"));
    	}
    
    	$adIds=explode(",",$adId);
    	
    	$PortalAd=new PortalAd();
    	foreach($adIds as $id){
    		
    		$ad = $PortalAd->GetOneById($id);
    		if (empty($ad)) {
    			continue;
    		}
    		$ad->Del($id);
    	}
    
    	return array("code" => true, "msg" => lang("ad_delete_success"));
    	
    }

	//上传图片
    public function uploadImages() {
        //上传图片
        $result=$this->uploadImage("file",[]);
        //自动压缩图片
        //$this->compressImage($result['path']);
        //获取图片宽高等信息
        list($width, $height, $type, $attr) = getimagesize($result['path']);
        $result['width']=$width;
        $result['height']=$height;
        $result['path']=substr($result['path'], 1);

        //直接新建图片记录
        $attrAry=[];
        $attrAry["attr_image_url"] = $result['path'];
        $attrAry["attr_image_width"] = $width;
        $attrAry["attr_image_height"] = $height;
        $attrAry["attr_image_title"] = $result['filename'];

        $pid=0;
        foreach($attrAry as $key=>$value){
            $PortalAdAttr=new PortalAdAttr();
            $PortalAdAttr->attr_key=$key;
            $PortalAdAttr->attr_value=$value;
            $id=$PortalAdAttr->InsertGetId($PortalAdAttr);
            if($key=='attr_image_url'&&$id!==false){
                $pid=$id;
            }
            if($pid){
                $PortalAdAttr->save([
                    'pid'  => $pid
                ],['id' => $id]);
            }
        }
        $result['pid']=$pid;
        return array("code" => '0', "msg" =>'success','data'=>$result);
        
    }

    //删除上传图片
    public function delImg(){
        $pid=intval(input("pid"));
        $PortalAdAttr=new PortalAdAttr();
        $list=$PortalAdAttr->GetAdAttrByPid($pid);
        if(!$pid||empty($list)){
            return array("code" => '0','msg'=>'缺少pid');
        }

        foreach($list as $key=>$value){
            if($value['attr_key']=='attr_image_url'){
                @unlink(".".$value['attr_value']);//删除图片
            }
        }
        $ok=$PortalAdAttr->DelByPid($pid);
        $code=$ok?'1':'0';
        return array("code" => $code, "msg" =>'删除成功');
    }

    //广告类型
    private function GetCategory() {
        return array(
            "image" => array("value" => "图片", "checked" => "checked"),
            "text" => array("value" => "文本", "checked" => ""),
            "code" => array("value" => "代码", "checked" => "")
        );
    }

	//广告列表
    public function getAdList() {
        $approve = input("approve");
        $category = input("category");
        $endTime = input("endTime");
        $space = input("sltSpace");
        $startTime = input("startTime");
        $title = input("title");
        $page = input('page') ? intval(input('page')) : 1;
        $rows = input('limit') ? intval(input('limit')) : 10;
        $cateAry = $this->GetCategory();
        
        $PortalAd=new PortalAd();
        $PortalAdSpace=new PortalAdSpace();
        $countSql='SELECT count(*) as total';
        $fieldSql="SELECT  a.ad_id, s.ad_space_name, a.ad_name, a.category, a.start_time, a.end_time, a.is_enabled";
        $sql =  " FROM " . $PortalAd->tableName() . " AS a "
                . " LEFT JOIN " . $PortalAdSpace->tableName() . " AS s ON s.ad_space_id=a.ad_space_id"
                . " WHERE 1=1 ";

        if ($category != "-1" && $category != "") {
            $sql .= " AND a.category='" . $category . "'";
        }
        if ($approve == "-1") {
            $sql .= " AND a.is_enabled=0";
        }
        if ($approve == "1") {
            $sql .= " AND a.is_enabled=1";
        }
        if ($startTime != "") {
            $sql .= " AND date(a.start_time) >= date('" . $startTime . "')";
        }
        if ($endTime != "") {
            $sql .= " AND date(a.end_time) <=  date('" . $endTime . "')";
        }
        if ($space != "-1" && $space != "") {
            $sql .= " AND a.ad_space_id=" . $space;
        }
        if ($title != "") {
            $sql .= " AND a.ad_name like '%" . $title . "%'";
        }
        $sql .= " ORDER BY a.ad_id DESC";

        $result=Db::query($countSql.$sql);
        $total=$result[0]['total'];
        
        $offset = ($page - 1) * $rows;
        $rowIndex = ($page - 1) * $rows + 1;
        $model = Db::query($fieldSql.$sql." limit $offset,$rows");
        
        $ary = array();
        foreach ($model as $k => $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["adId"] = $v["ad_id"];
            $tt["adName"] = $v["ad_name"];
            $tt["adSpaceName"] = $v["ad_space_name"];
            $tt["category"] = $cateAry[$v["category"]]["value"];
            $tt["startTime"] = $v["start_time"];
            $tt["endTime"] = $v["end_time"];
            $tt["isEnabled"] = $v["is_enabled"];

            array_push($ary, $tt);
            $rowIndex++;
        }
        $result["rows"] = $ary;
        return listJson(0,'',$total,$ary);
    }

}
