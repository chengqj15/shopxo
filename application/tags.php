<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2019 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------

// 应用行为扩展定义文件
return array (
  'app_init' => 
  array (
  ),
  'app_begin' => 
  array (
  ),
  'module_init' => 
  array (
  ),
  'action_begin' => 
  array (
  ),
  'view_filter' => 
  array (
  ),
  'app_end' => 
  array (
  ),
  'log_write' => 
  array (
  ),
  'plugins_css' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
    1 => 'app\\plugins\\share\\Hook',
    2 => 'app\\plugins\\multilingual\\Hook',
    3 => 'app\\plugins\\freightfee\\Hook',
  ),
  'plugins_js' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
    1 => 'app\\plugins\\share\\Hook',
    2 => 'app\\plugins\\multilingual\\Hook',
  ),
  'plugins_service_navigation_header_handle' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
  ),
  'plugins_service_users_center_left_menu_handle' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
  ),
  'plugins_service_header_navigation_top_right_handle' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
  ),
  'plugins_view_goods_detail_panel_bottom' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
  ),
  'plugins_view_buy_goods_bottom' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
  ),
  'plugins_service_buy_handle' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
    1 => 'app\\plugins\\freightfee\\Hook',
  ),
  'plugins_view_buy_form_inside' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
  ),
  'plugins_service_buy_order_insert_success' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
  ),
  'plugins_service_order_status_change_history_success_handle' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
  ),
  'plugins_service_user_register_end' => 
  array (
    0 => 'app\\plugins\\coupon\\Hook',
  ),
  'plugins_view_common_bottom' => 
  array (
    0 => 'app\\plugins\\share\\Hook',
    1 => 'app\\plugins\\multilingual\\Hook',
  ),
  'plugins_view_goods_detail_photo_bottom' => 
  array (
    0 => 'app\\plugins\\share\\Hook',
  ),
  'plugins_view_header_navigation_top_left' => 
  array (
    0 => 'app\\plugins\\multilingual\\Hook',
    1 => 'app\\plugins\\weixinwebauthorization\\Hook',
  ),
  'plugins_view_user_login_info_top' => 
  array (
    0 => 'app\\plugins\\weixinwebauthorization\\Hook',
  ),
  'plugins_view_user_reg_info' => 
  array (
    0 => 'app\\plugins\\weixinwebauthorization\\Hook',
  ),
  'plugins_service_users_personal_show_field_list_handle' => 
  array (
    0 => 'app\\plugins\\weixinwebauthorization\\Hook',
  ),
  'plugins_service_system_begin' => 
  array (
    0 => 'app\\plugins\\weixinwebauthorization\\Hook',
  ),
  'plugins_view_goods_detail_title' => 
  array (
    0 => 'app\\plugins\\freightfee\\Hook',
  ),
);
?>