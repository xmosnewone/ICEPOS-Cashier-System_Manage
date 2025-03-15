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
ALTER TABLE `ice_pm_sheet_detail` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '采购单主表编号';
ALTER TABLE `ice_pm_sheet_master` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '采购订单主编号';
ALTER TABLE `ice_fm_recpay_detail` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '结算单主编号';
ALTER TABLE `ice_fm_recpay_master` CHANGE `sheet_no` `sheet_no` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '结算单编号';

--
--  2024-10-08
--
ALTER TABLE `ice_pos_branch_info` CHANGE `alipay_public_key` `alipay_public_key` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '支付宝公钥';
ALTER TABLE `ice_pos_branch_info` CHANGE `alipay_private_key` `alipay_private_key` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '商户私钥';

--
--  2024-10-11
--
ALTER TABLE `ice_bd_item_combsplit` CHANGE `memo` `memo` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

--
--  2024-10-31
--
DROP TABLE IF EXISTS `ice_pos_feedback`;
CREATE TABLE IF NOT EXISTS `ice_pos_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_no` varchar(10) NOT NULL,
  `posid` varchar(10) NOT NULL,
  `oper_id` varchar(10) NOT NULL,
  `content` varchar(50) NOT NULL,
  `reply` text COMMENT '回复',
  `reply_date` int(11) UNSIGNED DEFAULT '0' COMMENT '回复日期',
  `oper_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `ice_function` (`id`, `name`, `code`, `icon`, `action`, `add_time`, `parent`, `url`, `is_display`, `level`, `orderby`) VALUES
(139, 'POS端留言', 'POS_Feedback', '', NULL, '1730384721', '98', '/admin/portal/Guestbook/posFeed', 1, 3, 109);

--
--  2024-11-03
--
ALTER TABLE `ice_im_sheet_master` ADD `add_date` INT(11) UNSIGNED NULL DEFAULT '0' COMMENT '新建单据时间戳' AFTER `approve_flag`;

--
--  2024-12-20
--
ALTER TABLE `ice_pos_operator` CHANGE `num1` `num1` DOUBLE NULL DEFAULT '1';
ALTER TABLE `ice_pos_operator` CHANGE `cashier_status` `cashier_status` CHAR(6) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '正常';

--
--  2024-12-27
--
ALTER TABLE `ice_integral_member` ADD `refund_flag` TINYINT(1) UNSIGNED NULL DEFAULT '0' COMMENT '0未退款，1已退款' AFTER `add_date`;

--
--  2025-02-13
--
ALTER TABLE `ice_news_type` ADD `orderby` INT(6) UNSIGNED NULL DEFAULT '0' COMMENT '排序' AFTER `name`;
ALTER TABLE `ice_news` ADD `link` TEXT NULL COMMENT '链接' AFTER `username`;
ALTER TABLE `ice_news` ADD `is_enabled` TINYINT(1) UNSIGNED NULL DEFAULT '1' COMMENT '1表示显示，0隐藏' AFTER `content`;

--
--  2025-02-19
--
ALTER TABLE `ice_pos_branch_info` CHANGE `branch_mj` `branch_mj` DOUBLE NULL DEFAULT NULL COMMENT '所占面积（㎡）';
ALTER TABLE `ice_pos_branch_info` ADD `logo` VARCHAR(200) NULL COMMENT '门店logo' AFTER `ismall`;
ALTER TABLE `ice_member` ADD `headimg` VARCHAR(250) NULL COMMENT '头像图片' AFTER `wechat`;
ALTER TABLE `ice_member` ADD `unionid` VARCHAR(50) NULL COMMENT ' 微信或其他平台统一的用户跨平台uid' AFTER `openid`;

--
--  2025-02-25
--
--
-- 表的结构 `ice_sms`
--

DROP TABLE IF EXISTS `ice_sms`;
CREATE TABLE IF NOT EXISTS `ice_sms` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `mobile` varchar(20) NOT NULL COMMENT '手机号码',
  `code` varchar(20) NOT NULL COMMENT '验证码',
  `send_time` int(11) UNSIGNED DEFAULT NULL COMMENT '发送时间戳',
  `expire_time` int(11) UNSIGNED DEFAULT NULL COMMENT '有效时间戳',
  PRIMARY KEY (`id`),
  KEY `mobile` (`mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='发送短信验证码记录表';

--
-- 表的结构 `ice_system_config`
--

DROP TABLE IF EXISTS `ice_system_config`;
CREATE TABLE IF NOT EXISTS `ice_system_config` (
  `key` varchar(50) NOT NULL COMMENT '设置名称',
  `value` text NOT NULL COMMENT '设置内容',
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='系统设置';

--
-- 表的结构 `ice_system_log`
--

DROP TABLE IF EXISTS `ice_system_log`;
CREATE TABLE IF NOT EXISTS `ice_system_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `loginnam` varchar(50) DEFAULT NULL COMMENT '登陆名',
  `uid` int(11) UNSIGNED NOT NULL COMMENT '登录uid',
  `logtxt` text COMMENT '日志',
  `add_time` int(11) UNSIGNED DEFAULT NULL COMMENT '添加日期',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='后台管理员操作日志';

ALTER TABLE `ice_news` ADD `visit` INT(11) UNSIGNED NULL DEFAULT '0' COMMENT '浏览次数' AFTER `is_enabled`;

ALTER TABLE `ice_portal_ad` ADD `link` VARCHAR(250) NULL COMMENT '超链接' AFTER `ad_code`;

ALTER TABLE `ice_pos_payflow` CHANGE `memo` `memo` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `ice_pos_status` ADD `postype` TINYINT(2) UNSIGNED NULL DEFAULT '0' COMMENT '收银机终端类型，0人工收银，1自助机' AFTER `hostmac`;