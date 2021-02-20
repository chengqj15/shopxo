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
namespace payment;

use think\Db;
use think\facade\Log;

/**
 * 微信支付
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2018-09-19
 * @desc    description
 */
class TransferPay
{
    // 插件配置参数
    private $config;
    private $superpay_order_url = 'https://gate.supaytechnology.com/api/gateway/merchant/order';

    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-17
     * @desc    description
     * @param   [array]           $params [输入参数（支付配置参数）]
     */
    public function __construct($params = [])
    {
        $this->config = $params;
    }

    /**
     * 配置信息
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     */
    public function Config()
    {
        // 基础信息
        $base = [
            'name'          => '银行转账',  // 插件名称
            'version'       => '1.0.0',  // 插件版本
            'apply_version' => '不限',  // 适用系统版本描述
            'apply_terminal'=> ['pc', 'h5', 'ios', 'android', 'weixin', 'toutiao'], // 适用终端 默认全部 ['pc', 'h5', 'app', 'alipay', 'weixin', 'baidu']
            'desc'          => '适用公众号+PC+H5+APP+[微信|头条]小程序，转账支付，买家转账后截图',  // 插件描述（支持html）
            'author'        => 'henlee',  // 开发者
            'author_url'    => 'https://www.henleemarket.xyz/',  // 开发者主页
        ];

        // 配置信息
        $element = [];

        return [
            'base'      => $base,
            'element'   => $element,
        ];
    }

        /**
     * 支付入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Pay($params = [])
    {
        if(!isset($params['transfer_snap']) || empty($params['transfer_snap'])){
            return DataReturn('转账截图不能为空', -1);
        }

        $url = 'out_trade_no='.$params['order_no'];
        $url .= '&subject='.$params['name'];
        $url .= '&total_price='.$params['total_price'];
        $url .= '&transfer_snap='.$params['transfer_snap'];
        Log::write('TransferPay, pay=' . $url);

        $suc = Db::name('Order')->where(['id'=>$params['order_id']])->update(['transfer_snap'=>$params['transfer_snap'], 'upd_time'=>time()]);
        
        if($suc)
        {
            return DataReturn('处理成功', 0);
        }else{
            return DataReturn('处理失败', -1);
        }
    }

    /**
     * 支付回调处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Respond($params = [])
    {
        return DataReturn('处理成功', 0, $params);
    }


}
?>