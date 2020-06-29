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
namespace app\plugins\multilingual;

use think\Controller;

/**
 * 多语言 - 钩子入口
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
        // 钩子名称
        if(!empty($params['hook_name']))
        {
            $ret = '';
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    $ret = __MY_ROOT_PUBLIC__.'static/plugins/css/multilingual/index/style.css';
                    break;

                // 公共js
                case 'plugins_js' :
                    $ret = __MY_ROOT_PUBLIC__.'static/plugins/js/multilingual/index/common.js';
                    break;

                // 顶部导航
                case 'plugins_view_header_navigation_top_left' :
                    $ret = $this->TopSubmitHtml();
                    break;

                // 底部
                case 'plugins_view_common_bottom' :
                    $ret = $this->BottomSubmitHtml();
                    break;
            }
            return $ret;
        }
    }

    /**
     * 顶部语言切换按钮
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-19
     * @desc    description
     */
    private function TopSubmitHtml()
    {
        return $this->fetch('../../../plugins/view/multilingual/index/public/top');
    }

    /**
     * 底部语言切换按钮
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-19
     * @desc    description
     */
    private function BottomSubmitHtml()
    {
        return $this->fetch('../../../plugins/view/multilingual/index/public/bottom');
    }
}
?>