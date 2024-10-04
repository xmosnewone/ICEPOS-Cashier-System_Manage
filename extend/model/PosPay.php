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
                $pay->coin_type = "Wechat";
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
                $pay->coin_type = "ZFB";
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

    //$flowno 是微信/支付宝的临时流水订单号
    //$payflow_no 是收银机的唯一支付流水订单号
    //$payflow_id 是pos_payflow表的自动id
    public function UpdatePosPayFlowno($payflow_no,$payflow_id,$flowno) {
        $result = 0;
        try {
            $pay = $this->where("flowno='$flowno'")->find();
            if (!empty($pay)) {
                $pay->payflow_id = $payflow_id;
                $pay->payflow_no = $payflow_no;
                if ($pay->save()) {
                    $result = 1;
                }
            } else {
                $result = -1;
            }
        } catch (\Exception $ex) {
            $result = -2;
            write_log("更新payflow_no异常:" . $ex,"PosPay");
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
