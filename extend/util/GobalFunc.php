<?php
namespace util;
class GobalFunc {


    public static function GetUid($prefix = "") {
        $str = md5($prefix . uniqid(mt_rand(), true));
        $uuid = substr($str, 0, 8) . '-';
        $uuid .= substr($str, 8, 4) . '-';
        $uuid .= substr($str, 12, 4) . '-';
        $uuid .= substr($str, 16, 4) . '-';
        $uuid .= substr($str, 20, 12);
        return $uuid;
    }


    public static function GetOrderId($prefix = "") {
        $yearCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $orderSn = $yearCode[intval(date('Y')) - 2014] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        return $orderSn;
    }


    public static function filtrate($msg) {
        $msg = str_replace('&amp;', '&', $msg);
        $msg = str_replace('&nbsp;', ' ', $msg);
        $msg = str_replace('"', '&quot;', $msg);
        $msg = str_replace("'", '&#39;', $msg);
        $msg = str_replace("<", "&lt;", $msg);
        $msg = str_replace(">", "&gt;", $msg);
        $msg = str_replace("\t", "   &nbsp;  &nbsp;", $msg);
        $msg = str_replace("\r", "", $msg);
        $msg = str_replace("   ", " &nbsp; ", $msg);
        return $msg;
    }


    public static function filterString($msg, $ty) {

        $getfilter = "/'|(and|or)\\b.+?(>|<|=|in|like)|\\/\\*.+?\\*\\/|<\\s*script\\b|<\/script.*?>|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)/";
        $postfilter = "/\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b>|<\/script.*?>|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)/";

        if ($ty == "post") { {
                $result = preg_replace(strtolower($postfilter), "", $msg);
                $result = preg_replace(strtoupper($postfilter), "", $msg);
            }
        } else if ($ty == "get") {
            $result = preg_replace(strtolower($getfilter), "", $msg);
            $result = preg_replace(strtoupper($getfilter), "", $msg);
        }
        return $result;
    }


    public static function getPhone($telephone) {
        if (empty($telephone)) {
            return '';
        } else {
            $arr = explode('-', $telephone);
            $result = '';
            $arrresult = array();
            for ($i = 0; $i < count($arr); $i++) {
                if (!empty($arr[$i])) {
                    array_push($arrresult, $arr[$i]);
                }
            }
            if (count($arrresult) > 0) {
                $result = implode('-', $arrresult);
            }
            return $result;
        }
    }


    public static function setPhone($telephone1, $telephone2, $telephone3) {
        $telephone = '';
        $qh = '';
        $dh = '';
        $fj = '';

        if (isset($telephone1) && !empty($telephone1)) {
            $qh = $telephone1;
        }
        if (isset($telephone2) && !empty($telephone2)) {
            $dh = $telephone2;
        }
        if (isset($telephone3) && !empty($telephone3)) {
            $fj = $telephone3;
        }
        if ($qh != '') {
            $qh = $qh . '-';
        }
        if ($fj != '') {
            $fj = '-' . $fj;
        }
        $telephone = $qh . $dh . $fj;
        return $telephone;
    }

	//获取城市名称
    public static function getCityNames($ty, $prov, $city, $area) {
        $arr = array();
        $isok = true;
        $citys = SITE_URL . 'xml/province_city.xml';
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->load($citys);
        $county = $dom->getElementsByTagName('country');
        $provices = $county->item(0)->getElementsByTagName('province');
        for ($i = 0; (($i < $provices->length) && $isok == true); $i++) {
            if ($provices->item($i)->getAttribute('code') == $prov) {
                $arr['provname'] = $provices->item($i)->getAttribute('value');
                if ($ty === 1) {
                    $isok = false;
                    break;
                }
                $citys = $provices->item($i)->getElementsByTagName('city');
                if ($citys->length > 0) {
                    for ($j = 0; (($j < $citys->length) && $isok == true); $j++) {
                        if ($citys->item($j)->getAttribute('code') == $city) {
                            $countys = $citys->item($j)->getElementsByTagName('county');
                            $arr['cityname'] = $citys->item($j)->getAttribute('value');
                            if ($ty === 2) {
                                $isok = false;
                                break;
                            }
                            if ($countys->length > 0) {
                                for ($m = 0; (($m < $countys->length) && $isok == true); $m++) {
                                    if ($countys->item($m)->getAttribute('code') == $area) {
                                        $arr['areaname'] = $countys->item($m)->getAttribute('value');
                                        break;
                                    }
                                }
                            }
                            break;
                        }
                    }
                }
                break;
            }
        }
        return $arr;
    }


    public static function sendEmailCode($email) {
        if (!empty($email)) {
            $emailCode = rand(100000, 999999);

            //$email=new PHPMailer();
            if ($email->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

	//发送短信验证码
    public static function sendSMSCode($mobile) {

        if (!empty($mobile)) {
            $mobileCode = rand(100000, 999999);
            //短信接口要自己补充
            $return = file_get_contents($url);
            if (trim($return) == 'ok') {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

	//返回隐藏部分数字的电话号码
    public static function GetTelephone($str, $type) {
        if (!empty($str)) {
            if ($type == "mobile") {
                $pattern = "/(1\d{1,2})\d(\d{0,3})/";
                $replacement = "\$1****\$3";
                return preg_replace($pattern, $replacement, $str);
            } else if ($type == "email") {
                $arr = explode("@", $str);
                $len = strlen($arr[0]);
                $replace = "*";
                for ($i = 1; $i < $len - 2; $i ++) {
                    $replace = $replace . "*";
                }

                $str1 = $arr[0][0] . '****' . $arr[0][$len - 1];
                return $str1 . "@" . $arr[1];
            }
        } else {
            return $str;
        }
    }

	//发送短信内容
    public static function SendSMSMessage($mobile, $content) {

        return $return;
    }


    public static function SendEmailMessage($email, $subject, $content) {
       
    	//需要自己补充发送邮件逻辑
        if (true) {
            return "ok";
        } else {
            return "fail";
        }
    }


    public static function GenerateCode() {
        return rand(100000, 999999);
    }


    public static function GetUrlToken($url) {
        return md5($url);
    }


    public static function fast_uuid($suffix_len = 3) {

        static $being_timestamp = 1401347375;

        $time = explode(' ', microtime());
        $id = ($time[1] - $being_timestamp) . sprintf('%06u', substr($time[0], 2, 6));
        if ($suffix_len > 0) {
            $id .= substr(sprintf('%010u', mt_rand()), 0, $suffix_len);
        }
        return $id;
    }


    public static function getSearchString($clsno = null, $brand = null, $area = null, $size = null, $keyword = null, $priceHigh = null, $priceLow = null, $itemname = null, $itemno = null) {
        $result = "";
       
        return $result;
    }


    public static function getRealIpAddr() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }


    public static function getStrlen($str) {
        return mb_strlen($str, "utf8");
    }


    public static function errMessage($code, $flag, $errMessage) {
        $errorMessage['code'] = $code;
        $errorMessage['flag'] = $flag;
        $errorMessage['errMessage'] = $errMessage;

        return $errorMessage;
    }


    public static function checkMobileVerify($mobile) {
        if (preg_match("/^13|4|5|7|8[0-9]{8}$/", $mobile)) {
            return true;
        } else {
            return false;
        }
    }


    public static function FormatMoneyDisplay($amount) {
        return sprintf("%.2f", $amount);
    }

    public static function GetDiscust() {
       
        return $discust;
    }


    public static function GetDefDiscust() {
       
        return $discust;
    }


    public static function _addJsonResponse($code, $message) {
        return (array("code" => $code, "message" => $message));
    }

    public static function GetRands($length){
        $hash = '';
		$chars = '0126ABCDJK789abcRSTUdefghijkLMNOPQVWXYZlmnopq345rstuEFGHIvwxyz';
		$max = strlen($chars) - 1;
		mt_srand((double)microtime() * 1000000);
		for($i = 0; $i < $length; $i++) {
		$hash .= $chars[mt_rand(0, $max)];
		}
		return $hash;
    }

    public static function Get_real_ip(){ 
        $ip=false; 
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){ 
            $ip = $_SERVER["HTTP_CLIENT_IP"]; 
        } 
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']); 
            if ($ip) { array_unshift($ips, $ip); $ip = FALSE; } 
            for ($i = 0; $i < count($ips); $i++) { 
                if (!preg_match ("/^(10|172\.16|192\.168)\./", $ips[$i])) { 
                    $ip = $ips[$i]; 
                    break; 
                } 
            } 
        } 
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']); 
        } 

}
