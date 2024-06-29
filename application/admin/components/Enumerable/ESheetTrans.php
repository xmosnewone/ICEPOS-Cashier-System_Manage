<?php
namespace app\admin\components\Enumerable;
/*
 * 单据操作状态
 */

class ESheetTrans{

    /**
     * 直调出库
     */
    const MO = 'MO';

    /**
     * 直调入库
     */
    const MI = 'MI';

    /**
     * 库存调整
     */
    const OO = 'OO';

    /**
     * 报损
     */
    const JO = 'JO';

    /**
     * 调拨差异
     */
    const MD = 'MD';

    /**
     * 采购补单
     */
    const BH = 'BH';

    /**
     * 采购收货
     */
    const PI = 'PI';

    /**
     * 采购订单
     */
    const PO = 'PO';

    /**
     * 要货单
     */
    const YH = 'YH';

    /**
     * 批发订单
     */
    const SS = 'SS'; 
    /**
     * 批发销售
     */
    const SO = 'SO'; 
    /**
     * 批发退货
     */
    const RI = 'RI'; 
    /**
     * 客户结算
     */
    const RP = 'RP';
    /**
     * 加号
     */
    const PLUS = "+";

    /**
     * 减号
     */
    const MINUS = '-';

    /**
     * 促销
     */
    const PS = 'PS';
    
    /**
     *调价单 
     */
    const PX='PX';
    /**
     * 差异处理 
     */
    const PD='PD';
    /**
     * 存货盘点 
     */
    const CR='CR';
}
