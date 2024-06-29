<?php
//member_money 会员余额变动表
namespace model;
use think\Db;

class MemberMoney extends BaseModel {

	protected $pk='id';
	protected $name="member_money";

    public function add($content){
        if ($content->save()) {
            return "OK";
        } else {
            return "ERROR";
        }
    }

    //获取单记录
    public function Get($id) {
        try {
            return $this->where("id='$id'")->find();
        } catch (\Exception $ex) {
            return null;
        }
    }

    //删除记录
    public function Del($id) {
        $res = 1;
        try {
            $model = $this->Get($id);
            if ($model == null) {
                $res = -1;
            } else {
                if ($model->delete()) {
                    $res = 1;
                } else {
                    $res = 0;
                }
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }


}
