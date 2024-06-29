<?php
/**
 * bd_supcust_item表
 */
namespace model;
use think\Db;

class BdSupcustItem extends BaseModel{

	protected $pk='item_no';
	protected $name="bd_supcust_item";
    
    public function UpdateLastPriceForPI($supcust_no, $item_no, $price) {
        $result = 0;
        try {
            $model = $this->where("supcust_no='$supcust_no' and item_no='$item_no'")->find();
            if (empty($model)) {
                $model = new BdSupcustItem();
                $model->supcust_no = $supcust_no;
                $model->item_no = $item_no;
                $model->branch_no = "000001";
                $model->top_price = $price;
                $model->last_price = $price;
                $model->contract_date = date(DATETIME_FORMAT);
                $model->appointed_price = $price;
                $model->bottom_price = $price;
            } else {
                $model->last_price = $price;
                $model->contract_date = date(DATETIME_FORMAT);
            }
            if ($model->save()) {
                $result = 1;
            }
        } catch (\Exception $ex) {
            $result = -2;
        }
        return $result;
    }

}
