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
namespace app\plugins\coupon;

use think\Db;
use think\Controller;
use app\service\UserService;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\CouponService;
use app\plugins\coupon\service\UserCouponService;

/**
 * 优惠劵 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-11T21:51:08+0800
 */
class Hook extends Controller
{
    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-11T14:25:44+0800
     * @param    [array]          $params [输入参数]
     */
    public function run($params = [])
    {
        // 后端访问不处理
        if(isset($params['params']['is_admin_access']) && $params['params']['is_admin_access'] == 1)
        {
            return DataReturn('无需处理', 0);
        }

        // 钩子名称
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器/方法
            $module_name = strtolower(request()->module());
            $controller_name = strtolower(request()->controller());
            $action_name = strtolower(request()->action());

            $ret = '';
            $coupon_style = ['indexgoodsindex', 'indexbuyindex'];
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    if(in_array($module_name.$controller_name.$action_name, $coupon_style))
                    {
                        $ret = __MY_ROOT_PUBLIC__.'static/plugins/css/coupon/index/common.css';
                    }
                    break;

                // 公共js
                case 'plugins_js' :
                    if(in_array($module_name.$controller_name.$action_name, $coupon_style))
                    {
                        $ret = __MY_ROOT_PUBLIC__.'static/plugins/js/coupon/index/common.js';
                    }
                    break;

                // 在前面添加导航
                case 'plugins_service_navigation_header_handle' :
                    $ret = $this->NavigationHeaderHandle($params);
                    break;

                    // 用户中心左侧导航
                case 'plugins_service_users_center_left_menu_handle' :
                    $ret = $this->UserCenterLeftMenuHandle($params);
                    break;

                // 顶部小导航右侧-我的商城
                case 'plugins_service_header_navigation_top_right_handle' :
                    $ret = $this->CommonTopNavRightMenuHandle($params);
                    break;

                // 商品详情
                case 'plugins_view_goods_detail_panel_bottom' :
                    $ret = $this->GoodsDetailCoupinView($params);
                    break;

                // 购买确认页面优惠劵选择
                case 'plugins_view_buy_goods_bottom' :
                    $ret = $this->BuyCoupinView($params);
                    break;

                // 购买订单优惠处理
                case 'plugins_service_buy_handle' :
                    $ret = $this->BuyDiscountCalculate($params);
                    break;

                // 购买提交订单页面隐藏域html
                case 'plugins_view_buy_form_inside' :
                    $coupon_id = (isset($params['params']) && isset($params['params']['coupon_id'])) ? $params['params']['coupon_id'] : 0;
                    $ret = '<input type="hidden" name="coupon_id" value="'.$coupon_id.'" />';
                    break;

                // 订单添加成功处理
                case 'plugins_service_buy_order_insert_success' :
                    $ret = $this->OrderInsertSuccessHandle($params);
                    break;

                // 订单状态改变处理
                case 'plugins_service_order_status_change_history_success_handle' :
                    $ret = $this->OrderInvalidHandle($params);
                    break;

                // 注册送优惠劵
                case 'plugins_service_user_register_end' :
                    $ret = $this->UserRegisterGiveHandle($params);
                    break;

                default :
                    $ret = '';
            }
            return $ret;
        } else {
            return '';
        }
    }

    /**
     * 注册送劵
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function UserRegisterGiveHandle($params = [])
    {
        if(!empty($params['user_id']))
        {
            UserCouponService::UserRegisterGive($params['user_id']);
        }
    }

    /**
     * 订单状态改变处理,状态为取消|关闭时释放优惠劵
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function OrderInvalidHandle($params = [])
    {
        if(!empty($params['data']) && isset($params['data']['new_status']) && in_array($params['data']['new_status'], [5,6]) && !empty($params['order_id']))
        {
            // 释放用户优惠劵
            UserCouponService::UserCouponUseStatusUpdate(Db::name('Order')->where(['id'=>intval($params['order_id'])])->value('extension_data'), 0, 0);
        }
    }

    /**
     * 订单添加成功处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function OrderInsertSuccessHandle($params = [])
    {
        if(!empty($params['order']) && !empty($params['order']['extension_data']) && !empty($params['order_id']))
        {
            // 更新优惠劵使用状态
            UserCouponService::UserCouponUseStatusUpdate($params['order']['extension_data'], 1, $params['order_id']);
        }
    }

    /**
     * 满减计算
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BuyDiscountCalculate($params = [])
    {
        $ret = $this->BuyUserCouponData($params);
        if(!empty($ret['coupon_choice']) && !empty($ret['coupon_choice']['buy_goods_ids']))
        {
            // 优惠劵是否限定, 则读取优惠劵可用商品id重新计算
            $order_price = 0.00;
            if($ret['coupon_choice']['coupon']['use_limit_type'] > 0)
            {
                foreach($params['data']['goods'] as $v)
                {
                    if(in_array($v['goods_id'], $ret['coupon_choice']['buy_goods_ids']))
                    {
                        $order_price += $v['total_price'];
                    }
                }
            } else {
                $order_price = $params['data']['base']['actual_price'];
            }
            if($order_price > 0)
            {
                $discount_price = BaseService::PriceCalculate($order_price, $ret['coupon_choice']['coupon']['type'], $ret['coupon_choice']['coupon']['where_order_price'], $ret['coupon_choice']['coupon']['discount_value']);

                if($discount_price > 0)
                {
                    // 扩展展示数据
                    $title = ($ret['coupon_choice']['coupon']['type'] == 0) ? '优惠劵' : '折扣劵';
                    $params['data']['extension_data'][] = [
                        'name'      => $title.'-'.$ret['coupon_choice']['coupon']['name'],
                        'price'     => $discount_price,
                        'type'      => 0,
                        'tips'      => '-'.config('shopxo.price_symbol').$discount_price,
                        'business'  => 'plugins-coupon',
                        'ext'       => $ret['coupon_choice'],
                    ];

                    // 金额
                    $params['data']['base']['preferential_price'] += $discount_price;
                    $params['data']['base']['actual_price'] -= $discount_price;

                    return DataReturn('处理成功', 0);
                }
            }
        }
        return DataReturn('无需处理', 0);
    }

    /**
     * 购买确认页面优惠劵选择
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function BuyCoupinView($params = [])
    {
        $ret = $this->BuyUserCouponData($params);
        $this->assign('coupon_choice', $ret['coupon_choice']);
        $this->assign('coupon_list', $ret['coupon_list']);
        $this->assign('params', $params['params']);
        return $this->fetch('../../../plugins/view/coupon/index/public/buy');
    }

    /**
     * 下订单用户优惠劵信息获取
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function BuyUserCouponData($params = [])
    {
        // 当前选中的优惠劵id
        $coupon_id = (!empty($params['params']) && isset($params['params']['coupon_id'])) ? intval($params['params']['coupon_id']) : 0;

        // 获取当前购买可选择的优惠劵数据
        return BaseService::BuyUserCouponData(['order_goods'=>$params['data']['goods'], 'coupon_id'=>$coupon_id]);
    }

    /**
     * 商品详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsDetailCoupinView($params = [])
    {
        // 优惠劵列表
        $coupon_params = [
            'where'             => [
                'is_enable'         => 1,
                'is_user_receive'   => 1,
            ],
            'm'                 => 0,
            'n'                 => 0,
            'is_sure_receive'   => 1,
            'user'              => UserService::LoginUserInfo(),
        ];
        $ret = CouponService::CouponList($coupon_params);
        if(!empty($ret['data']))
        {
            // 排除商品不支持的活动
            $ret['data'] = BaseService::CouponListGoodsExclude(['data'=>$ret['data'], 'goods_id'=>$params['goods_id']]);
        }
        $this->assign('coupon_list', $ret['data']);
        return $this->fetch('../../../plugins/view/coupon/index/public/goods_detail_panel');
    }

    /**
     * 中间大导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function NavigationHeaderHandle($params = [])
    {
        if(is_array($params['header']))
        {
            // 获取应用数据
            $base = BaseService::BaseConfig();
            if($base['code'] == 0 && !empty($base['data']['application_name']))
            {
                $nav = [
                    'id'                    => 0,
                    'pid'                   => 0,
                    'name'                  => $base['data']['application_name'],
                    'url'                   => PluginsHomeUrl('coupon', 'index', 'index'),
                    'data_type'             => 'custom',
                    'is_show'               => 1,
                    'is_new_window_open'    => 0,
                    'items'                 => [],
                ];
                array_unshift($params['header'], $nav);
            }
        }
    }

    /**
     * 用户中心左侧菜单处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function UserCenterLeftMenuHandle($params = [])
    {
        $params['data']['property']['item'][] = [
            'name'      =>  '我的卡劵',
            'url'       =>  PluginsHomeUrl('coupon', 'coupon', 'index'),
            'contains'  =>  ['couponindex'],
            'is_show'   =>  1,
            'icon'      =>  'am-icon-gift',
        ];
    }

    /**
     * 顶部小导航右侧-我的商城
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function CommonTopNavRightMenuHandle($params = [])
    {
        array_push($params['data'][1]['items'], [
            'name'  => '我的卡劵',
            'url'   => PluginsHomeUrl('coupon', 'coupon', 'index'),
        ]);
    }
}
?>