<?php
namespace app\admin\components\Enumerable;
/*
 * 商品拆分组合
 */
class EItemCombSplit{
    /**
    * 普通商品     
    */
    const GENERAL = 0;
    /**
     * 捆绑商品
     */
    const BUNDLE = 1;
    /**
     * 制单拆分
     */
    const SPLIT = 2; 
    /**
     * 制单组合
     */
    const COMB = 3; 
    /**
     * 自动转货
     */
    const AUTOITEM = 6; 
     /**
     * 自动加工
     */
    const AUTOPROCESS = 7; 
}
