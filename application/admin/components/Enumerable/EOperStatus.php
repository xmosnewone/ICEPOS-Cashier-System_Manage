<?php
namespace app\admin\components\Enumerable;
/**
 * 操作状态枚举
 */
class EOperStatus{

    /**
     * 新增
     */
    const ADD = "I";

    /**
     * 删除
     */
    const DELETE = "D";

    /**
     * 更新
     */
    const UPDATE = "U";

    /**
     * 提交
     */
    const COMMIT = "C";

    /**
     * 终止
     */
    const STOP = "S";

    /**
     * 审核
     */
    const APPROVE = "A";

}
