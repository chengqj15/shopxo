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
namespace app\plugins\freightfee;

use app\service\PluginsService;

/**
 * 运费设置 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-09T14:25:44+0800
     * @param    [array]          $params [输入参数]
     */
    public function run($params = [])
    {
        if(!empty($params['hook_name']))
        {
            switch($params['hook_name'])
            {
                // css
                case 'plugins_css' :
                    $ret = __MY_ROOT_PUBLIC__.'static/plugins/css/freightfee/index/style.css';
                    break;

                // 运费计算
                case 'plugins_service_buy_handle' :
                    $ret = $this->FreightFeeCalculate($params);
                    break;

                // 商品免运费icon
                case 'plugins_view_goods_detail_title' :
                    $ret = $this->FreeShippingGoodsIcon($params);
                    break;

                default :
                    $ret = '';
            }
            return $ret;
        }
    }

    /**
     * 商品免运费icon
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-26
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    private function FreeShippingGoodsIcon($params = [])
    {
        $ret = PluginsService::PluginsData('freightfee');
        if($ret['code'] == 0 && !empty($ret['data']['goods_ids']) && !empty($params['goods_id']))
        {
            $goods_all = explode(',', $ret['data']['goods_ids']);
            if(in_array($params['goods_id'], $goods_all))
            {
                return '<span class="am-badge am-radius plugins-freightfee-goods-icon">免运费</span>';
            }
        }
        return '';
    }

    /**
     * 运费计算
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-21
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function FreightFeeCalculate($params = [])
    {
        // 站点模式 0销售, 2自提, 4销售+自提, 则其它正常模式
        $site_model = isset($params['data']['base']['site_model']) ? $params['data']['base']['site_model'] : 0;
        if($site_model != 0)
        {
            return DataReturn('无需处理', 0);
        }
        
        // 配置信息
        $ret = PluginsService::PluginsData('freightfee');
        if($ret['code'] == 0)
        {
            // 默认运费
            $price = 0;

            // 支付方式免运费
            $is_payment = true;
            if(!empty($ret['data']['payment']) && !empty($params['params']['payment_id']))
            {
                $payment = array_map(function($v){return explode('-', $v);}, explode(',', $ret['data']['payment']));
                if(!empty($payment) && is_array($payment))
                {
                    foreach($payment as $v)
                    {
                        if(isset($v[0]) && $v[0] == $params['params']['payment_id'])
                        {
                            $is_payment = false;
                            break;
                        }
                    }
                }
            }

            // 免运费商品
            $free_goods = $this->FreeShippingGoods(empty($ret['data']['goods_ids']) ? '' : $ret['data']['goods_ids'], $params);
            $params['data']['base']['buy_count'] -= $free_goods['buy_count'];
            $params['data']['base']['spec_weight_total'] -= $free_goods['spec_weight'];
            
            // 是否设置运费数据
            if($is_payment === true && !empty($ret['data']['data'][0]))
            {
                // 规则
                $rules = $this->RulesHandle($ret['data']['data'], $params['data']['base']['address']);

                // 计费方式
                if(!empty($rules))
                {
                    // 订单金额满免运费
                    if(empty($rules['free_shipping_price']) || $params['data']['base']['total_price'] < $rules['free_shipping_price'])
                    {
                        // 根据规则计算运费
                        switch($ret['data']['valuation'])
                        {
                            // 按件
                            case 0 :
                                $price = $this->PieceCalculate($rules, $params['data']);
                                break;

                            // 按量
                            case 1 :
                                $price = $this->QuantityCalculate($rules, $params['data']);
                                break;
                        }
                    }
                }
            }

            // 扩展展示数据
            $show_name = empty($ret['data']['show_name']) ? '运费' : $ret['data']['show_name'];
            $params['data']['extension_data'][] = [
                'name'      => $show_name,
                'price'     => $price,
                'type'      => 1,
                'tips'      => '+'.config('shopxo.price_symbol').$price.'元',
            ];

            // 金额
            $params['data']['base']['increase_price'] += $price;
            $params['data']['base']['actual_price'] += $price;

            return DataReturn('无需处理', 0);
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 免运费商品
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-26
     * @desc    description
     * @param   [string]         $goods_ids [商品ids]
     * @param   [array]          $params    [钩子参数]
     */
    private function FreeShippingGoods($goods_ids, $params)
    {
        $result = [
            'buy_count'     => 0,
            'spec_weight'   => 0,
        ];
        if(!empty($goods_ids))
        {
            $goods_all = explode(',', $goods_ids);
            foreach($params['data']['goods'] as $goods)
            {
                if(in_array($goods['goods_id'], $goods_all))
                {
                    $result['buy_count'] += $goods['stock'];
                    $result['spec_weight'] += $goods['spec_weight'];
                }
            }
        }

        return $result;
    }

    /**
     * 按重计费
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-21
     * @desc    description
     * @param   [array]          $rules     [规则]
     * @param   [array]          $buy       [生成订单数据]
     */
    public function QuantityCalculate($rules, $buy)
    {
        $price = 0;
        if($rules['first_price'] > 0 && $buy['base']['spec_weight_total'] >= $rules['first'])
        {
            $price = $rules['first_price'];
        }
        if($rules['continue_price'] > 0 && $buy['base']['spec_weight_total'] >= $rules['continue']+$rules['first'])
        {
            $number = ($buy['base']['spec_weight_total']-$rules['first'])/$rules['continue'];
            if($number > 0)
            {
                $price += round($rules['continue_price']*$number);
            }
        }

        return $price;
    }

    /**
     * 按件计费
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-21
     * @desc    description
     * @param   [array]          $rules     [规则]
     * @param   [array]          $buy       [生成订单数据]
     */
    public function PieceCalculate($rules, $buy)
    {
        $price = 0;
        if($rules['first_price'] > 0 && $buy['base']['buy_count'] >= $rules['first'])
        {
            $price = $rules['first_price'];
        }
        if($rules['continue_price'] > 0 && $buy['base']['buy_count'] >= $rules['continue']+$rules['first'])
        {
            $number = round(($buy['base']['buy_count']-$rules['first'])/$rules['continue']);
            if($number > 0)
            {
                $price += round($rules['continue_price']*$number);
            }
        }

        return $price;
    }

    /**
     * 运费规则匹配
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-21
     * @desc    description
     * @param   [array]          $rules   [运费规则列表]
     * @param   [array]          $address [用户地址]
     */
    public function RulesHandle($rules, $address)
    {
        if(count($rules) > 1 && !empty($address))
        {
            $data = [
                'province'  => ['rules' => [], 'number' => 0],
                'city'      => ['rules' => [], 'number' => 0],
            ];
            foreach($rules as $k=>$v)
            {
                if($k != 0)
                {
                    $region = explode('-', $v['region']);
                    if(!empty($region))
                    {
                        if(in_array($address['province'], $region))
                        {
                            $data['province']['rules'] = $v;
                            $data['province']['number']++;
                        }
                        if(in_array($address['city'], $region))
                        {
                            $data['city']['rules'] = $v;
                            $data['city']['number']++;
                        }
                    }
                }
            }
            if($data['city']['number'] > 0)
            {
                if($data['province']['number'] > $data['city']['number'])
                {
                    return $data['province']['rules'];
                }
                return $data['city']['rules'];
            } else {
                if($data['province']['number'] > 0)
                {
                    return $data['province']['rules'];
                }
            }
        }
        return $rules[0];
    }
}
?>