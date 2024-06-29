<?php
/**
 * account_info表
 */
namespace model;
use think\Db;

class CapitalInfo extends BaseModel {

	protected $pk='id';
	protected $name="account_info";
	
	public function str_no($str='') {
			return $str.date("YmdHis").rand(0,9);
	}

    public function getall($con=[]){
       $list=Db::table($this->table)->order('id desc')->where($con)->select();
       return $list;
    }
    
    public function add($model){
       if ($model->save()) {
                return true;
        } else {
                return false;
        }
    }

}
