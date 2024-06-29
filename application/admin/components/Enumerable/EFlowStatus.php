<?php
namespace app\admin\components\Enumerable;
/**
 * 前台订单状态枚举 对应T_BD_BASE_CODE，type_no='OF'
 */
class EFlowStatus{

    /**
     * 订单原始状态
     */
    const DEF = 0;

    /**
     * 等待付款 
     */
    const ADD = 1;

    /**
     * 等待确认
     */
    const APPROVE = 2;

    /**
     * 等待发货
     */
    const SEND = 3;

    /**
     * 等待收货
     */
    const RECIVE = 4;

    /**
     * 已完成
     */
    const OVER = 5;

    /**
     * 已取消
     */
    const CANCEL = 6;

    /**
     * 已退货
     */
    const BACK = 7;
}
