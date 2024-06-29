<?php
//pos_pay表
namespace model;
use think\Db;

class PosPay extends BaseModel {
	
	protected $pk='id';
	protected $name="pos_pay";

    public function AddPosPay($flowno, $qrcode, $pay_mount) {
        $result = 0;
        try {
            $model = $this->where("flowno='$flowno'")->find();
            if (empty($model)) {
                $pay = new PosPay();
                $pay->flowno = $flowno;
                $pay->qrcode = $qrcode;
                $pay->pay_amount = $pay_mount;
                $pay->create_time = date(DATETIME_FORMAT);
                if ($pay->save()) {
                    $result = 1;
                }
            } else {
                $result = 1;
            }
        } catch (\Exception $ex) {
            $result = -2;
            write_log("新增支付异常:" . $ex,"PosPay");
        }
        return $result;
    }
    
    public function AddWxPosPay($flowno, $pay_mount) {
    	$result = 0;
    	try {
    		$model = $this->where("flowno='$flowno'")->find();
    		if (empty($model)) {
    			$pay = new PosPay();
    			$pay->flowno = $flowno;
    			$pay->pay_amount = $pay_mount;
    			$pay->create_time = date(DATETIME_FORMAT,time());
    			if ($pay->save()) {
    				$result = 1;
    			}
    		} else {
    			$result = 1;
    		}
    	} catch (\Exception $ex) {
    		$result = -2;
    		write_log("新增微信支付异常:" . $ex,"PosPay");
    	}
    	return $result;
    }
    
    public function UpdatewxPosPay($flowno,$transaction_id) {
    	$result = 0;
    	try {
    		$pay = $this->where("flowno='$flowno'")->find();
    		if (!empty($pay)) {
    			$pay->transaction_id = $transaction_id;
    			$pay->over_time = date(DATETIME_FORMAT,time());
    			$pay->pay_status = '1';
    			if ($pay->save()) {
    				$result = 1;
    			}
    		} else {
    			$result = -1;
    		}
    	} catch (\Exception $ex) {
    		$result = -2;
    		write_log("更新微信支付异常:" . $ex,"PosPay");
    	}
    	return $result;
    }

    public function AddZfbPosPay($flowno, $pay_mount) {
        $result = 0;
        try {
            $model = $this->where("flowno='$flowno'")->find();
            if (empty($model)) {
                $pay = new PosPay();
                $pay->flowno = $flowno;
                $pay->pay_amount = $pay_mount;
                $pay->create_time = date(DATETIME_FORMAT,time());
                if ($pay->save()) {
                    $result = 1;
                }
            } else {
                $result = 1;
            }
        } catch (\Exception $ex) {
            $result = -2;
            write_log("新增支付宝支付异常:" . $ex,"PosPay");
        }
        return $result;
    }

    public function UpdateZfbPosPay($flowno,$transaction_id) {
        $result = 0;
        try {
            $pay = $this->where("flowno='$flowno'")->find();
            if (!empty($pay)) {
                $pay->transaction_id = $transaction_id;
                $pay->over_time = date(DATETIME_FORMAT,time());
                $pay->pay_status = '1';
                if ($pay->save()) {
                    $result = 1;
                }
            } else {
                $result = -1;
            }
        } catch (\Exception $ex) {
            $result = -2;
            write_log("更新支付宝支付异常:" . $ex,"PosPay");
        }
        return $result;
    }



    public function UpdatePosPay($qrcode, $trade_no) {
        $result = 0;
        try {
            $pay = $this->where("qrcode='$qrcode'")->find();
            if (!empty($pay)) {
                $pay->trade_no = $trade_no;
                $pay->over_time = date(DATETIME_FORMAT);
                $pay->pay_status = '1';
                if ($pay->save()) {
                    $result = 1;
                }
            } else {
                $result = -1;
            }
        } catch (\Exception $ex) {
            $result = -2;
            write_log("更新支付异常:" . $ex,"PosPay");
        }
        return $result;
    }

    public function UpdatePosPayByflowno($flowno, $trade_no) {
        $result = 0;
        try {
            $pay = $this->where("flowno='$flowno'")->find();
            if (!empty($pay)) {
                $pay->trade_no = $trade_no;
                $pay->over_time = date(DATETIME_FORMAT);
                $pay->pay_status = '1';
                if ($pay->save()) {
                    $result = 1;
                }
            } else {
                $result = -1;
            }
        } catch (\Exception $ex) {
            $result = -2;
            write_log("更新支付异常:" . $ex,"PosPay");
        }
        return $result;
    }


    public function GetPosPay($flowno) {
        $result = 0;
        try {
            $pay = $this->where("flowno='$flowno'")->find();
            if (!empty($pay)) {
                return $pay->pay_status;
            } else {
                $result = 0;
            }
        } catch (\Exception $ex) {
            $result = -2;
        }
        return $result;
    }

}
