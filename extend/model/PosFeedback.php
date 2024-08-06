<?php
//pos_feedback表
namespace model;
use think\Db;

class PosFeedback extends BaseModel {

	protected $pk='id';
	protected $name="pos_feedback";
	
    public function AddModelForPos($model) {
        $res = $this->CheckModel($model);
        if ($res == 1) {
            try {
                $model->oper_date = date(DATETIME_FORMAT,time());
                if ($model->save()) {
                    $res = 1;
                } else {
                    $res = 0;
                }
            } catch (\Exception $ex) {
                $res = -2;
            }
        }
        return $res;
    }


    private function CheckModel($model) {
        $res = 0;
        try {
            if (empty($model)) {
                $res = -1;
            } else {
                if (empty($model->branch_no)) {
                    $res = -10;
                } else if (empty($model->posid)) {
                    $res = -10;
                } else if (empty($model->oper_id)) {
                    $res = -10;
                } else if (empty($model->content)) {
                    $res = -10;
                } else if (getStrlen($model->content) > 50) {
                    $res = -10;
                } else {
                    $res = 1;
                }
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }

    public function GetById($id) {
        return $this->where("id='$id'")->find();
    }
}
