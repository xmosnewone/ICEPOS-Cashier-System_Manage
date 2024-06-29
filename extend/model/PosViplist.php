<?php
//pos_viplist表
namespace model;
use think\Db;

class PosViplist extends BaseModel {

	protected $pk='id';
	protected $name="pos_viplist";

    public function AddModelsForPos($model) {
        $res = $this->CheckModel($model);
        if ($res == 1) {
            try {
                $model1 = $this->where("flow_no='{$model->flow_no}' and card_no='{$model->card_no}' and oper_date='{$model->oper_date}'")->find();
                if (empty($model1)) {
                    if ($model->save()) {
                        $res = 1;
                    } else {
                        $res = 0;
                    }
                } else {
                    $res = 0;
                }
            } catch (\Exception $ex) {
                $res = -2;
            }
        } else {
            return $res;
        }
        return $res;
    }


    private function CheckModel($model) {
        $res = 1;
        try {
            if (empty($model)) {
                $res = 0;
            } else if (empty($model->flow_no)) {
                $res = 0;
            } else if (empty($model->card_no)) {
                $res = 0;
            } else if (!is_numeric($model->score)) {
                $res = 0;
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }

}
