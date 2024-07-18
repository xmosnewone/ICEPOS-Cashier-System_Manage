<?php
if(!defined('APP_PATH')) exit('Access Denied');
return $function=Array
	(
	102 => Array
		(
		'id' => 102,
		'name' => '首页',
		'icon' => 'layui-icon-chart',
		'parent' => '',
		'url' => '',
		'level' => 1,
		'orderby' => '0'
		),
	105 => Array
		(
		'id' => 105,
		'name' => '数据统计',
		'icon' => 'layui-icon-component',
		'parent' => 103,
		'url' => '/admin/main/data',
		'level' => 3,
		'orderby' => '0'
		),
	107 => Array
		(
		'id' => 107,
		'name' => '门店地图',
		'icon' => 'layui-icon-component',
		'parent' => 103,
		'url' => '/admin/main/shopmap',
		'level' => 3,
		'orderby' => '0'
		),
	110 => Array
		(
		'id' => 110,
		'name' => '会员资料',
		'icon' => 'layui-icon-component',
		'parent' => 108,
		'url' => '',
		'level' => 2,
		'orderby' => '0'
		),
	111 => Array
		(
		'id' => 111,
		'name' => '会员列表',
		'icon' => 'layui-icon-component',
		'parent' => 110,
		'url' => '/admin/member/index',
		'level' => 3,
		'orderby' => '0'
		),
	112 => Array
		(
		'id' => 112,
		'name' => '会员等级',
		'icon' => 'layui-icon-component',
		'parent' => 108,
		'url' => '/admin/member/level',
		'level' => 2,
		'orderby' => '0'
		),
	114 => Array
		(
		'id' => 114,
		'name' => '等级列表',
		'icon' => 'layui-icon-component',
		'parent' => 112,
		'url' => '/admin/member/level',
		'level' => 3,
		'orderby' => '0'
		),
	1 => Array
		(
		'id' => 1,
		'name' => '商品仓库',
		'icon' => 'layui-icon-component',
		'parent' => '0',
		'url' => '',
		'level' => 1,
		'orderby' => 1
		),
	2 => Array
		(
		'id' => 2,
		'name' => '采购管理',
		'icon' => 'layui-icon-list',
		'parent' => '0',
		'url' => '',
		'level' => 1,
		'orderby' => 2
		),
	3 => Array
		(
		'id' => 3,
		'name' => '促销管理',
		'icon' => 'layui-icon-download-circle',
		'parent' => '0',
		'url' => '',
		'level' => 1,
		'orderby' => 3
		),
	4 => Array
		(
		'id' => 4,
		'name' => '销售管理',
		'icon' => 'layui-icon-user',
		'parent' => '0',
		'url' => '',
		'level' => 1,
		'orderby' => 4
		),
	5 => Array
		(
		'id' => 5,
		'name' => '批发管理',
		'icon' => 'layui-icon-diamond',
		'parent' => '',
		'url' => '',
		'level' => 1,
		'orderby' => 5
		),
	6 => Array
		(
		'id' => 6,
		'name' => '库存管理',
		'icon' => 'layui-icon-search',
		'parent' => '',
		'url' => '',
		'level' => 1,
		'orderby' => 6
		),
	108 => Array
		(
		'id' => 108,
		'name' => '会员管理',
		'icon' => 'layui-icon-friends',
		'parent' => '',
		'url' => '',
		'level' => 1,
		'orderby' => 7
		),
	11 => Array
		(
		'id' => 11,
		'name' => '报表分析',
		'icon' => 'layui-icon-table',
		'parent' => '',
		'url' => '',
		'level' => 1,
		'orderby' => 11
		),
	12 => Array
		(
		'id' => 12,
		'name' => '系统管理',
		'icon' => 'layui-icon-set-sm',
		'parent' => '',
		'url' => '',
		'level' => 1,
		'orderby' => 12
		),
	13 => Array
		(
		'id' => 13,
		'name' => '门店仓库',
		'icon' => 'layui-icon-component',
		'parent' => 1,
		'url' => '',
		'level' => 2,
		'orderby' => 13
		),
	14 => Array
		(
		'id' => 14,
		'name' => '代码信息',
		'icon' => 'layui-icon-component',
		'parent' => 1,
		'url' => '',
		'level' => 2,
		'orderby' => 14
		),
	15 => Array
		(
		'id' => 15,
		'name' => '商品信息',
		'icon' => 'layui-icon-component',
		'parent' => 1,
		'url' => '',
		'level' => 2,
		'orderby' => 15
		),
	16 => Array
		(
		'id' => 16,
		'name' => '商品维护',
		'icon' => 'layui-icon-component',
		'parent' => 1,
		'url' => '',
		'level' => 2,
		'orderby' => 16
		),
	17 => Array
		(
		'id' => 17,
		'name' => '基础信息',
		'icon' => 'layui-icon-component',
		'parent' => 2,
		'url' => '',
		'level' => 2,
		'orderby' => 17
		),
	18 => Array
		(
		'id' => 18,
		'name' => '订单管理',
		'icon' => 'layui-icon-component',
		'parent' => 2,
		'url' => '',
		'level' => 2,
		'orderby' => 18
		),
	19 => Array
		(
		'id' => 19,
		'name' => '促销方案',
		'icon' => 'layui-icon-component',
		'parent' => 3,
		'url' => '',
		'level' => 2,
		'orderby' => 19
		),
	20 => Array
		(
		'id' => 20,
		'name' => '基础信息',
		'icon' => 'layui-icon-component',
		'parent' => 4,
		'url' => '',
		'level' => 2,
		'orderby' => 20
		),
	21 => Array
		(
		'id' => 21,
		'name' => '销售查询',
		'icon' => 'layui-icon-component',
		'parent' => 4,
		'url' => '',
		'level' => 2,
		'orderby' => 21
		),
	22 => Array
		(
		'id' => 22,
		'name' => '客户信息',
		'icon' => 'layui-icon-component',
		'parent' => 5,
		'url' => '',
		'level' => 2,
		'orderby' => 22
		),
	24 => Array
		(
		'id' => 24,
		'name' => '盘点业务',
		'icon' => 'layui-icon-component',
		'parent' => 6,
		'url' => '',
		'level' => 2,
		'orderby' => 24
		),
	25 => Array
		(
		'id' => 25,
		'name' => '其他业务',
		'icon' => 'layui-icon-component',
		'parent' => 6,
		'url' => '',
		'level' => 2,
		'orderby' => 25
		),
	26 => Array
		(
		'id' => 26,
		'name' => '库存查询',
		'icon' => 'layui-icon-component',
		'parent' => 6,
		'url' => '',
		'level' => 2,
		'orderby' => 26
		),
	31 => Array
		(
		'id' => 31,
		'name' => '进销存报表',
		'icon' => 'layui-icon-component',
		'parent' => 11,
		'url' => '',
		'level' => 2,
		'orderby' => 31
		),
	32 => Array
		(
		'id' => 32,
		'name' => '用户管理',
		'icon' => 'layui-icon-component',
		'parent' => 12,
		'url' => '',
		'level' => 2,
		'orderby' => 32
		),
	34 => Array
		(
		'id' => 34,
		'name' => '新闻管理',
		'icon' => 'layui-icon-component',
		'parent' => 12,
		'url' => '',
		'level' => 2,
		'orderby' => 34
		),
	35 => Array
		(
		'id' => 35,
		'name' => '图片管理',
		'icon' => 'layui-icon-component',
		'parent' => 12,
		'url' => '',
		'level' => 2,
		'orderby' => 35
		),
	36 => Array
		(
		'id' => 36,
		'name' => '缓存管理',
		'icon' => 'layui-icon-component',
		'parent' => 12,
		'url' => '',
		'level' => 2,
		'orderby' => 36
		),
	37 => Array
		(
		'id' => 37,
		'name' => '门店仓库',
		'icon' => 'layui-icon-component',
		'parent' => 13,
		'url' => '/admin/pos/branch/branchlist',
		'level' => 3,
		'orderby' => 37
		),
	38 => Array
		(
		'id' => 38,
		'name' => '基础代码',
		'icon' => 'layui-icon-component',
		'parent' => 14,
		'url' => '/admin/baseCode/codelist',
		'level' => 3,
		'orderby' => 38
		),
	39 => Array
		(
		'id' => 39,
		'name' => '基础代码分类',
		'icon' => 'layui-icon-component',
		'parent' => 14,
		'url' => '/admin/baseCode/type',
		'level' => 3,
		'orderby' => 39
		),
	40 => Array
		(
		'id' => 40,
		'name' => '商品管理',
		'icon' => 'layui-icon-component',
		'parent' => 15,
		'url' => '/admin/product/index',
		'level' => 3,
		'orderby' => 40
		),
	41 => Array
		(
		'id' => 41,
		'name' => '商品类别',
		'icon' => 'layui-icon-component',
		'parent' => 15,
		'url' => '/admin/itemCls/index',
		'level' => 3,
		'orderby' => 41
		),
	42 => Array
		(
		'id' => 42,
		'name' => '商品品牌',
		'icon' => 'layui-icon-component',
		'parent' => 15,
		'url' => '/admin/Basecode/pplist',
		'level' => 3,
		'orderby' => 42
		),
	43 => Array
		(
		'id' => 43,
		'name' => '组合商品',
		'icon' => 'layui-icon-component',
		'parent' => 16,
		'url' => '/admin/product/comblist',
		'level' => 3,
		'orderby' => 43
		),
	44 => Array
		(
		'id' => 44,
		'name' => '供应商档案',
		'icon' => 'layui-icon-component',
		'parent' => 17,
		'url' => '/admin/supcust/index',
		'level' => 3,
		'orderby' => 44
		),
	45 => Array
		(
		'id' => 45,
		'name' => '采购订单',
		'icon' => 'layui-icon-component',
		'parent' => 18,
		'url' => '/admin/pmsheet/po/index',
		'level' => 3,
		'orderby' => 45
		),
	46 => Array
		(
		'id' => 46,
		'name' => '采购收货',
		'icon' => 'layui-icon-component',
		'parent' => 18,
		'url' => '/admin/pmsheet/pi/index',
		'level' => 3,
		'orderby' => 46
		),
	49 => Array
		(
		'id' => 49,
		'name' => '登记POS机',
		'icon' => 'layui-icon-component',
		'parent' => 20,
		'url' => '/admin/pos/posno/index',
		'level' => 3,
		'orderby' => 49
		),
	50 => Array
		(
		'id' => 50,
		'name' => '营业员',
		'icon' => 'layui-icon-component',
		'parent' => 20,
		'url' => '/admin/pos/operator/index',
		'level' => 3,
		'orderby' => 50
		),
	51 => Array
		(
		'id' => 51,
		'name' => '销售流水',
		'icon' => 'layui-icon-component',
		'parent' => 21,
		'url' => '/admin/pos/saleflow/index',
		'level' => 3,
		'orderby' => 51
		),
	52 => Array
		(
		'id' => 52,
		'name' => '收银流水',
		'icon' => 'layui-icon-component',
		'parent' => 21,
		'url' => '/admin/pos/payflow/index',
		'level' => 3,
		'orderby' => 52
		),
	53 => Array
		(
		'id' => 53,
		'name' => '收银员对账',
		'icon' => 'layui-icon-component',
		'parent' => 21,
		'url' => '/admin/pos/payflow/reconciliation',
		'level' => 3,
		'orderby' => 53
		),
	54 => Array
		(
		'id' => 54,
		'name' => '收银日报',
		'icon' => 'layui-icon-component',
		'parent' => 21,
		'url' => '/admin/pos/payFlow/Dayreport',
		'level' => 3,
		'orderby' => 54
		),
	55 => Array
		(
		'id' => 55,
		'name' => '销售汇总',
		'icon' => 'layui-icon-component',
		'parent' => 21,
		'url' => '/admin/pos/saleFlow/Summarylist',
		'level' => 3,
		'orderby' => 55
		),
	56 => Array
		(
		'id' => 56,
		'name' => '批发客户管理',
		'icon' => 'layui-icon-component',
		'parent' => 22,
		'url' => '/admin/Wholesale/Index/index',
		'level' => 3,
		'orderby' => 56
		),
	60 => Array
		(
		'id' => 60,
		'name' => '盘点批号申请',
		'icon' => 'layui-icon-component',
		'parent' => 24,
		'url' => '/admin/check/Pdsheet/index',
		'level' => 3,
		'orderby' => 60
		),
	61 => Array
		(
		'id' => 61,
		'name' => '库存盘点',
		'icon' => 'layui-icon-component',
		'parent' => 24,
		'url' => '/admin/check/Crsheet/index',
		'level' => 3,
		'orderby' => 61
		),
	62 => Array
		(
		'id' => 62,
		'name' => '差异处理',
		'icon' => 'layui-icon-component',
		'parent' => 24,
		'url' => '/admin/check/Crsheet/dfindex',
		'level' => 3,
		'orderby' => 62
		),
	63 => Array
		(
		'id' => 63,
		'name' => '库存调整单',
		'icon' => 'layui-icon-component',
		'parent' => 25,
		'url' => '/admin/imsheet/Stocksheet/index',
		'level' => 3,
		'orderby' => 63
		),
	64 => Array
		(
		'id' => 64,
		'name' => '库存查询',
		'icon' => 'layui-icon-component',
		'parent' => 26,
		'url' => '/admin/stock/search/index',
		'level' => 3,
		'orderby' => 64
		),
	28 => Array
		(
		'id' => 28,
		'name' => '客户结算',
		'icon' => 'layui-icon-component',
		'parent' => 5,
		'url' => '',
		'level' => 2,
		'orderby' => 67
		),
	68 => Array
		(
		'id' => 68,
		'name' => '客户结算',
		'icon' => 'layui-icon-component',
		'parent' => 28,
		'url' => '/admin/fmsheet/rpsheet/index?TransNo=RP',
		'level' => 3,
		'orderby' => 68
		),
	75 => Array
		(
		'id' => 75,
		'name' => '日      结',
		'icon' => 'layui-icon-component',
		'parent' => 31,
		'url' => '/admin/report/Daylist',
		'level' => 3,
		'orderby' => 75
		),
	76 => Array
		(
		'id' => 76,
		'name' => '角色管理',
		'icon' => 'layui-icon-component',
		'parent' => 32,
		'url' => '/admin/Operator',
		'level' => 3,
		'orderby' => 76
		),
	77 => Array
		(
		'id' => 77,
		'name' => '用户管理',
		'icon' => 'layui-icon-component',
		'parent' => 32,
		'url' => '/admin/manager',
		'level' => 3,
		'orderby' => 77
		),
	78 => Array
		(
		'id' => 78,
		'name' => '功能管理',
		'icon' => 'layui-icon-component',
		'parent' => 32,
		'url' => '/admin/functions',
		'level' => 3,
		'orderby' => 78
		),
	79 => Array
		(
		'id' => 79,
		'name' => '新闻列表',
		'icon' => 'layui-icon-component',
		'parent' => 34,
		'url' => '/admin/news/index',
		'level' => 3,
		'orderby' => 79
		),
	81 => Array
		(
		'id' => 81,
		'name' => '图片压缩',
		'icon' => 'layui-icon-component',
		'parent' => 35,
		'url' => '/admin/manager/compressImg',
		'level' => 3,
		'orderby' => 81
		),
	82 => Array
		(
		'id' => 82,
		'name' => '清理缓存',
		'icon' => 'layui-icon-component',
		'parent' => 36,
		'url' => '/admin/manager/cleanup',
		'level' => 3,
		'orderby' => 82
		),
	83 => Array
		(
		'id' => 83,
		'name' => '报 损 单',
		'icon' => 'layui-icon-component',
		'parent' => 25,
		'url' => '/admin/imsheet/Josheet',
		'level' => 3,
		'orderby' => 83
		),
	84 => Array
		(
		'id' => 84,
		'name' => '商品调价',
		'icon' => 'layui-icon-component',
		'parent' => 16,
		'url' => '/admin/pcprice/PXFlow',
		'level' => 3,
		'orderby' => 84
		),
	88 => Array
		(
		'id' => 88,
		'name' => '库存异常告警',
		'icon' => 'layui-icon-component',
		'parent' => 26,
		'url' => '/admin/stock/warning/index',
		'level' => 3,
		'orderby' => 88
		),
	90 => Array
		(
		'id' => 90,
		'name' => '终端管理',
		'icon' => 'layui-icon-home',
		'parent' => '',
		'url' => '',
		'level' => 1,
		'orderby' => 90
		),
	91 => Array
		(
		'id' => 91,
		'name' => '内容管理',
		'icon' => 'layui-icon-component',
		'parent' => 90,
		'url' => '',
		'level' => 2,
		'orderby' => 91
		),
	92 => Array
		(
		'id' => 92,
		'name' => '栏目设置',
		'icon' => 'layui-icon-component',
		'parent' => 91,
		'url' => '/admin/portal/Channel/index',
		'level' => 3,
		'orderby' => 92
		),
	94 => Array
		(
		'id' => 94,
		'name' => '内容设置',
		'icon' => 'layui-icon-component',
		'parent' => 91,
		'url' => '/admin/portal/Content/index',
		'level' => 3,
		'orderby' => 94
		),
	95 => Array
		(
		'id' => 95,
		'name' => '广告管理',
		'icon' => 'layui-icon-component',
		'parent' => 90,
		'url' => '',
		'level' => 2,
		'orderby' => 95
		),
	96 => Array
		(
		'id' => 96,
		'name' => '广告列表',
		'icon' => 'layui-icon-component',
		'parent' => 95,
		'url' => '/admin/portal/Ad/Index',
		'level' => 3,
		'orderby' => 96
		),
	97 => Array
		(
		'id' => 97,
		'name' => '广告位设置',
		'icon' => 'layui-icon-component',
		'parent' => 95,
		'url' => '/admin/portal/Adspace/Index',
		'level' => 3,
		'orderby' => 97
		),
	98 => Array
		(
		'id' => 98,
		'name' => '留言管理',
		'icon' => 'layui-icon-component',
		'parent' => 90,
		'url' => '',
		'level' => 2,
		'orderby' => 98
		),
	99 => Array
		(
		'id' => 99,
		'name' => '留言管理',
		'icon' => 'layui-icon-component',
		'parent' => 98,
		'url' => '/admin/portal/Guestbook/index',
		'level' => 3,
		'orderby' => 99
		),
	100 => Array
		(
		'id' => 100,
		'name' => '留言类别',
		'icon' => 'layui-icon-component',
		'parent' => 98,
		'url' => '/admin/portal/Guestbookctg/index',
		'level' => 3,
		'orderby' => 100
		),
	103 => Array
		(
		'id' => 103,
		'name' => '首页数据',
		'icon' => 'layui-icon-component',
		'parent' => 102,
		'url' => '',
		'level' => 2,
		'orderby' => 103
		),
	137 => Array
		(
		'id' => 137,
		'name' => '收货明细',
		'icon' => 'layui-icon-component',
		'parent' => 31,
		'url' => '/admin/report/reveive',
		'level' => 3,
		'orderby' => 107
		),
	138 => Array
		(
		'id' => 138,
		'name' => '积分方案',
		'icon' => '',
		'parent' => 19,
		'url' => '/admin/integral/index',
		'level' => 3,
		'orderby' => 107
		),
	115 => Array
		(
		'id' => 115,
		'name' => '订单报表',
		'icon' => 'layui-icon-component',
		'parent' => 11,
		'url' => '',
		'level' => 2,
		'orderby' => 115
		),
	116 => Array
		(
		'id' => 116,
		'name' => '库存报表',
		'icon' => 'layui-icon-component',
		'parent' => 11,
		'url' => '',
		'level' => 2,
		'orderby' => 116
		),
	117 => Array
		(
		'id' => 117,
		'name' => '门店销售',
		'icon' => 'layui-icon-component',
		'parent' => 115,
		'url' => '/admin/report/storeSales',
		'level' => 3,
		'orderby' => 117
		),
	118 => Array
		(
		'id' => 118,
		'name' => '单品销售',
		'icon' => 'layui-icon-component',
		'parent' => 115,
		'url' => '/admin/report/goodsSales',
		'level' => 3,
		'orderby' => 118
		),
	119 => Array
		(
		'id' => 119,
		'name' => '单品库存',
		'icon' => 'layui-icon-component',
		'parent' => 116,
		'url' => '/admin/stock/Singlestock/Index',
		'level' => 3,
		'orderby' => 119
		),
	130 => Array
		(
		'id' => 130,
		'name' => '零库存',
		'icon' => 'layui-icon-component',
		'parent' => 116,
		'url' => '/admin/stock/Singlestock/Zerostock',
		'level' => 3,
		'orderby' => 130
		),
	132 => Array
		(
		'id' => 132,
		'name' => '分店要货',
		'icon' => 'layui-icon-component',
		'parent' => 18,
		'url' => '/admin/pmsheet/Yhsheet/index',
		'level' => 3,
		'orderby' => 132
		)
	)
?>