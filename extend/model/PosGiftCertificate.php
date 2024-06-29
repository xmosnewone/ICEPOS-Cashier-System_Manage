<?php
//pos_gift_certificate表
namespace model;
use think\Db;

class PosGiftCertificate extends BaseModel {

	protected $pk='giftcert_no';
	protected $name="pos_gift_certificate";
	
    public function AddOrUpdate($model, $addOrUpdate) {
        $res = 1;
        try {
            $res = $this->Check($model);
            if ($res == 1) {
                if ($addOrUpdate != "add") {
                    $temp = $this->Get($model->giftcert_no);
                    if ($temp == null) {
                        $res = -1;
                    }
                }
                if ($res == 1) {
                    if ($model->save()) {
                        $res = 1;
                    } else {
                        $res = 0;
                    }
                }
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }


    private function Check($model) {
        $res = 1;
        try {
            if (empty($model->giftcert_no)) {
                $res = -2;
            } else if (empty($model->gift_type)) {
                $res = -3;
            } else if (!is_numeric($model->gift_money)) {
                $res = -4;
            } else if (!$model->oper_date) {
                $res = -5;
            } else if (!$model->status) {
                $res = -6;
            } else if (!$model->begin_date) {
                $res = -7;
            } else if (!$model->end_date) {
                $res = -8;
            } else if (!$model->send_branch) {
                $res = -9;
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }


    public function Get($giftcert_no) {
        try {
            return $this->where("giftcert_no='$giftcert_no'")->find();
        } catch (\Exception $ex) {
            return null;
        }
    }


    public function Del($giftcert_no) {
        $res = 1;
        try {
            $model = $this->Get($giftcert_no);
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
    

    public function SearchGift($start, $end, $gift_no, $oper_id, $status, $page, $rows) {
       
        $where="1=1";
        if (!empty($start) && !empty($end)) {
            if ($start == $end) {
                $where.=" and date(i.oper_date) = date('$start')";
            } else {
                $where.=" and date(i.oper_date) >= date('$start') and date(i.oper_date) <= date('$end')";
            }
        }
        if (!empty($gift_no)) {
            $where.=" and i.giftcert_no like '%$gift_no%'";
        }
        if (!empty($oper_id)) {
            $where.=" and i.oper_id = '$oper_id'";
        }
        if (!empty($status)) {
            $where.=" and i.status = '$status'";
        }
        
        $offset = ($page - 1) * $rows;
        
        $use=lang("gf_can_use");
        $nuse=lang("gf_cant_use");
        $used=lang("gf_is_used");
        
        $res=Db::name($this->name)
        		->alias('i')
        		->field("i.giftcert_no,i.gift_type,i.gift_money,i.oper_id,a.oper_name,i.oper_date,i.send_branch,b.branch_name," .
                "   case i.status when '1'  then '{$use}' when '3' then '{$used}' else '{$nuse}' end as status,i.begin_date,i.end_date")
        		->join('pos_operator a','i.oper_id= a.oper_id',"LEFT")
        		->join('pos_branch_info b','i.send_branch=b.branch_no',"LEFT")
        		->limit($offset,$rows)
        		->where($where)
        		->select();
        
        $count=Db::name($this->name)
        		->alias('i')
        		->join('pos_operator a','i.oper_id= a.oper_id',"LEFT")
        		->join('pos_branch_info b','i.send_branch=b.branch_no',"LEFT")
        		->where($where)
        		->count();
        
        
        $result = array();
        $result["total"] = $count;
        $temp = array();
        foreach ($res as $k => $v) {
            $tt = array();
            $tt["giftcert_no"] = $v["giftcert_no"];
            $tt["gift_type"] = $v["gift_type"];
            $tt["gift_money"] = $v["gift_money"];
            $tt["oper_id"] = $v["oper_id"];
            $tt["oper_name"] = $v["oper_name"];
            $tt["oper_date"] = $v["oper_date"];
            $tt["send_branch"] = $v["send_branch"];
            $tt["branch_name"] = $v["branch_name"];
            $tt["status"] = $v["status"];
            $tt["begin_date"] = $v["begin_date"];
            $tt["end_date"] = $v["end_date"];
            array_push($temp, $tt);
        }
        $result["rows"] = $temp;
        return $result;
    }


    public function AddModelBat($models) {
        $res = 0;
        try {
            $isok = TRUE;
            Db::startTrans();
            foreach ($models as $k => $v) {
                if ($v->save() == FALSE) {
                    $isok = FALSE;
                }
            }
            if ($isok) {
                $res = 1;
                Db::commit();
            } else {
                Db::rollback();
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }

}
