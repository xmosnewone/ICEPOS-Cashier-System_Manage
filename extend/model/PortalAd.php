<?php
//portal_ad表
namespace model;
use model\PortalAdAttr;
use think\Db;

class PortalAd extends BaseModel {

	protected $pk='ad_id';
	protected $name="portal_ad";
	
    public function GetAdById($adId) {
        $table=$this->table;
        $adAttrTableModel=new PortalAdAttr();
        $adAttrTable=$adAttrTableModel->tableName();
        $now=time();
        $sql = "SELECT a.ad_id, a.ad_name, a.category, a.ad_code,a.start_time,a.end_time, a.is_enabled,t.attr_key,t.attr_value" 
        		. " FROM " . $table . " AS a"
        		. " LEFT JOIN " .$adAttrTable. " AS t ON a.ad_id=t.ad_id"
        		. " WHERE a.is_enabled=1 AND a.start_time <= $now AND a.end_time >= $now AND a.ad_id=" . $adId;
        
        $list=Db::query($sql);
        return $list;
    }
    
    //根据广告位获取所有的广告和属性
    public function GetAdBySpaceId($spaceId) {
    	$table=$this->table;
    	$adAttrTableModel=new PortalAdAttr();
    	$adAttrTable=$adAttrTableModel->tableName();
    	$now=time();
    	$sql = "SELECT a.ad_id, a.ad_name, a.category, a.ad_code,a.start_time,a.end_time, a.is_enabled,t.attr_key,t.attr_value"
    			. " FROM " . $table . " AS a"
    			. " LEFT JOIN " .$adAttrTable. " AS t ON a.ad_id=t.ad_id"
    			. " WHERE a.ad_space_id=" . $spaceId;
    
    	$list=Db::query($sql);
    	return $list;
    }
    
    public function Add($model, $attrAry) {
    	$isCommit = false;
    	Db::startTrans();
    	try {
    		if ($model->save()) {

    		    if($model->category!='image'){
                    $PortalAdAttr=new PortalAdAttr();
                    $adAttr = $PortalAdAttr->GetAdAttrByAdid($model->ad_id);

                    if (!empty($adAttr)&&count($adAttr)>0) {//删除旧有数据
                        if (!$PortalAdAttr->Del($model->ad_id)) {
                            Db::rollback();
                            return FALSE;
                        }
                    }
                }


    			if (empty($model->ad_code)&&$model->category!='image') {
    				foreach ($attrAry as $k => $v) {
    					$attr = new PortalAdAttr();
    					$attr->ad_id = $model->ad_id;
    					$attr->attr_key = $k;
    					$attr->attr_value = $v;
    					if ($attr->Add($attr)) {
    						$isCommit = TRUE;
    					} else {
    						Db::rollback();
    						return FALSE;
    					}
    				}
    			} else {
    				$isCommit = TRUE;
    			}
    		} else {
    			Db::rollback();
    			return FALSE;
    		}
    		if ($isCommit) {
    			Db::commit();
    			return $model->ad_id;
    		}
    	} catch (\Exception $ex) {
    		Db::rollback();
    		return FALSE;
    	}
    }
    
    
    public function Del($adId) {
    	$isCommit = false;
    	Db::startTrans();
    	try {
    		$delNum=$this->where("ad_id = '{$adId}'")->delete();
    		if ($delNum > 0) {
    			$PortalAdAttr=new PortalAdAttr();
    			$adAttr = $PortalAdAttr->GetAdAttrByAdid($adId);
    
    			if (!empty($adAttr)) {
    				if (!$PortalAdAttr->Del($adId)) {
    					Db::rollback();
    					return FALSE;
    				}
    			}
    			$isCommit = TRUE;
    		} else {
    			return FALSE;
    		}
    		if ($isCommit) {
    			Db::commit();
    			return TRUE;
    		}
    	} catch (\Exception $e) {
    		Db::rollback();
    		return FALSE;
    	}
    }
    
    //更新广告
    public function updateAd($model) {
    	try{
    		
    		if($model->save()){
    			return true;
    		}else{
    			return false;
    		}
    		
    	}catch (\Exception $e) {
    		return FALSE;
    	}
    }
    
    public function GetAllAd() {
    	return $this->select();
    }
    
    
    public function GetOneById($adId) {
    	return $this->where("ad_id='$adId'")->find();
    }

    public function GetAdForPos($branch_no, $space_id='') {
        $now=date('Y-m-d H:i:s',time());
        $where="(branch_no = '$branch_no' or branch_no='ALL')";
        if(!empty($space_id)){
            $where.=" and ad_space_id='$space_id'";
        }
        $model = $this->where("$where and start_time < '$now' and end_time > '$now' and is_enabled='1'")->find();
        $return=array();
        if (!empty($model)) {
            $return['category']=$model->category;
            $return['ad_id'] = $model->ad_id;
            $return['ad_code'] = $model->ad_code;
        }

        return $return;
    }

}
