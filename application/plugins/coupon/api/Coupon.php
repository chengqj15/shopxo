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

use app\service\SeoService;
use app\plugins\coupon\api\Common;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\CouponService;
use app\plugins\coupon\service\UserCouponService;
use app\plugins\coupon\service\UserCouponAdminService;

/**
 * 用户优惠券
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
     * 列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2019-08-12
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        $coupon_params = [
            'user'  => $this->user,
            'where' => [
                'user_id'   => $this->user['id'],
                'is_valid'  => 1,
            ],
        ];
        return UserCouponService::CouponUserList($coupon_params);
    }

    /**
     * 领取优惠劵
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-10-15
     * @desc    description
     */
    public function Receive()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 领取优惠劵
        return CouponService::UserReceiveCoupon($this->data_post);
    }

    /**
     * 购买
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-10-17
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Buy($params = [])
    {
        // 调用服务层
        return DataReturn('操作成功', 0, BaseService::BuyUserCouponData($params));
    }

    public function detail($params = [])
    {
        // 优惠劵保存
        $coupon_params = [
            'where' => [
                'id' => $params['id'],
                'user_id'   => $this->user['id'],
                'is_valid'  => 1,
            ],
        ];
        $data = UserCouponService::UserCouponDetail($coupon_params);
        if(empty($data)){
            return DataReturn('invalid coupon id', -1); 
        }

        $coupon_params = [
            'where' => [
                'id' => $data['coupon_id'],           
            ],
        ];
        $coupon_data = CouponService::CouponDetail($coupon_params);
        if(empty($coupon_data)){
            return DataReturn('invalid coupon', -1); 
        }

        // $images = MyUrl('index/qrcode/barcode', ['content'=>urlencode(base64_encode($data['coupon_code']))]);
        $images = (new \base\Qrcode())->BarcodeView(['content'=>urlencode(base64_encode($coupon_data['coupon_code']))]);
        $data['images'] = $images;
        $data['coupon_barcode'] = $coupon_data['coupon_code'];
        $data['msg'] = '请向收银员出示此核销码';
        return DataReturn('success', 0, $data); 
    }

    public function Verify($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }
        $coupon_params = [
            'where' => [
                'id' => $params['id'],
                'user_id'   => $this->user['id'],
                'is_valid'  => 1,
            ],
        ];
        $data = UserCouponService::UserCouponDetail($coupon_params);
        if(empty($data)){
            return DataReturn('invalid coupon id', -1); 
        }

        return UserCouponAdminService::UserCouponVerify($params);
    }

}
?>