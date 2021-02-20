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
use app\plugins\coupon\service\UserCouponAdminService;

/**
 * 优惠劵 - 用户优惠劵
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-05T21:51:08+0800
 */
class User extends Controller
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
        $where = UserCouponAdminService::CouponUserWhere($params);

        // 获取总数
        $total = UserCouponAdminService::CouponUserTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsAdminUrl('coupon', 'user', 'index'),
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
            $data = UserCouponAdminService::CouponUserList($data_params);
            $data_list = $data['data'];
        }

        // 静态数据
        $this->assign('common_is_whether_list', BaseService::$common_is_whether_list);

        // 优惠劵列表
        $coupon_params = [
            'field'     => 'id,name',
            'where'     => ['is_enable' => 1],
            'is_handle' => 0,
        ];
        $ret = CouponService::CouponList($coupon_params);
        $this->assign('coupon_list', $ret['data']);

        // 数据
        $this->assign('data_list', $data_list);
        $this->assign('params', $params);
        return $this->fetch('../../../plugins/view/coupon/admin/user/index');
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
        return UserCouponAdminService::StatusUpdate($params);
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
        return UserCouponAdminService::Delete($params);
    }

    
    public function verify($params = [])
    {
        // 优惠劵保存
        return UserCouponAdminService::UserCouponVerify($params);
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

        $params = ['id' => $params['id']];

        // 优惠劵保存
        return CouponService::CouponExpireNotice($params);
    }
}
?>