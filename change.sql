--
-- 表 `ice_pos_payflow`  2024-10-02 
--
ALTER TABLE `ice_pos_payflow` ADD COLUMN `refund_flag` tinyint(1) UNSIGNED DEFAULT 0 COMMENT '1表示已退款，0正常' AFTER pos_flag;
ALTER TABLE `ice_pos_payflow` CHANGE `pos_id` `pos_id` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `ice_pos_payflow` CHANGE `branch_no` `branch_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `ice_pos_payflow` CHANGE `vip_no` `vip_no` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `ice_pos_saleflow` CHANGE `pos_id` `pos_id` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `ice_pos_saleflow` CHANGE `branch_no` `branch_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `ice_pos_pay` ADD `payflow_id` INT(11) UNSIGNED NULL DEFAULT '0' COMMENT 'pos_payflow表的id值' AFTER `id`, ADD INDEX (`payflow_id`);
ALTER TABLE `ice_pos_pay` ADD `coin_type` VARCHAR(20) NULL COMMENT '支付方式英文简称' AFTER `payflow_no`;
ALTER TABLE `ice_pos_pay` CHANGE `pay_status` `pay_status` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0' COMMENT '0未支付，1已支付，2已退款';
ALTER TABLE `ice_member` CHANGE `ucode` `ucode` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户编码-微信等编码统一';

--
--  2024-10-05
--
ALTER TABLE `ice_pos_branch_info` ADD `wechat_private_cert` VARCHAR(200) NULL COMMENT '微信支付私钥证书绝对路径' AFTER `wechat_pay_qrcode`;
ALTER TABLE `ice_pos_branch_info` ADD `wechat_public_cert` VARCHAR(200) NULL COMMENT '微信支付公钥证书绝对路径' AFTER `wechat_private_cert`;
ALTER TABLE `ice_pos_branch_info` CHANGE `wechat_private_cert` `wechat_apiclient_cert` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信支付私钥证书绝对路径';
ALTER TABLE `ice_pos_branch_info` CHANGE `wechat_public_cert` `wechat_apiclient_key` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信支付公钥证书绝对路径';
ALTER TABLE `ice_pos_branch_info` DROP INDEX `branch_no_2`;

--
--  2024-10-06
--
ALTER TABLE `ice_im_check_detail` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `ice_im_check_init` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '盘点号';
ALTER TABLE `ice_im_check_master` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '库存盘点单号';
ALTER TABLE `ice_im_check_master` CHANGE `check_no` `check_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '盘点批号';
ALTER TABLE `ice_im_check_sum` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '盘点批号';
ALTER TABLE `ice_im_sheet_detail` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `ice_im_sheet_master` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '单号';
ALTER TABLE `ice_stock_flow` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '订单编号';
ALTER TABLE `ice_wm_sheet_detail` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `ice_wm_sheet_master` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '单号';
ALTER TABLE `ice_pm_sheet_detail` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '采购单主表编号';
ALTER TABLE `ice_pm_sheet_master` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '采购订单主编号';
ALTER TABLE `ice_fm_recpay_detail` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '结算单主编号';
ALTER TABLE `ice_fm_recpay_master` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '结算单编号';