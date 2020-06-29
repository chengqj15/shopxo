<?php
namespace app\api\controller;

use app\service\UserLevelService;
use app\service\PaymentService;

/**
 * 会员等级类
 */
class UserLevel extends Common
{
    public function __construct()
    {
        // 调用父类前置方法
        parent::__construct();
         // 是否登录
        $this->IsLogin();
    }

    /**
     * 会员等级列表
     * @param Request $request
     * @return mixed
     */
    public function grade()
    {
        // 参数
        $params = $this->data_post;
        $params['user'] = $this->user;
        // 支付方式
        $payment_list = PaymentService::BuyPaymentList(['is_enable'=>1, 'is_open_user'=>1]);
        $ret = UserLevelService::getLevelList($params);
        if($ret['code'] == 0){
            $ret['data']['payment_list'] = $payment_list;
        }
        return $ret;
    }

    /**
     * 获取任务列表
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function task()
    {
        // 参数
        $params = $this->data_post;
        $params['user'] = $this->user;
        return UserLevelService::getTashList($params);
    }

}