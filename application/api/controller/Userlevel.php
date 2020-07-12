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
        $ret = UserLevelService::getLevelList($params);
        if($ret['code'] == 0){
            // 支付方式
            $payment_list = PaymentService::BuyPaymentList(['is_enable'=>1, 'is_open_user'=>1]);
            $ret['data']['payment_list'] = $payment_list;
            $task_list = UserLevelService::getTashList($params);
            $ret['data']['task_list'] = $task_list;
        }
        return $ret;
    }

    /**
     * 会员等级分列表
     * @param Request $request
     * @return mixed
     */
    public function values()
    {
        // 参数
        $params = $this->data_post;
        $params['user'] = $this->user;
        
        // 分页
        $number = 10;
        $page = max(1, isset($this->data_post['page']) ? intval($this->data_post['page']) : 1);

        // 条件
        $where = UserLevelService::UserLevelValueLogListWhere($params);

        // 获取总数
        $total = UserLevelService::getLevelValuesTotal($where);
        $page_total = ceil($total/$number);
        $start = intval(($page-1)*$number);

        // 获取列表
        $data_params = array(
            'm'         => $start,
            'n'         => $number,
            'where'     => $where,
        );
        $data = UserLevelService::getLevelValues($data_params);

        // 返回数据
        $result = [
            'total'             =>  $total,
            'page_total'        =>  $page_total,
            'data'              =>  $data['data'],
        ];
        return DataReturn('success', 0, $result);

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