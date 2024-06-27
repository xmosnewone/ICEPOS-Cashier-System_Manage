<?php
namespace app\admin\controller\portal;
use app\admin\controller\Super;
use app\admin\controller\portal\Jsondata;
use model\PortalAd;
use model\PortalAdSpace;
use model\BaseModel;

class Adspace extends Super {

    public function index() {
        return $this->fetch("portal/ad/space");
    }

    //编辑或者添加广告位置
    public function view() {
    	
        $adSpaceid = input("id");
       
        if (!empty($adSpaceid)) {
            	$PortalAdSpace=new PortalAdSpace();
            	$adSpace = $PortalAdSpace->GetAdSpaceById($adSpaceid);
            	if (empty($adSpace)) {
            		return array("code" => "-1", "msg" => lang("space_not_exists"));
            	}
            	$this->assign("one", $adSpace);
        }
       
        return $this->fetch("portal/ad/addspace");
       
    }

	//保存广告位置
    public function save() {
    	
        $spaceid = input("txtSpaceno");	
        $spacename = input("txtSpaceName");
        $description = input("txtDescription");
        $isenabled = input("isEnabled");

        if (empty($spacename)) {
            return array("code" => false, "msg" => lang("space_name_empty"));
        }
		
        //添加
        if (empty($spaceid)) {
	            $adSpace = new PortalAdSpace();
	            $adSpace->ad_space_name = $spacename;
	            $adSpace->is_enabled = $isenabled;
	            $adSpace->description = $description;
	            if ($adSpace->Add($adSpace)) {
	                return array("code" => true, "msg" => lang("save_data_success"));
	            } else {
	                return array("code" => false, "msg" => lang("save_data_error"));
	            }
        }else{
        		//编辑
        		$PortalAdSpace=new PortalAdSpace();
        		$adSpace = $PortalAdSpace->GetAdSpaceById($spaceid);
        		if (empty($adSpace)) {
        			return array("code" => false, "msg" => lang("space_not_exists"));
        		}
        		$adSpace->ad_space_id = $spaceid;
        		$adSpace->ad_space_name = $spacename;
        		$adSpace->description = $description;
        		$adSpace->is_enabled = $isenabled;
        		if ($adSpace->Add($adSpace)) {
        			return array("code" => true, "msg" => lang("save_data_success"));
        		} else {
        			return array("code" => false, "msg" => lang("save_data_error"));
        		}
        	
        }
    }

	//删除广告位置
    public function delete() {
       
        $spaceid = input("spaceid");
        
        if (empty($spaceid)) {
            return array("code" => false, "msg" => lang("spaceid_empty"));
        }
        
        $PortalAdSpace=new PortalAdSpace();
        $adSpace = $PortalAdSpace->GetAdSpaceById($spaceid);
        $PortalAd=new PortalAd();
        $ads=$PortalAd->GetAdBySpaceId($spaceid);
        if(!empty($ads)){
        	return array("code" => false, "msg" => lang("space_ad_exists"));
        }
        
        if (empty($adSpace)) {
            return array("code" => true, "msg" => lang("space_not_exists"));
        }
        if ($adSpace->Del($spaceid)) {
            return array("code" => true, "msg" => lang("space_delete_success"));
        } else {
            return array("code" => false, "msg" => lang("space_delete_error"));
        }
    }
    
    //是否显示栏目
    public function updateEnable() {
    
    	$space_id = input("space_id");
    	$is_enabled=is_numeric(input("is_enabled")) ? input("is_enabled") : 0;
    	if (empty($space_id)) {
    		return array("code" => false, "msg" =>lang("spaceid_empty"));
    	}
    
    	$PortalChannel=new PortalAdSpace();	
		$space=$PortalChannel->GetAdSpaceById($space_id);
		$space->is_enabled=$is_enabled;
		$flag=$PortalChannel->Add($space);
    	if ($flag) {
    		return array("code" => true, "msg" => lang("save_data_success"));
    	} else {
    		return array("code" => false, "msg" => lang("save_data_error"));
    	}
    }

	//分页获取广告位置
    public function getAdSpace() {
        $page = input('page') ? intval(input('page')) : 1;
        $rows = input('limit') ? intval(input('limit')) : 10;
	
        $model = new PortalAdSpace();
        $rowCount=$model->count();
        $offset = ($page - 1) * $rows;
        $field="ad_space_id,ad_space_name,description,is_enabled";
        $list = $model->field($field)->limit($offset,$rows)->select()->toArray();

        $temp = array();
        $rowIndex = $offset + 1;
        foreach ($list as $v) {
            $tt = $v;
            $tt["rowIndex"] = $rowIndex;
            $rowIndex++;
            array_push($temp, $tt);
        }
        return listJson(0,'',$rowCount,$temp);
    }

}
