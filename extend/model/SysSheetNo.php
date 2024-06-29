<?php
//sys_sheet_no表
namespace model;
use think\Model;

class SysSheetNo extends BaseModel {

	protected $pk='sheet_id';
	protected $name="sys_sheet_no";

    public function CreateSheetNo($sheet_id, $branch_no) {
        $model = $this->where("sheet_id='$sheet_id'")->find();
        if (empty($model->sheet_date)) {
            $model->sheet_date = date('Y-m-d');
            $model->sheet_value = 0;
        } else {
            if ((date('Y-m-d', strtotime($model->sheet_date)) != date('Y-m-d'))) {
                $model->sheet_value = 0;
                $model->sheet_date = date('Y-m-d');
            }
            if (intval($model->sheet_value) > 999) {
                $model->sheet_value = 0;
            }
        }
        $model->sheet_value = intval($model->sheet_value) + 1;
        if ($model->save()) {
            $sheetno = intval($model->sheet_value);
            $day = substr(date('Ymd'), 2);
            $sheet_no = implode(array($sheet_id, $branch_no, $day, str_pad($sheetno, 4, '0', STR_PAD_LEFT)));
        } else {
            return "";
        }
        return $sheet_no;
    }

}
