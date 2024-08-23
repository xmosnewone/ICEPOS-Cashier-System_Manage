<?php
/**
 * pos_status表
 */
namespace model;
use think\Db;


class PosStatus extends BaseModel {

	protected $pk=['branch_no','posid'];
	protected $name="pos_status";
	
    public function getone($posid, $con = '') {
        return Db::table($this->table)
        ->field("*")
        ->where("posid='$posid'")
        ->find();
    }
    
    public function getone_bybranchno($branch_no) {
    	return Db::table($this->table)
    	->field("*")
    	->where("branch_no='$branch_no'")
    	->find();
    }
    
    public function getAllBywhere($where="") {
    	return Db::table($this->table)
    	->field("*")
    	->where($where)
    	->select();
    }


    public function GetModelsForPos($branch_no) {
        return Db::table($this->table)
        ->field("*")
        ->where("branch_no='$branch_no'")
        ->select();
    }


    public function ValiedatePosForPos($hostDisk, $hostmac, $branch_no, $posid, $hostname) {
        $result = -1;
        try {
        	$model=$this->where([
        			"hostdisk" => $hostDisk,
        			"hostmac" => $hostmac,
        			"branch_no" => $branch_no,
        			"posid" => $posid,
        			"hostname" => $hostname
        	])
        	->find();
           
            if (empty($model)) {
                $model1=$this->where([
                		"hostdisk" => $hostDisk,
                		"hostmac" => $hostmac,
                		"hostname" => $hostname
                ])
                ->find();
                
                if (empty($model1)) {
                    $model2=$this->where([
                    		"branch_no" => $branch_no,
                    		"posid" => $posid
                    ])
                    ->find();
                    //该门店未登记过的收银机硬件
                    if (empty($model2)) {

                        $model3 = new PosStatus();
                        $model3->branch_no = $branch_no;
                        $model3->posid = $posid;
                        $model3->hostdisk = $hostDisk;
                        $model3->hostmac = $hostmac;
                        $model3->hostname = $hostname;
                        $model3->operdate = date(DATETIME_FORMAT, time());
                        $model3->status = "1";
                        $model3->load_flag = "1";
                        if ($model3->save()) {
                            $result = 1;
                        } else {
                            $result = -1;
                        }
                    } else {
                    	//已登记该硬件的收银机
                    	//已停用
                        if ($model2->status == "0") {
                            $result = -3;
                        } else {
                        	//未绑定收银机
                            if ($model2->load_flag == "0") {
                                $model2->hostdisk = $hostDisk;
                                $model2->hostmac = $hostmac;
                                $model2->hostname = $hostname;
                                $model2->operdate = date(DATETIME_FORMAT, time());
                                $model2->load_flag = "1";
                                if ($model2->save()) {
                                    $result = 1;
                                } else {
                                    $result = -1;
                                }
                            } else {
								//POS端重新编译后，application.xml会被重写
								//如果是不小心删除了C#端的application.xml导致返回结果3
								//，则需要在管理后台解绑POS机，C#端重新打开软件且重新连接即可
                                $result = 3;
                            }
                        }
                    }
                } else {
                    if ($model1->status == "0") {
                        $result = -4;
                    } else {
                        $result = 2;
                    }
                }
            } else {
                if ($model->status == "0") {
                    $result = -4;
                } else {
                    $result = 0;
                }
            }
        } catch (\Exception $ex) {

            $result = -2;
        }
        return $result;
    }


    public function GetAdUrlForPos($branch_no, $pos_id) {
        $adUrl = "";
        try {
            $model=$this->where([
            		"branch_no" => $branch_no,
            		"posid" => $pos_id
            ])
            ->find();
            if (!empty($model)) {
                $adUrl = $model->adurl;
            }
        } catch (\Exception $ex) {
            $adUrl = $ex;
        }
        return $adUrl;
    }


    public function GetModelsForControls($branch_no, $page, $rows, $keyword) {
       
        $where="1=1";
        if (!empty($branch_no)) {
        	$where.=" and s.branch_no='$branch_no'";
        }
        if (!empty($keyword)) {
        	$where.=" and (s.posid like '%$keyword%' or s.hostname like '%$keyword%')";
        }
        
        $offset=($page - 1) * $rows;
        
        $list=Db::table($this->table)
        ->alias('s')
        ->field("s.posid,s.hostname")
        ->where($where)
        ->limit($offset,$rows)
        ->select();
        
        $count=Db::table($this->table)
        ->alias('s')
        ->where($where)
        ->count();
        
        $result = array();
        $result["total"] = $count;
        $result["rows"] = $list;
        return $result;
    }


    public function UnBind($branch_no, $pos_id) {
        $result = 0;
        try {
            $model=$this->where([
            		"branch_no" => $branch_no,
            		"posid" => $pos_id
            ])
            ->find();
            if ($model->status == "0" || $model->load_flag == "0") {
                $result = -1;
            } else {
                $model->hostdisk = "";
                $model->hostmac = "";
                $model->hostname = "";
                $model->operdate = date(DATETIME_FORMAT, time());
                $model->load_flag = "0";
                if ($model->save()) {
                    $result = 1;
                } else {
                    $result = 0;
                }
            }
        } catch (\Exception $ex) {

            $result = -2;
        }
        return $result;
    }


    public function UpdateStatus($branch_no, $posid) {
        $result = 0;
        try {
            $model=$this->where([
            		"branch_no" => $branch_no,
            		"posid" => $posid
            ])
            ->find();
            if (empty($model)) {
                $result = -1;
            } else {
                if ($model->status === '0') {
                    $model->status = '1';
                } else {
                    $model->status = '0';
                }
                if ($model->save()) {
                    $result = 1;
                } else {
                    $result = 0;
                }
            }
        } catch (\Exception $ex) {
            $result = -2;
        }
        return $result;
    }

    //删除
    public function deleteOne($posid,$branch_no){
    	$result=$this->where("posid='$posid' and branch_no='$branch_no'")->delete();
    	if ($result) {
    		return true;
    	}else{
    		return false;
    	}
    }


    
}
