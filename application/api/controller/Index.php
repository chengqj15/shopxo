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
namespace app\api\controller;

use app\service\GoodsService;
use app\service\BannerService;
use app\service\AppHomeNavService;
use app\service\PluginsService;
use app\service\BuyService;
use app\plugins\coupon\service\CouponService;
use app\plugins\coupon\service\BaseService;

/**
 * 首页
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
	 * [Index 入口]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  1.0.0
	 * @datetime 2018-05-25T11:03:59+0800
	 */
	public function Index()
	{
		// 返回数据
		$result = [
			'navigation'						=> AppHomeNavService::AppHomeNav(),
			'banner_list'						=> BannerService::Banner(),
			'data_list'							=> GoodsService::HomeFloorList(),
			'common_shop_notice'				=> MyC('common_shop_notice', null, true),
			'common_app_is_enable_search'		=> (int) MyC('common_app_is_enable_search', 1),
			'common_app_is_enable_answer'		=> (int) MyC('common_app_is_enable_answer', 1),
			'common_app_is_header_nav_fixed'	=> (int) MyC('common_app_is_header_nav_fixed', 0),
			'common_app_is_online_service'		=> (int) MyC('common_app_is_online_service', 0),
			'common_app_is_limitedtimediscount'	=> (int) MyC('common_app_is_limitedtimediscount'),
			'common_cart_total'                 => BuyService::UserCartTotal(['user'=>$this->user]),
		];

		// 支付宝小程序在线客服
		if(APPLICATION_CLIENT_TYPE == 'alipay')
		{
			$result['common_app_mini_alipay_tnt_inst_id'] = MyC('common_app_mini_alipay_tnt_inst_id', null, true);
			$result['common_app_mini_alipay_scene'] = MyC('common_app_mini_alipay_scene', null, true);
		}

		// 限时秒杀
		if($result['common_app_is_limitedtimediscount'] == 1)
		{
			$ret = PluginsService::PluginsControlCall(
                'limitedtimediscount', 'index', 'index', 'api');
            if($ret['code'] == 0 && isset($ret['data']['code']) && $ret['data']['code'] == 0)
            {
                $result['plugins_limitedtimediscount_data'] = $ret['data']['data'];
            }
		}

		// 首页弹窗

        

		// 返回数据
		return DataReturn('success', 0, $result);
	}

	private function fetchPopupContent($popupver)
	{
		// 是否关闭状态
		$ret = DataReturn('success', -1, '');
        if(session('plugins_popupscreen_close_status') == 1)
        {
            return $ret;
        }else{
        	// 基础配置是否正常
	        $base = PluginsService::PluginsData('popupscreen', ['images']);
	        if($base['code'] != 0)
	        {
	            return $ret;
	        }
	        if($base['data']['popupver'] <= $popupver){
	        	return $ret;
	        }
	        // 有效时间
	        $current = date('Y-m-d', time());
	        if(!empty($base['data']['time_start']))
	        {
	            // 是否已开始
	            if(date('Y-m-d', strtotime($base['data']['time_start'])) > $current)
	            {
	                return $ret;
	            }
	        }
	        if(!empty($base['data']['time_end']))
	        {
	            // 是否已结束
	            if(date('Y-m-d', strtotime($base['data']['time_end'])) < $current)
	            {
	                return $ret;
	            }
	        }
	        return DataReturn('success', 0, $base['data']);
        }
	}

	/**
	 * [Index 入口]
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  1.0.0
	 * @datetime 2018-05-25T11:03:59+0800
	 */
	public function Index2()
	{
		// 返回数据
		$new_goods = [];
		$new_goods_ret = GoodsService::HomeNewList();
		if($new_goods_ret['code'] == 0){
			$new_goods = $new_goods_ret['data'];
		}
		$hot_goods = [];
		$hot_goods_ret = GoodsService::HomeHotList();
		if($hot_goods_ret['code'] == 0){
			$hot_goods = $hot_goods_ret['data'];
		}
		$result = [
			'navigation'						=> AppHomeNavService::AppHomeNav(),
			'banner_list'						=> BannerService::Banner(),
			'new_goods'							=> $new_goods,
			'hot_goods'							=> $hot_goods,
			'common_shop_notice'				=> MyC('common_shop_notice', null, true),
			'common_app_is_enable_search'		=> (int) MyC('common_app_is_enable_search', 1),
			'common_app_is_enable_answer'		=> (int) MyC('common_app_is_enable_answer', 1),
			'common_app_is_header_nav_fixed'	=> (int) MyC('common_app_is_header_nav_fixed', 0),
			'common_app_is_online_service'		=> (int) MyC('common_app_is_online_service', 0),
			'common_app_is_limitedtimediscount'	=> (int) MyC('common_app_is_limitedtimediscount'),
			'common_cart_total'                 => BuyService::UserCartTotal(['user'=>$this->user]),
		];

		// 支付宝小程序在线客服
		if(APPLICATION_CLIENT_TYPE == 'alipay')
		{
			$result['common_app_mini_alipay_tnt_inst_id'] = MyC('common_app_mini_alipay_tnt_inst_id', null, true);
			$result['common_app_mini_alipay_scene'] = MyC('common_app_mini_alipay_scene', null, true);
		}

		// 限时秒杀
		if($result['common_app_is_limitedtimediscount'] == 1)
		{
			$ret = PluginsService::PluginsControlCall(
                'limitedtimediscount', 'index', 'index', 'api');
            if($ret['code'] == 0 && isset($ret['data']['code']) && $ret['data']['code'] == 0)
            {
                $result['plugins_limitedtimediscount_data'] = $ret['data']['data'];
            }
		}

		//类别
		$params['is_home_recommended'] = 1;
        $params['pid'] = 0;
        $category = GoodsService::GoodsCategoryApiIndex($params);
        $result['category'] = $category;

        //弹窗
        $query_params = $this->data_post;
        $popupver = isset($query_params['popupver']) ? $query_params['popupver'] : 0;
        $popupret = $this->fetchPopupContent($popupver);
        if($popupret['code'] == 0)
        {
        	$data = $popupret['data'];
        	$popup_data = [
        		'title' => $data['title'],
        		'popup_type' => $data['popup_type'],
        		'content' => $data['content'],
        		'button_text' => $data['button_text'],
        		'popop_url' => $data['url'],
        		'popupver' => $data['popupver']
        	];
        	if($data['popup_type'] == 0){
        		//获取弹窗优惠券列表
        		// 优惠劵列表
		        $coupon_params = [
		            'where'             => [
		                'is_enable'         => 1,
		                'is_user_receive'   => 1,
		                'is_popup'	=> 1,
		            ],
		            'm'                 => 0,
		            'n'                 => 5,
		            'is_sure_receive'   => 1,
		            'filter_not_available' => 1,
		            'user'              => $this->user,
		        ];
        		$ret = CouponService::CouponList($coupon_params);
        		$popup_data['coupon_list'] = $ret['data'];
        		// 获取基础配置信息
        		$base = BaseService::BaseConfig();
        		$popup_data['coupon_base'] = $base['data'];
        	}
            $result['plugins_popup_data'] = $popup_data;
        }
		// 返回数据
		return DataReturn('success', 0, $result);
	}
}
?>