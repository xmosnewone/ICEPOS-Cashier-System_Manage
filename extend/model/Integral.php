<?php
//Integral表
namespace model;
use think\Db;

class Integral extends BaseModel {

	protected $pk='id';
	protected $name="integral";

	//新增或修改操作
    public function AddOrUpdate($model, $addOrUpdate) {
        $res = 1;
        try {
            $res = $this->Check($model);
            if ($res == 1) {
                if ($addOrUpdate != "add") {
                    $temp = $this->Get($model->id);
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

    //检测数据是否为空
    private function Check($model) {
        $res = 1;
        try {
            if (empty($model->title)) {
                $res = -2;
            } else if (empty($model->branch_no)) {
                $res = -3;
            } else if (!is_numeric($model->rate)) {
                $res = -4;
            }else if (empty($model->start_date)) {
                $res = -5;
            }else if (empty($model->end_date)) {
                $res = -6;
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }

    //获取单记录
    public function Get($id) {
        try {
            return $this->where("id='$id'")->find();
        } catch (\Exception $ex) {
            return null;
        }
    }

    //条件返回方案信息
    public function getWhere($where){
        $one=Db::name($this->name)->where($where)->find();
        return $one?$one:false;
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
    
    //搜索列表
    public function SearchIntegral($start, $end, $branch_no, $status, $page, $rows) {
       
        $where="1=1";
        if (!empty($start) && !empty($end)) {
            $start=ymktime($start);
            $end=ymktime($end);
            if ($start == $end) {
                $where.=" and start_date >= $start";
            } else {
                $where.=" and start_date >= $start and end_date <= $end";
            }
        }
        if (!empty($branch_no)) {
            $where.=" and branch_no like '%$branch_no%'";
        }

        if (!empty($status)) {
            $where.=" and status = '$status'";
        }
        
        $offset = ($page - 1) * $rows;
        
        $res=Db::name($this->name)
        		->field("*")
        		->limit($offset,$rows)
        		->where($where)
        		->select();
        
        $count=Db::name($this->name)->where($where)->count();

        $result = array();
        $result["total"] = $count;
        $temp = array();
        foreach ($res as $k => $v) {
            $v["start_date"]=date('Y-m-d H:i:s',$v['start_date']);
            $v["end_date"]=date('Y-m-d H:i:s',$v['end_date']);
            $v["add_date"]=date('Y-m-d H:i:s',$v['add_date']);
            array_push($temp, $v);
        }
        $result["rows"] = $temp;
        return $result;
    }

    //批量添加新记录
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
