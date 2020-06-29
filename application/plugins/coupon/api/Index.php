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
namespace app\plugins\coupon\api;

use app\plugins\coupon\api\Common;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\CouponService;

/**
 * 优惠劵
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * [__construct 构造方法]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-03T12:39:08+0800
     */
    public function __construct()
    {
        // 调用父类前置方法
        parent::__construct();
    }

    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-10-15
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 获取基础配置信息
        $base = BaseService::BaseConfig();

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

        // 返回数据
        $result = [
            'base'  => $base['data'],
            'data'  => $ret['data'],
        ];
        return DataReturn('处理成功', 0, $result);
    }

    /**
     * 商品详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-10-17
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Goods($params = [])
    {
        // 参数
        if(empty($params['goods_id']))
        {
            return DataReturn('商品id为空', -1);
        }

        // 获取基础配置信息
        $base = BaseService::BaseConfig();

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

        // 排除商品不支持的活动
        if(!empty($ret['data']))
        {
            $ret['data'] = BaseService::CouponListGoodsExclude(['data'=>$ret['data'], 'goods_id'=>intval($params['goods_id'])]);
        }

        // 返回数据
        $result = [
            'base'  => $base['data'],
            'data'  => $ret['data'],
        ];
        return DataReturn('处理成功', 0, $result);
    }
}
?>