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
use app\service\UserService;
use app\service\PaymentService;
use app\service\BuyService;
use app\service\PluginsService;

/**
 * 购买
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Buy extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct()
    {
        parent::__construct();

        // 是否登录
        $this->IsLogin();
    }
    
    /**
     * [Index 首页]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-02-22T16:50:32+0800
     */
    public function Index()
    {
        // 获取商品列表
        $params = $this->data_post;
        $params['user'] = $this->user;
        $ret = BuyService::BuyTypeGoodsList($params);

        // 商品校验
        if(isset($ret['code']) && $ret['code'] == 0)
        {
            if(isset($params['test_mode']) && $params['test_mode'] == 1){
                // 支付方式
                $payment_list = PaymentService::BuyPaymentList(['is_enable'=>1]);
            }else{
                $payment_list = PaymentService::BuyPaymentList(['is_enable'=>1, 'is_open_user'=>1]);
            }
            
            // 当前选中的优惠劵
            $coupon_id = isset($params['coupon_id']) ? intval($params['coupon_id']) : 0;

            // 数据返回组装
            $result = [
                'goods_list'                => $ret['data']['goods'],
                'payment_list'              => $payment_list,
                'base'                      => $ret['data']['base'],
                'extension_data'            => $ret['data']['extension_data'],
                'common_order_is_booking'   => (int) MyC('common_order_is_booking', 0),
                'common_site_type'          => (int) MyC('common_site_type', 0, true),
            ];
            $site_model = $result['common_site_type'] == 2 ? 2 : (isset($params['site_model']) ? $params['site_model'] : 0);
            $result['site_model'] = $site_model;

            // 优惠劵
            $ret = PluginsService::PluginsControlCall(
                    'coupon', 'coupon', 'buy', 'api', ['order_goods'=>$ret['data']['goods'], 'coupon_id'=>$coupon_id]);
            if($ret['code'] == 0 && isset($ret['data']['code']) && $ret['data']['code'] == 0)
            {
                $result['plugins_coupon_data'] = $ret['data']['data'];
            }

            // 提货配置
            $result['common_self_extraction_days'] = MyC('common_self_extraction_days', 1, true);
            $result['common_self_extraction_hours'] = MyC('common_self_extraction_hours', '11:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00', true);
            $day_array = [];
            $today_hour_array = [];
            $hour_array = [];
            $extraction_config = MyC('common_self_extraction_config');
            $extraction_config = str_replace('&quot;', '"', $extraction_config);
            // $result['extraction_config'] = $extraction_config;
            if(!empty($extraction_config)){
                $extraction = json_decode($extraction_config, true);
            }else{
                $extraction = [
                    'Sun' => '',
                    'Mon' => '10:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00',
                    'Tues' => '10:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00',
                    'Wed' => '10:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00',
                    'Thur' => '',
                    'Fri' => '11:00 - 13:00, 17:00 - 19:00, 21:00 - 22:00',
                    'Sat' => '',
                ];
            }
            $weekarray = array('Sun', 'Mon', 'Tues', 'Wed', 'Thur', 'Fri', 'Sat');
            $weekday = date('w');
            $currentHours = date('H:i');
            $i=0; 
            $count = 0;
            while($i<$result['common_self_extraction_days'] && $count<7){
                $weekday = $weekday % 7;
                $hour_config = $extraction[$weekarray[$weekday]];
                $weekday++;

                if(!empty($hour_config)){
                    $hours = explode(",", $hour_config);
                    if($count == 0){
                        //current day
                        for ($k = 0; $k < count($hours); $k++) {
                            $hour_set = explode("-", $hours[$k]);
                            if(!empty($hour_set) && count($hour_set) >1){
                              $time_end = trim($hour_set[1]);
                              if($time_end > $currentHours){
                                $today_hour_array[] = $hours[$k];
                              }
                            }
                        }
                        if(!empty($today_hour_array)){
                            $day_array[] = 'today';
                            $i++;
                            $hour_array['today'] = $today_hour_array;
                        } 
                    }else{
                        $i++;
                        $key = date('Y-m-d', strtotime('+'. $count.' day'));
                        $day_array[] = $key;
                        $hour_array[$key] = $hours;
                    }
                }
                $count++;
            } 
            $result['day_array'] = $day_array;
            $result['hour_array'] = $hour_array;

            $result['out_of_stock_type_list']       = lang('out_of_stock_type_list');


            return DataReturn('操作成功', 0, $result);
        }
        return $ret;
    }

    /**
     * 订单添加
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-25
     * @desc    description
     */
    public function Add()
    {
        $params = $this->data_post;
        $params['user'] = $this->user;
        return BuyService::OrderInsert($params);
    }
}
?>