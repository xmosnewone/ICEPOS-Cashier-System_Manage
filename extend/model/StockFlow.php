<?php
//stock_flow表
namespace model;
use think\Model;
use think\Db;

class StockFlow extends BaseModel {
	
	protected $pk='id';
	protected $name="stock_flow";
	
    public function search($condition=array()) {
    	$list=Db::table($this->table)
    	->where($condition)
    	->select();
    }

    public function Add($branch_no, $sheet_no, $db_no, $item_no, $old_qty, $real_qty, $new_qty, $sell_way) {
        $result = 0;
        try {
            $model = new StockFlow();
            $model->branch_no = $branch_no;
            $model->sheet_no = $sheet_no;
            $model->db_no = $db_no;
            $model->sell_way = $sell_way;
            $model->item_no = $item_no;
            $model->new_qty = $new_qty;
            $model->real_qty = $real_qty;
            $model->old_qty = $old_qty;
            $model->oper_date = date(DATETIME_FORMAT);
            if ($model->save()) {
                $result = 1;
            }
        } catch (\Exception $ex) {
        	write_log("库存流水表新增(Add)异常:" . $ex,"StockFlow");
            $result = -2;
        }
        return $result;
    }

}
