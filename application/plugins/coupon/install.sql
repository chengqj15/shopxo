# 优惠劵表
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_coupon` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` char(30) NOT NULL DEFAULT '' COMMENT '名称',
  `desc` char(60) NOT NULL DEFAULT '' COMMENT '描述',
  `bg_color` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '优惠券颜色（0红色, 1紫色, 2黄色, 3蓝色, 4橙色, 5绿色, 6咖啡色）',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '优惠券类型（0满减劵, 1折扣劵）',
  `discount_value` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '减免金额 | 折扣系数 0-10，9.5代表9.5折，0或空代表无折扣',
  `expire_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '优惠券类型（0领取生效, 1固定日期）',
  `expire_hour` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '有效小时（单位 时）',
  `fixed_time_start` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '限时开始时间',
  `fixed_time_end` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '限时结束时间',
  `where_order_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订单最低金额',
  `use_limit_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '使用限制（0不限, 1商品分类, 2商品）',
  `use_value_ids` text COMMENT '关联商品分类id 或 关联商品id（以json存储）',
  `is_paid` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否购买（0否，1是）',
  `buy_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '购买金额 | 0或空代表不需要付费购买',
  `limit_send_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '限制发放总数量（0则不限）',
  `already_send_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已发放总数量（0则不限）',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用（0否，1是）',
  `is_user_receive` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否开放用户领取（0否, 1是）',
  `is_regster_send` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否注册即发放（0否, 1是）',  
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `is_enable` (`is_enable`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='优惠劵 - 应用';

alter table s_plugins_coupon add column `buy_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '购买金额 | 0或空代表不需要付费购买' after `use_value_ids`;
alter table s_plugins_coupon add column `is_paid` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否购买（0否，1是）' after `use_value_ids`;
alter table s_plugins_coupon_user add column `buy_order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '购买的订单id' after `use_order_id`;
alter table s_plugins_coupon_user modify column `coupon_code` varchar(20) NOT NULL DEFAULT '0' COMMENT '优惠券序列号' after `coupon_id`;
alter table s_plugins_coupon_user add UNIQUE index `coupon_code`(`coupon_code`);

alter table s_plugins_coupon add column `use_count_limit` int(11) NOT NULL DEFAULT '1' COMMENT '使用次数限制' after `already_send_count`;
alter table s_plugins_coupon add column `use_count` int(11) NOT NULL DEFAULT '0' COMMENT '使用次数' after `already_send_count`;
alter table s_plugins_coupon_user add column `use_count_limit` int(11) NOT NULL DEFAULT '1' COMMENT '使用次数限制' after `is_use`;
alter table s_plugins_coupon_user add column `use_count` int(11) NOT NULL DEFAULT '0' COMMENT '使用次数' after `is_use`;

alter table s_plugins_coupon add column `coupon_code` varchar(20) NOT NULL DEFAULT '0' COMMENT '优惠券序列号' after `id`;
alter table s_plugins_coupon add UNIQUE index `coupon_code`(`coupon_code`);
alter table s_plugins_coupon add column `is_popup` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否弹窗（0否，1是）' after `is_regster_send`;

# 用户优惠劵表
CREATE TABLE IF NOT EXISTS `{PREFIX}plugins_coupon_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `coupon_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '优惠劵id',
  `coupon_code` varchar(20) NOT NULL DEFAULT '0' COMMENT '优惠劵code',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `time_start` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '有效开始时间',
  `time_end` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '有效结束时间',
  `is_valid` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有效（0否，1是）',
  `is_expire` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已过期（0否，1是）',
  `is_use` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已使用（0否，1是）',
  `use_order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用关联的订单id',
  `buy_order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '购买的订单id',
  `use_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用时间',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `user_id` (`user_id`),
  KEY `is_valid` (`is_valid`)
) ENGINE=InnoDB DEFAULT CHARSET={CHARSET} ROW_FORMAT=DYNAMIC COMMENT='用户优惠劵 - 应用';