<?php
namespace app\admin\components\Enumerable;
/**
 * 订单状态操作标志 与数据库中T_BD_BASE_CODE,type_no=sp对应
 */
class ESheetOper{

    /**
     * 新增订单 
     */
    const ADD = 1;

    /**
     * 支付订单
     */
    const PAY = 2;

    /**
     * 审核订单
     */
    const APPROVE = 2;

    /**
     * 订单发货
     */
    const SEND = 3;

    /**
     * 订单收货
     */
    const RECIVE = 4;

    /**
     * 订单完成
     */
    const OVER = 5;

    /**
     * 订单取消
     */
    const CANCEL = 6;

    /**
     * 订单退货
     */
    const BACK = 7;

    /**
     * 提交取消订单
     */
    const CCANCEL = 8;

    /**
     * 审核取消订单
     */
    const ACANCEL = 9;

}
