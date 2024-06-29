<?php
//purchaser_affix表
namespace model;
use think\Db;

class PurchaserAffix extends BaseModel {
    
	protected $pk='invoice_id';
	protected $name="purchaser_affix";

    public function AddData($model){
        try {
            $res = $this->Check($model);
            if ($res == 1) {
                if ($model->save()) {
                    return TRUE;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        } catch (\Exception $ex) {
            return FALSE;
        }
    }

    public function UpdateData($username){
        
    }

    public function DelData(){
        
    }

    public function GetOneDateil($username){
        return $this->where("usernam='$username'")->find();
    }


    private function Check($model) {
        $res = 1;
        if (empty($model->username)) {
            $res = "单据编号不能为空";
        }
        return $res;
    }
}
