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
namespace app\plugins\coupon\index;

use app\service\UserService;
use app\service\SeoService;
use app\plugins\coupon\index\Common;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\CouponService;
use app\plugins\coupon\service\UserCouponService;

/**
 * 优惠劵 - 优惠劵首页
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-11T15:36:19+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 基础配置
        $base = BaseService::BaseConfig();
        $this->assign('plugins_base', $base['data']);

        // 优惠劵列表
        $coupon_params = [
            'where'             => [
                'is_enable'         => 1,
                'is_user_receive'   => 1,
            ],
            'm'                 => 0,
            'n'                 => 0,
            'is_sure_receive'   => 1,
            'user'              => $this->user,
        ];
        $ret = CouponService::CouponList($coupon_params);
        $this->assign('coupon_list', $ret['data']);

        // 浏览器名称
        if(!empty($base['data']['application_name']))
        {
            $this->assign('home_seo_site_title', SeoService::BrowserSeoTitle($base['data']['application_name'], 1));
        }

        $this->assign('params', $params);
        return $this->fetch('../../../plugins/view/coupon/index/index/index');
    }

    /**
     * 领取优惠劵
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-11T15:36:19+0800
     * @param    [array]          $params [输入参数]
     */
    public function receive($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 领取优惠劵
        return CouponService::UserReceiveCoupon($params);
    }

    /**
     * 优惠劵过期处理
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-18T16:57:38+0800
     * @param    [array]          $params [输入参数]
     */
    public function expire($params = [])
    {
        $ret = UserCouponService::CouponUserExpireHandle();
        return 'success:'.$ret['data'];
    }
}
?>