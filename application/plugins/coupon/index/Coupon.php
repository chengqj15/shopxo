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

use app\service\SeoService;
use app\plugins\coupon\index\Common;
use app\plugins\coupon\service\UserCouponService;

/**
 * 优惠劵 - 用户优惠劵
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Coupon extends Common
{
    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     */
    public function __construct()
    {
        parent::__construct();

        // 是否登录
        $this->IsLogin();
    }

    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * 2019-08-12
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 优惠劵列表
        $coupon_params = [
            'user'  => $this->user,
            'where' => [
                'user_id'   => $this->user['id'],
                'is_valid'  => 1,
            ],
        ];
        $ret = UserCouponService::CouponUserList($coupon_params);
        $this->assign('coupon_list', $ret['data']);

        // 浏览器名称
        $this->assign('home_seo_site_title', SeoService::BrowserSeoTitle('我的卡劵', 1));

        $this->assign('params', $params);
        return $this->fetch('../../../plugins/view/coupon/index/coupon/index');
    }
}
?>