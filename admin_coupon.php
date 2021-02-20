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
use app\service\GoodsService;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\CouponService;

/**
 * 优惠劵 - 优惠劵管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-05T21:51:08+0800
 */
class Coupon extends Controller
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
        // 分页
        $number = MyC('admin_page_number', 10, true);

        // 条件
        $where = CouponService::CouponWhere($params);

        // 获取总数
        $total = CouponService::CouponTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsAdminUrl('coupon', 'coupon', 'index'),
            );
        $page = new \base\Page($page_params);
        $this->assign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_list = [];
        if($total > 0)
        {
            $data_params = array(
                'm'         => $page->GetPageStarNumber(),
                'n'         => $number,
                'where'     => $where,
            );
            $data = CouponService::CouponList($data_params);
            $data_list = $data['data'];
        }

        // 静态数据
        $this->assign('common_is_whether_list', BaseService::$common_is_whether_list);
        $this->assign('coupon_type_list', BaseService::$coupon_type_list);
        $this->assign('coupon_bg_color_list', BaseService::$coupon_bg_color_list);
        $this->assign('common_expire_type_list', BaseService::$common_expire_type_list);
        $this->assign('common_use_limit_type_list', BaseService::$common_use_limit_type_list);

        // 商品分类
        $this->assign('category_list', GoodsService::GoodsCategoryAll());

        // 数据
        $this->assign('data_list', $data_list);
        $this->assign('params', $params);
        return $this->fetch('../../../plugins/view/coupon/admin/coupon/index');
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
        // 静态数据
        $this->assign('coupon_type_list', BaseService::$coupon_type_list);
        $this->assign('coupon_bg_color_list', BaseService::$coupon_bg_color_list);
        $this->assign('common_expire_type_list', BaseService::$common_expire_type_list);
        $this->assign('common_use_limit_type_list', BaseService::$common_use_limit_type_list);

        // 商品分类
        $this->assign('category_list', GoodsService::GoodsCategoryAll());

        // 编辑
        $data = [];
        if(!empty($params['id']))
        {
            $data_params = array(
                'm'         => 0,
                'n'         => 1,
                'where'     => ['id'=>intval($params['id'])],
            );
            $ret = CouponService::CouponList($data_params);
            $data = isset($ret['data'][0]) ? $ret['data'][0] : [];
        }

        unset($params['id']);
        $this->assign('params', $params);
        $this->assign('data', $data);
        return $this->fetch('../../../plugins/view/coupon/admin/coupon/saveinfo');
    }

    /**
     * 发放页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-05T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function sendinfo($params = [])
    {
        if(!empty($params['id']))
        {
            $data_params = array(
                'm'         => 0,
                'n'         => 1,
                'where'     => ['id'=>intval($params['id'])],
            );
            $ret = CouponService::CouponList($data_params);
            if(!empty($ret['data'][0]))
            {
                $this->assign('data', $ret['data'][0]);
            }
        }

        unset($params['id']);
        $this->assign('params', $params);
        return $this->fetch('../../../plugins/view/coupon/admin/coupon/sendinfo');
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
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 优惠劵保存
        return CouponService::CouponSave($params);
    }

    /**
     * 商品搜索
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function search($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 搜索数据
        return CouponService::GoodsSearchList($params);
    }

    /**
     * 状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-12T22:23:06+0800
     * @param    [array]          $params [输入参数]
     */
    public function statusupdate($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return CouponService::StatusUpdate($params);
    }

    /**
     * 删除
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-12T22:23:06+0800
     * @param    [array]          $params [输入参数]
     */
    public function delete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return CouponService::Delete($params);
    }

    /**
     * 用户搜索
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function usersearch($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 搜索数据
        $ret =  CouponService::UserSearchList($params);
        $this->assign('data', $ret['data']);
        return DataReturn('获取成功', 0, $this->fetch('../../../plugins/view/coupon/admin/coupon/user'));
    }

    /**
     * 优惠劵发放
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-05T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function send($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 优惠劵保存
        return CouponService::CouponSend($params);
    }

    /**
     * 优惠劵过期提醒
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-05T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function notice($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        $params = ['coupon_id' => $params['id']];

        // 优惠劵保存
        return CouponService::CouponExpireNotice($params);
    }
}
?>
