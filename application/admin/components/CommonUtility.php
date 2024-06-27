<?php
namespace app\admin\components;
//后台公共工具类库
class CommonUtility {
    //put your code here

    /**
     * 根据单据类型，请求分店编号，接受分店编号创建单据编号
     * @param type $type
     * @param type $branch_no
     * @param type $d_branch_no
     */
    public static function CreateSheetno($type, $branch_no, $d_branch_no) {
        $sheet_no = "";
        $day = substr(date('Ymd'), 2);
        $sheet_no = implode("-", array($type, $branch_no, $day, $d_branch_no));
        return $sheet_no;
    }
    /**
     * 保留两位小数
     * @param float $num 
     * @return float 两位小数
     */
    public static function getRound2Float($num){
        return sprintf("%.2f", $num);
    }
}
