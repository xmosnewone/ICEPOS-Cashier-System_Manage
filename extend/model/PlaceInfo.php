<?php
//bd_place_info表
namespace model;
use think\Db;

class PlaceInfo extends BaseModel {

	protected $pk='place_no';
	protected $name="bd_place_info";

    public function GetPlaceInfo($branchorplace) {
    	$condition=[];
    	if(!empty($branchorplace)){
    		$condition="branch_no='$branchorplace' or super_place_no='$branchorplace'";
    	}
        return $this->where($condition)->select();
    }

    public function IsExistsPlace($placeno) {
        $model = $this->where("place_no='$placeno'")->find();
        if(!$model){
            return false;
        }
        else
        { 
            return true;
        }
    }
    
    public function GetPlaceInfoByNo($placeno) {
    	return $this->where("place_no='$placeno'")->find();
    }

    public function GetSuperPlace($superPlaceno) {
        return $this->where("super_place_no='$superPlaceno'")->select();
    }

    public function GetPlaceOrBranchByOnce($branchOrPlace) {
        return $this->where("branch_no='$branchOrPlace' or place_no='$branchOrPlace'")->find();
    }
    
    public function Add($model) {
        if ($model->save()) {
            return true;
        } else {
            return false;
        }
    }

    public function Del($placeno) {
        
        try {
        	$delNum=$this->where("place_no='$placeno'")->delete();
            if ($delNum> 0) {
                return TRUE;
            } else {
                return FALSE;
            }
        } catch (\Exception $ex) {
            return FALSE;
        }
    }

}
