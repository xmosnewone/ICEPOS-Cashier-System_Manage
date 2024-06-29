<?php
//pos_account表
namespace model;
use think\Db;

class PosAccount extends BaseModel {

	protected $pk='flow_id';
	protected $name="pos_account";
	
    public function AddModelsForPos($models) {
        $res = 0;
        $res = $this->CheckModels($models);
        if ($res == 1) {
            Db::startTrans();
            try {
                foreach ($models as $model) {
                    $count = $this
                    		->where("branch_no='{$model->branch_no}' and pos_id='{$model->pos_id}' and oper_id='{$model->oper_id}' and start_time > '{$model->start_time}' and end_time <= '{$model->end_time}'")
                    		->count();
                    if (empty($count)||$count<=0) {
                        if ($model->save() == FALSE) {
                            $res = 0;
                            break;
                        }
                    } else {
                        $res = 0;
                        break;
                    }
                }
                if ($res == 1) {
                    Db::commit();
                } else {
                    Db::rollback();
                }
            } catch (\Exception $ex) {
                $res = -2;
            }
        }
        return $res;
    }


    private function CheckModels($models) {
        $res = 1;
        try {
            if (empty($models)) {
                $res = 0;
            } else {
                foreach ($models as $model) {
                    if (empty($model)) {
                        $res = 0;
                        break;
                    } else if (empty($model->branch_no)) {
                        $res = 0;
                        break;
                    } else if (empty($model->pos_id)) {
                        $res = 0;
                        break;
                    } else if (empty($model->oper_id)) {
                        $res = 0;
                        break;
                    } else if (empty($model->oper_date)) {
                        $res = 0;
                        break;
                    } else if (empty($model->start_time)) {
                        $res = 0;
                        break;
                    } else if (empty($model->end_time)) {
                        $res = 0;
                        break;
                    } else if (!is_numeric($model->sale_amt)) {
                        $res = 0;
                        break;
                    }
                }
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }


    public $branch_name;

    public $oper_name;
    public $rowIndex;

	//已080
    public function GetModelsForList($start, $end, $branch_no, $oper_id, $page, $rows, $mark) {
      
        $where="1=1";
        if (!empty($start) && !empty($end)) {
            if ($start == $end) {
                $where.=" and date(s.oper_date) = '$start'";
            } else {
               // $end = date(DATETIME_FORMAT, strtotime("+1 day", strtotime($end)));
                $where.=" and s.oper_date >= '{$start}' and s.oper_date < '{$end}'";
            }
        }
        if (!empty($branch_no)) {
            $where.=" and s.branch_no='$branch_no'";
        }
        if (!empty($oper_id)) {
            $where.=" and s.oper_id='$oper_id'";
        }

        $rowCount=Db::name($this->name)
        ->alias('s')
    	->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        ->join('pos_operator b','s.oper_id=b.oper_id',"LEFT")
        ->where($where)
        ->count();
        
        if ($mark == "1") {
            $offset = ($page - 1) * $rows;
            $temp=Db::name($this->name)
            ->alias('s')
            ->field("s.pos_id,s.branch_no,s.oper_id,s.oper_date,s.start_time,s.end_time,s.sale_amt,a.branch_name,b.oper_name,s.hand_amt")
            ->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
            ->join('pos_operator b','s.oper_id=b.oper_id',"LEFT")
            ->where($where)
            ->limit($offset,$rows)
            ->order("oper_date desc")
            ->select();
        }else{
        	$temp=Db::name($this->name)
        	->alias('s')
        	->field("s.pos_id,s.branch_no,s.oper_id,s.oper_date,s.start_time,s.end_time,s.sale_amt,a.branch_name,b.oper_name,s.hand_amt")
        	->join('pos_branch_info a','s.branch_no=a.branch_no',"LEFT")
        	->join('pos_operator b','s.oper_id=b.oper_id',"LEFT")
        	->where($where)
        	->order("oper_date desc")
        	->select();
        }

        $result = array();
        $result["total"] = $rowCount;
        $list = array();
        $rowIndex = ($page - 1) * $rows + 1;
        foreach ($temp as $v) {
            $tt = array();
            $tt["rowIndex"] = $rowIndex;
            $tt["branch_no"] = $v["branch_no"];
            $tt["oper_id"] = $v["oper_id"];
            $tt["pos_id"] = $v["pos_id"];
            $tt["oper_date"] = $v["oper_date"];
            $tt["start_time"] = $v["start_time"];
            $tt["end_time"] = $v["end_time"];
            $tt["sale_amt"] = formatMoneyDisplay($v["sale_amt"]);
            $tt["branch_name"] = $v["branch_name"];
            $tt["oper_name"] = $v["oper_name"];
            $tt["hand_amt"] = $v["hand_amt"];
            $rowIndex++;
            array_push($list, $tt);
        }
        $result["rows"] = $list;
        return $result;
    }

}
