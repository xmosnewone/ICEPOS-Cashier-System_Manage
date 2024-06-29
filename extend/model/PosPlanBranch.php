<?php
//pos_plan_branch表
namespace model;
use think\Db;

class PosPlanBranch extends BaseModel {

    public $branch_name;
    
    protected $pk='plan_no';
    protected $name="pos_plan_branch";

    public function Get($plan_no) {
        try {

            return $this->where("plan_no'$plan_no'")->select();
        } catch (\Exception $ex) {
            return null;
        }
    }


    public function GetBranch($plan_no) {
        try {
           
            $where['s.plan_no']=$plan_no;
            $res=Db::name($this->name)
            		->alias('s')
            		->field("s.plan_no,s.branch_no,a.branch_name")
            		->join("pos_branch_info a","s.branch_no=a.branch_no","left")
            		->where($where)
            		->select();
            
            $temp = array();
            foreach ($res as $v) {
                $tt = array();
                $tt["branch_name"] = $v["branch_name"];
                $tt["branch_no"] = $v["branch_no"];
                $tt["plan_no"] = $v["plan_no"];
                array_push($temp, $tt);
            }
            return $temp;
        } catch (\Exception $ex) {
            return null;
        }
    }


    public function Add($branch) {
        $res = 1;
        try {
            if ($branch->save()) {
                $res = 1;
            } else {
                $res = 0;
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }


    public function Del($plan_no) {
        $res = 1;
        try {
            $record = $this->where("plan_no='$plan_no'")->delete();
            if ($record > 0) {
                $res = 1;
            } else {
                $res = 0;
            }
        } catch (\Exception $ex) {
            $res = -2;
        }
        return $res;
    }

}
