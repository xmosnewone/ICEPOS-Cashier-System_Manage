<?php
//wx_item_info表
namespace model;
use think\Db;

class WxItemInfo extends BaseModel {

	protected $pk='item_no';
	protected $name="wx_item_info";
    
    public function updatestcok($item_no,$num){
        $model=$this->where("item_no='$item_no'")->find();
        $model->num=$num;
        $model->save();
    }
}
