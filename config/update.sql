INSERT INTO `s_config` VALUES (null, '10', '服务费比率', '服务费比率', '订单金额*服务费比率%', 'admin', 'common_order_service_fee_ratio', '1580643823'),

INSERT INTO `s_config` VALUES (null, '10', '服务费上限', '服务费上限', '服务费上限', 'admin', 'common_order_service_fee_upper_limit', '1580643823'),

INSERT INTO `s_config` VALUES (null, '3', '自提日期', '允许多少天内自提', '自提日期', 'admin', 'common_self_extraction_days', '1580643823');

INSERT INTO `s_config` VALUES (null, '{"Sun": "11:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00", "Mon": "11:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00","Tues": "11:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00","Wed": "11:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00","Thur": "11:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00","Fri": "11:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00","Sat": "11:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00"}', '自提时间', '允许自提时间段', '自提时间', 'admin', 'common_self_extraction_config', '1580643823');

INSERT INTO `s_config` VALUES (null, '11:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00', '自提时间', '允许自提时间段', '自提时间', 'admin', 'common_self_extraction_hours', '1580643823');

INSERT INTO `s_config` VALUES (null, '30', '退货时间限制', '可退货时间限制（单位：分钟）', '可退货时间限制', 'admin', 'home_order_aftersale_time_limit', '1580643823');



alter table s_order_address add column `contact_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '取件联系人地址id' after tel;
alter table s_order_address add column `contact_name` char(60) NOT NULL DEFAULT '' COMMENT '取件人姓名' after tel;
alter table s_order_address add column `contact_tel` char(15) NOT NULL DEFAULT '' COMMENT '取件人-电话' after tel;
alter table s_order_address add column `target_date` char(24) NOT NULL DEFAULT '' COMMENT '取件时间' after tel;

alter table s_order add column `biz_id` int NOT NULL DEFAULT 1 COMMENT '业务id' after id;


--
-- 表的结构 `eb_user_level`
--
CREATE TABLE IF NOT EXISTS `s_user_level_info` (
  `id` int(11) NOT NULL DEFAULT '0' COMMENT '用户uid',
  `level_no` varchar(24) NOT NULL DEFAULT '0' COMMENT '会员序号',
  `level_id` int(11) NOT NULL DEFAULT '0' COMMENT '等级vip',
  `grade` int(11) NOT NULL DEFAULT '0' COMMENT '会员等级',
  `begin_time` int(11) NOT NULL DEFAULT '0' COMMENT '获得时间',
  `valid_time` int(11) NOT NULL DEFAULT '0' COMMENT '过期时间',
  `level_value` int(11) NOT NULL DEFAULT '0' COMMENT '当前成长值',
  `level_money` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '购买金额',
  `level_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=购买,0=成长值',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:禁止,1:正常',
  `mark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `remind` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已通知',
  `is_delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '是否已删除（0否, 大于0删除时间）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户等级信息表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `s_user_level_value_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户uid',
  `delta_value` int(11) NOT NULL DEFAULT '0' COMMENT '变动值',
  `orginal_value` int(11) NOT NULL DEFAULT '0' COMMENT '原有值',
  `level_value` int(11) NOT NULL DEFAULT '0' COMMENT '当前成长值',
  `delta_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '变动类型。1=购买,0=成长值',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '购买订单id',
  `mark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `is_delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '是否已删除（0否, 大于0删除时间）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户成长值信息表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `s_user_level_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户uid',
  `level_id` int(11) NOT NULL DEFAULT '0' COMMENT '等级vip',
  `grade` int(11) NOT NULL DEFAULT '0' COMMENT '会员等级',
  `origin_level_id` int(11) NOT NULL DEFAULT '0' COMMENT '原等级id',
  `origin_grade` int(1) NOT NULL DEFAULT '0' COMMENT '原等级',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=购买,0=成长值',
  `mark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '购买订单id',
  `is_delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '是否已删除（0否, 大于0删除时间）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户等级记录表' AUTO_INCREMENT=1 ;

--
-- 表的结构 `eb_user_task_finish`
--

CREATE TABLE IF NOT EXISTS `s_user_task_finish` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL DEFAULT '0' COMMENT '任务id',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有效',
  `is_delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '是否已删除（0否, 大于0删除时间）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户任务完成记录表' AUTO_INCREMENT=1 ;

--
-- 表的结构 `eb_system_user_level`
--

CREATE TABLE IF NOT EXISTS `s_system_user_level` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '会员名称',
  `money` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '购买金额',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '积分兑换', 
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '成长值', 
  `valid_date` int(11) NOT NULL DEFAULT '0' COMMENT '有效时间',
  `valid_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=年;2=月;0=天',
  `is_forever` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为永久会员',
  `is_pay` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否购买,2=现金或积分,1=现金,0=不购买',
  `is_show` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示 1=显示,0=隐藏',
  `grade` int(11) NOT NULL DEFAULT '0' COMMENT '会员等级',
  `discount` int(11) NOT NULL DEFAULT '0' COMMENT '享受折扣',
  `free_package_count` int(11) NOT NULL DEFAULT '0.00' COMMENT '免打包费次数',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '会员卡背景',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '会员图标',
  `explain` text NOT NULL COMMENT '说明',
  `is_delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '是否已删除（0否, 大于0删除时间）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='设置用户等级表' AUTO_INCREMENT=1 ;

alter table s_system_user_level add column `integral` int(11) NOT NULL DEFAULT '0' COMMENT '积分兑换' after `money`;
alter table s_system_user_level add column `point` int(11) NOT NULL DEFAULT '0' COMMENT '成长值' after `integral`;
alter table s_system_user_level add column `valid_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=年;2=月;0=天' after valid_date;
alter table s_system_user_level add column `free_package_count` int(11) NOT NULL DEFAULT '0.00' COMMENT '免打包费次数' after discount;

alter table s_order add column `service_fee_free` tinyint(1) NOT NULL DEFAULT '0' COMMENT '免打包费' after `service_fee`;

--
-- 表的结构 `eb_system_user_task`
--

CREATE TABLE IF NOT EXISTS `s_system_user_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `real_name` varchar(255) NOT NULL DEFAULT '' COMMENT '配置原名',
  `task_type` varchar(50) NOT NULL DEFAULT '' COMMENT '任务类型',
  `number` int(11) NOT NULL DEFAULT '0' COMMENT '限定数',
  `level_id` int(11) NOT NULL DEFAULT '0' COMMENT '等级id',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_show` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示',
  `is_must` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否务必达成任务,1务必达成,0=满足其一',
  `illustrate` varchar(255) NOT NULL DEFAULT '' COMMENT '任务说明',
  `is_delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '是否已删除（0否, 大于0删除时间）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='等级任务设置' AUTO_INCREMENT=1 ;

alter table `s_order_detail` add column `discount_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折前价格' after `original_price`;
alter table `s_order_detail` add column `discount_total_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折前价格' after `original_price`;

alter table `s_order_detail` add column `deliver_number` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发货数量' after `buy_number`;
update `s_order_detail` set deliver_number=buy_number;
update `s_order_detail` set before_discount_price=price;

alter table `s_order` add column `out_of_stock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '缺货处理' after `order_model`;
alter table `s_order` add column `is_under_line` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否线下' after `order_model`;


alter table `s_goods` add column `is_new` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '是否新品' after `is_home_recommended`;
alter table `s_goods` add column `is_hot` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '是否热销' after `is_home_recommended`;


alter table `s_payment` add column `display_msg` text COMMENT '说明' after `sort`;
alter table `s_payment` add column `display_img` text COMMENT '图片说明' after `sort`;
alter table `s_plugins_coupon` add column `display_img` text COMMENT '图片说明' after `sort`;

