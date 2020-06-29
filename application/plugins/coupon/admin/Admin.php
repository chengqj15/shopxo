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
namespace app\plugins\coupon\admin;

use think\Controller;
use app\service\PluginsService;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\StatisticalService;

/**
 * 优惠劵 - 基础配置
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-05T21:51:08+0800
 */
class Admin extends Controller
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-05T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        $ret = BaseService::BaseConfig(false);
        if($ret['code'] == 0)
        {
            // 统计数据
            $this->assign('statistical', StatisticalService::StatisticalData());

            $this->assign('data', $ret['data']);
            return $this->fetch('../../../plugins/view/coupon/admin/admin/index');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-05T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function saveinfo($params = [])
    {
        $ret = BaseService::BaseConfig(false);
        if($ret['code'] == 0)
        {
            $this->assign('data', $ret['data']);
            return $this->fetch('../../../plugins/view/coupon/admin/admin/saveinfo');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-05T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function save($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'coupon', 'data'=>$params], BaseService::$base_config_attachment_field);
    }
}
?>