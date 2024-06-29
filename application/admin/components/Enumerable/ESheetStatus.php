<?php
namespace app\admin\components\Enumerable;
/*
 * 单据操作状态
 */
class ESheetStatus{
    /**
     * 未处理 
     */
    const NONDEAR = 0;
    /**
     * 部分处理
     */
    const PARTDEAR = 1;
    /**
     * 全部处理
     */
    const DEARED = 2; 
    /**
     * 终止
     */
    const CLOSE = 4; 
    /**
     * 退货
     */
    const RETU = 5;

}
