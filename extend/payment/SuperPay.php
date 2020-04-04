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

use think\facade\Log;

/**
 * 微信支付
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2018-09-19
 * @desc    description
 */
class SuperPay
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
            'name'          => 'SuperPay',  // 插件名称
            'version'       => '1.0.0',  // 插件版本
            'apply_version' => '不限',  // 适用系统版本描述
            'apply_terminal'=> ['pc', 'h5', 'ios', 'android', 'weixin', 'toutiao'], // 适用终端 默认全部 ['pc', 'h5', 'app', 'alipay', 'weixin', 'baidu']
            'desc'          => '适用公众号+PC+H5+APP+[微信|头条]小程序，即时到帐支付方式，买家的交易资金直接打入卖家账户，快速回笼交易资金。 <a href="https://www.superpayglobal.com/" target="_blank">立即申请</a>',  // 插件描述（支持html）
            'author'        => 'Devil',  // 开发者
            'author_url'    => 'https://www.henleemarket.xyz/',  // 开发者主页
        ];

        // 配置信息
        $element = [
            [
                'element'       => 'input',
                'type'          => 'text',
                'default'       => '',
                'name'          => 'appid',
                'placeholder'   => '公众号/服务号AppID',
                'title'         => '公众号/服务号AppID',
                'is_required'   => 0,
                'message'       => '请填写微信分配的AppID',
            ],
            [
                'element'       => 'input',
                'type'          => 'text',
                'default'       => '',
                'name'          => 'mini_appid',
                'placeholder'   => '小程序ID',
                'title'         => '小程序ID',
                'is_required'   => 0,
                'message'       => '请填写微信分配的小程序ID',
            ],
            [
                'element'       => 'input',
                'type'          => 'text',
                'default'       => '',
                'name'          => 'recevice_id',
                'placeholder'   => '收款方唯一标识',
                'title'         => '收款方唯一标识',
                'is_required'   => 0,
                'message'       => '接入平台商户编号',
            ],
            [
                'element'       => 'input',
                'type'          => 'text',
                'default'       => '',
                'name'          => 'mch_id',
                'placeholder'   => '平台方商户编号',
                'title'         => '平台方商户编号',
                'is_required'   => 0,
                'message'       => '请填写平台方商户编号',
            ],
            [
                'element'       => 'input',
                'type'          => 'text',
                'default'       => '',
                'name'          => 'key',
                'placeholder'   => '密钥',
                'title'         => '密钥',
                'desc'          => '微信支付商户平台API配置的密钥',
                'is_required'   => 0,
                'message'       => '请填写密钥',
            ],
            [
                'element'       => 'select',
                'title'         => '异步通知协议',
                'message'       => '请选择协议类型',
                'name'          => 'agreement',
                'is_multiple'   => 0,
                'element_data'  => [
                    ['value'=>1, 'name'=>'默认当前协议'],
                    ['value'=>2, 'name'=>'强制https转http协议'],
                ],
            ],
        ];

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
        // 参数
        if(empty($params))
        {
            return DataReturn('参数不能为空', -1);
        }

        // 配置信息
        if(empty($this->config))
        {
            return DataReturn('支付缺少配置', -1);
        }

        // 微信中打开
        if(in_array(APPLICATION_CLIENT_TYPE, ['pc', 'h5']))
        {
            if(!empty($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false && empty($params['user']['weixin_web_openid']))
            {
                exit(header('location:'.PluginsHomeUrl('weixinwebauthorization', 'pay', 'index', input())));
            }
        }

        // 获取支付参数
        $ret = $this->GetPayParams($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 请求接口处理
        $result = json_decode($this->HttpRequest($this->superpay_order_url, $ret['data']), true);
        if(!empty($result['status']) && $result['status'] == 'T' 
            && !empty($result['data']['result']) && $result['data']['result'] == true)
        {
            $pay_info = json_decode($result['data']['pay_info'], true);

            return $this->PayHandleReturn($ret['data'], $pay_info, $params);
        }
        $msg = is_string($result) ? $result : (empty($result['message']) ? '支付接口异常' : $result['message']);
        if(!empty($result['data']))
        {
            $msg .= '-'.$result['data'];
        }
        return DataReturn($msg, -1);
    }

    /**
     * 支付返回处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-01-08
     * @desc    description
     * @param   [array]           $pay_params   [支付参数]
     * @param   [array]           $data         [支付返回数据]
     * @param   [array]           $params       [输入参数]
     */
    private function PayHandleReturn($pay_params = [], $data = [], $params = [])
    {
        $redirect_url = empty($params['redirect_url']) ? __MY_URL__ : $params['redirect_url'];
        $result = DataReturn('支付接口异常', -1);
        switch($pay_params['pay_way']) //trade_type
        {
            // web支付
            case 'NATIVE' :
                if(empty($params['ajax_url']))
                {
                    return DataReturn('支付状态校验地址不能为空', -50);
                }
                $pay_params = [
                    'url'       => urlencode(base64_encode($data['code_url'])),
                    'order_no'  => $params['order_no'],
                    'name'      => urlencode('微信支付'),
                    'msg'       => urlencode('打开微信APP扫一扫进行支付'),
                    'ajax_url'  => urlencode(base64_encode($params['ajax_url'])),
                ];
                $url = MyUrl('index/pay/qrcode', $pay_params);
                $result = DataReturn('success', 0, $url);
                break;

            // h5支付
            case 'MWEB' :
                if(!empty($params['order_id']))
                {
                    $data['mweb_url'] .= '&redirect_url='.$redirect_url;
                }
                $result = DataReturn('success', 0, $data['mweb_url']);
                break;

            // 微信中/小程序支付
            case 'JSAPI' :
                // appid
                //$appid = (APPLICATION == 'app') ? $this->config['mini_appid'] :  $this->config['appid'];

                // $pay_data = array(
                //     'appId'         => $data['appId'],
                //     'package'       => $data['package'],
                //     'nonceStr'      => $data['nonceStr'],
                //     'signType'      => $pay_params['sign_type'],
                //     'timeStamp'     => (string) time(),
                // );
                // $pay_data['paySign'] = $this->GetSign($pay_data);
                $pay_data = $data;

                // 微信中
                if(in_array(APPLICATION_CLIENT_TYPE, ['pc', 'h5']) && !empty($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false)
                {
                    $this->PayHtml($pay_data, $redirect_url);
                } else {
                    $result = DataReturn('success', 0, $pay_data);
                }
                break;

            // APP支付
            case 'APP' :
                $pay_data = array(
                    'appid'         => $this->config['appid'],
                    'partnerid'     => $this->pay_params['mch_id'],
                    'prepayid'      => $data['prepayid'],
                    'package'       => 'Sign=WXPay',
                    'noncestr'      => md5(time().rand()),
                    'timestamp'     => (string) time(),
                );
                $pay_data['sign'] = $this->GetSign($pay_data);
                $result = DataReturn('success', 0, $pay_data);
                break;
        }
        return $result;
    }

    /**
     * 支付代码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-05-25T00:07:52+0800
     * @param    [array]                   $pay_data     [支付信息]
     * @param    [string]                  $redirect_url [支付结束后跳转url]
     */
    private function PayHtml($pay_data, $redirect_url)
    {
        // 支付代码
        exit('<html>
            <head>
                <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
                <title>微信安全支付</title>
                <script type="text/javascript">
                    function onBridgeReady()
                    {
                       WeixinJSBridge.invoke(
                            \'getBrandWCPayRequest\', {
                                "appId":"'.$pay_data['appId'].'",
                                "timeStamp":"'.$pay_data['timeStamp'].'",
                                "nonceStr":"'.$pay_data['nonceStr'].'",
                                "package":"'.$pay_data['package'].'",     
                                "signType":"'.$pay_data['signType'].'",
                                "paySign":"'.$pay_data['paySign'].'"
                            },
                            function(res) {
                                window.location.href = "'.$redirect_url.'";
                            }
                        ); 
                    }
                    if(typeof WeixinJSBridge == "undefined")
                    {
                       if( document.addEventListener )
                       {
                           document.addEventListener("WeixinJSBridgeReady", onBridgeReady, false);
                       } else if (document.attachEvent)
                       {
                           document.attachEvent("WeixinJSBridgeReady", onBridgeReady); 
                           document.attachEvent("onWeixinJSBridgeReady", onBridgeReady);
                       }
                    } else {
                       onBridgeReady();
                    }
                </script>
                </head>
            <body>
        </html>');
    }

    /**
     * 获取支付参数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GetPayParams($params = [])
    {
        $trade_type = empty($params['trade_type']) ? $this->GetTradeType() : $params['trade_type'];
        if(empty($trade_type))
        {
            return DataReturn('支付类型不匹配', -1);
        }

        // openid
        if(APPLICATION == 'app')
        {
            $openid = isset($params['user']['weixin_openid']) ? $params['user']['weixin_openid'] : '';
        } else {
            $openid = isset($params['user']['weixin_web_openid']) ? $params['user']['weixin_web_openid'] : '';
        }

        // 异步地址处理
        $notify_url = (__MY_HTTP__ == 'https' && isset($this->config['agreement']) && $this->config['agreement'] == 2) ? 'http'.mb_substr($params['notify_url'], 5, null, 'utf-8') : $params['notify_url'];

        $amout = strval($params['total_price']);

        // 请求参数
        $data = [
            'service'           => 'create_instant_trade',
            'mch_id'            => $this->config['mch_id'],
            'charset'           => 'UTF-8',
            'sign_type'         => 'MD5',
            'memo'              => $params['site_name'].'-'.$params['name'],
            'version'           => '1.0',
            'call_back_url'     => $notify_url,
            'store_name'        => $params['site_name'],
            'recevice_id'       => $this->config['recevice_id'],
            'mch_order_no'      => $params['order_no'],
            'pay_way'           => $trade_type,
            'pay_channel'       => 'WXPAY',
            'subOpenId'         => ($trade_type == 'JSAPI') ? $openid : '',
            'currency'          => 'AUD',
            'pay_amount'        => $amout,
            'goods_name'        => 'goods',
            'goods_price'       => $amout,
            'quantity'          => '1',
            'goods_desc'        => empty($params['attach']) ? $params['site_name'].'-'.$params['name'] : $params['attach'],
            'timeout_express'   => '30m',
            'client_ip'         => GetClientIP(),
        ];
        $data['sign'] = $this->GetSign($data);
        Log::write('GetPayParams ret:' . json_encode($data));
        return DataReturn('success', 0, $data);
    }

    /**
     * 获取支付交易类型
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-01-08
     * @desc    description
     */
    private function GetTradeType()
    {
        $type_all = [
            'pc'        => 'NATIVE',
            'weixin'    => 'JSAPI',
            'h5'        => 'MWEB',
            'toutiao'   => 'MWEB',
            'app'       => 'APP',
            'ios'       => 'APP',
            'android'   => 'APP',
        ];

        // 手机中打开pc版本
        if(APPLICATION_CLIENT_TYPE == 'pc' && IsMobile())
        {
            $type_all['pc'] = $type_all['h5'];
        }

        // 微信中打开
        if(in_array(APPLICATION_CLIENT_TYPE, ['pc', 'h5']))
        {
            if(!empty($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false)
            {
                $type_all['pc'] = $type_all['weixin'];
            }
        }

        return isset($type_all[APPLICATION_CLIENT_TYPE]) ? $type_all[APPLICATION_CLIENT_TYPE] : '';
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
        $data = empty($GLOBALS['HTTP_RAW_POST_DATA']) ? file_get_contents('php://input') : $GLOBALS['HTTP_RAW_POST_DATA'];
        Log::write('superpay asyn respon:' . $data);

        parse_str($data, $result);

        if(isset($result['notify_type']) && $result['notify_type'] == 'trade_status_sync' && $result['sign'] == $this->GetSign($result))
        {
            if($result['trade_status'] == 'TRADE_SUCCESS'){
                return DataReturn('支付成功', 0, $this->ReturnData($result));
            }else{
                return DataReturn('支付关闭', 10001);
            }
        }
        return DataReturn('处理异常错误', -100);
    }

    /**
     * [ReturnData 返回数据统一格式]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2018-10-06T16:54:24+0800
     * @param    [array]                   $data [返回数据]
     */
    private function ReturnData($data)
    {
        // 参数处理
        //$out_trade_no = substr($data['outer_trade_no'], 0, strlen($data['outer_trade_no'])-6);

        // 返回数据固定基础参数
        $data['trade_no']       = $data['inner_trade_no'];  // 支付平台 - 订单号
        $data['buyer_user']     = ''; //$data['openid'];          // 支付平台 - 用户
        $data['out_trade_no']   = $data['outer_trade_no'];            // 本系统发起支付的 - 订单号
        $data['subject']        = $data['trade_amount'];          // 本系统发起支付的 - 商品名称
        $data['pay_price']      = $data['trade_amount'];   // 本系统发起支付的 - 总价 Trade amount, in RMB-Yuan, rounded to hundredth
        return $data;
    }

    /**
     * 退款处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Refund($params = [])
    {
        // 参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'order_no',
                'error_msg'         => '订单号不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'trade_no',
                'error_msg'         => '交易平台订单号不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'pay_price',
                'error_msg'         => '支付金额不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'refund_price',
                'error_msg'         => '退款金额不能为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 退款原因
        $refund_reason = empty($params['refund_reason']) ? $params['order_no'].'订单退款'.$params['refund_price'].'元' : $params['refund_reason'];

        // 请求参数
        $data = [
            'service'           => 'create_refund',
            'mch_id'            => $this->config['mch_id'],
            'charset'           => 'UTF-8',
            'sign_type'         => 'MD5',
            'version'           => '1.0',
            'call_back_url'      => $params['refund_notify_url'],
            'mch_order_no'      => $params['order_no'].GetNumberCode(6),
            'orig_mch_order_no' => $params['order_no'],
            'refund_amount'     => strval($params['refund_price']),
            'refund_note'       => $refund_reason,
        ];
        $data['sign'] = $this->GetSign($data);
        Log::write("superpay refund request: ". json_encode($data));

        // 请求接口处理
        $result = json_decode($this->HttpRequest($this->superpay_order_url, $data), true);
        if(isset($result['status']) && $result['status'] == 'T' && isset($result['data']['result']) && $result['data']['result'] == true)
        {
            // 统一返回格式
            $data = [
                'out_trade_no'  => $params['order_no'],
                'trade_no'      => $data['mch_order_no'],
                'buyer_user'    => '',
                'refund_price'  => $params['refund_price'],
                'return_params' => isset($result['message']) ? $result['message'] : '',
            ];
            return DataReturn('退款成功', 0, $data);
        }
        $msg = is_string($result) ? $result : (empty($result['message']) ? '退款接口异常' : $result['message']);
        return DataReturn($msg, -1);
    }

    /**
     * 退款回调处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function RefundNotify($params = [])
    {
        $data = empty($GLOBALS['HTTP_RAW_POST_DATA']) ? file_get_contents('php://input') : $GLOBALS['HTTP_RAW_POST_DATA'];
        Log::write('superpay refund respon:' . $data);

        parse_str($data, $result);

        if(isset($result['notify_type']) && $result['notify_type'] == 'refund_status_sync' && $result['sign'] == $this->GetSign($result))
        {
            $ret = [
                'new_trade_no' => $result['inner_trade_no'],
                'refund_price' => $result['refund_amount'],
                'old_trade_no' => $result['outer_trade_no'],
            ];
            if($result['refund_status'] == 'REFUND_SUCCESS'){
                $ret['msg'] = 'sucess';
                return DataReturn('退款成功', 0, $ret);
            }else{
                $ret['msg'] = 'fail';
                return DataReturn('退款失败', 10001, $ret);
            }
        }
        return DataReturn('处理异常错误', -100);
    }

    /**
     * 签名生成
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GetSign($params = [])
    {
        ksort($params);
        $sign  = '';
        foreach($params as $k=>$v)
        {
            if($k != 'sign' && $k != 'sign_type' && $v != '' && $v != null)
            {
                $sign .= "$k=$v&";
            }
        }
        return strtoupper(md5($sign.'key='.$this->config['key']));
    }


    /**
     * [HttpRequest 网络请求]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2017-09-25T09:10:46+0800
     * @param    [string]          $url         [请求url]
     * @param    [array]           $data        [发送数据]
     * @param    [boolean]         $use_cert    [是否需要使用证书]
     * @param    [int]             $second      [超时]
     * @return   [mixed]                        [请求返回数据]
     */
    private function HttpRequest($url, $data, $use_cert = false, $second = 30)
    {
        $data_string = json_encode($data);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',                                                                                
                'Content-Length: ' . strlen($data_string),                                                                      
            ),
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POSTFIELDS     => $data_string,
            CURLOPT_TIMEOUT        => $second,
        );

        if($use_cert == true)
        {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            $apiclient = $this->GetApiclientFile();
            $options[CURLOPT_SSLCERTTYPE] = 'PEM';
            $options[CURLOPT_SSLCERT] = $apiclient['cert'];
            $options[CURLOPT_SSLKEYTYPE] = 'PEM';
            $options[CURLOPT_SSLKEY] = $apiclient['key'];
        }
 
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        Log::write('httprequest result:' . $result);
        //返回结果
        if($result)
        {
            curl_close($ch);
            return $result;
        } else { 
            $error = curl_errno($ch);
            curl_close($ch);
            return "curl出错，错误码:$error";
        }
    }

    /**
     * 获取证书文件路径
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-29
     * @desc    description
     */
    private function GetApiclientFile()
    {
        // 证书位置
        $apiclient_cert_file = ROOT.'runtime'.DS.'temp'.DS.'payment_weixin_pay_apiclient_cert.pem';
        $apiclient_key_file = ROOT.'runtime'.DS.'temp'.DS.'payment_weixin_pay_apiclient_key.pem';

        // 证书处理
        if(stripos($this->config['apiclient_cert'], '-----') === false)
        {
            $apiclient_cert = "-----BEGIN CERTIFICATE-----\n";
            $apiclient_cert .= wordwrap($this->config['apiclient_cert'], 64, "\n", true);
            $apiclient_cert .= "\n-----END CERTIFICATE-----";
        } else {
            $apiclient_cert = $this->config['apiclient_cert'];
        }
        file_put_contents($apiclient_cert_file, $apiclient_cert);

        if(stripos($this->config['apiclient_key'], '-----') === false)
        {
            $apiclient_key = "-----BEGIN PRIVATE KEY-----\n";
            $apiclient_key .= wordwrap($this->config['apiclient_key'], 64, "\n", true);
            $apiclient_key .= "\n-----END PRIVATE KEY-----";
        } else {
            $apiclient_key = $this->config['apiclient_key'];
        }
        file_put_contents($apiclient_key_file, $apiclient_key);

        return ['cert' => $apiclient_cert_file, 'key' => $apiclient_key_file];
    }


}
?>