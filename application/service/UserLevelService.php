<?php
namespace app\service;

use think\Db;
use think\facade\Log;

use app\service\SystemUserLevel;
use app\service\UserTaskService;


/**
 * TODO 会员等级Model
 * Class UserLevel
 * @package app\models\user
 */
class UserLevelService
{

    /**
     * 模型名称
     * @var string
     */
    protected static $name = 'UserLevelInfo';


    /**
     * 获取用户等级人数
     * @param $uids
     * @return int
     */
    public static function setUserLevelCount($uids)
    {
        $model=new self();
        if(is_array($uids)) $model=$model->where('uid','in',$uids);
        else $model=$model->where('uid',$uids);
        return $model->count();
    }

    /**
     * 设置查询初始化条件
     * @param string $alias 表别名
     * @param null $model 模型实例化对象
     * @return UserLevel
     */
    public static function valiWhere($alias='',$model=null)
    {
        $model = is_null($model) ? Db::name(self::$name) : $model;
        if($alias){
            $model=$model->alias($alias);
            $alias.='.';
        }
        return $model->where("{$alias}status", 1)->where("{$alias}is_delete_time", 0);
    }

    public static function setUserLevelInternal($levelInfo, $level, $type=0, $order_id=''){
        Log::write('setUserLevelInternal level=' . json_encode($level));

        $valueLog = false;
        $uid = $levelInfo['id'];

        $levelLog = [
            'uid' => $uid,
            'grade' => $level['grade'],
            'level_id' => $level['id'],
            'origin_level_id' => $levelInfo['level_id'],
            'origin_grade' => $levelInfo['grade'],
            'type' => $type,
            'order_id' => $order_id,
            'add_time' => time()
        ];
    
        //检查是否购买过
        if($levelInfo && $levelInfo['level_id'] == $level['id']){
            //剩余时间
            $begin = time();
            if($begin < $levelInfo['valid_time']) {
                $begin = $levelInfo['valid_time'];
            }
            //如果购买过当前等级的会员过期了.从当前时间开始计算
            $data['valid_time'] = self::getValidTime($level, $begin);
            $levelLog['mark'] = '有效期从 $begin 延长 到 ' . $data['valid_time'];
        }else{
            $data=[
                'status' => 1,
                'grade' => $level['grade'],
                'level_id' => $level['id'],
                'level_money' => $level['money'],
                'valid_time'=>self::getValidTime($level)
            ];
            
            $up = $level['grade'] > $levelInfo['grade'];
            $levelLog['mark'] = '有效期延长 到 ' . $data['valid_time'];

            $delta_value = $level['point'] - $levelInfo['level_value'];
            if(($delta_value > 0 && $up) || ($delta_value < 0 && !$up)){
                //升级 或者 降级
                $orginal_value = $levelInfo['level_value'];
                $data['level_value'] = $level['point'];
                $mark = $up ? '会员等级升级到:'. $level['grade'] : '会员等级降级到:'. $level['grade'];
                $valueLog = [
                    'uid' => $uid,
                    'delta_value' => $delta_value,
                    'orginal_value' => $orginal_value,
                    'level_value' => $level['point'],
                    'delta_type' => $type,
                    'order_id' => $order_id,
                    'mark' => $mark,
                    'add_time' => time()
                ];
                
            }
        }
        // 开始事务
        Db::startTrans();
        // 更新 level info
        Db::name(self::$name)->where('id', $uid)->update($data);

        // 记录level log
        $log_id = Db::name('UserLevelLog')->insertGetId($levelLog);
        if($log_id <= 0){
            Db::rollback();
            return DataReturn('日志添加失败', -1);
        }

        // 记录成长值
        if($valueLog != false){
            $log_id = Db::name('UserLevelValueLog')->insertGetId($valueLog);
            if($log_id <= 0){
                Db::rollback();
                return DataReturn('成长值日志添加失败', -1);
            }
        }
        // 订单提交成功
        Db::commit();

        return DataReturn('操作成功', 0, $levelInfo);
    }

    /**
     * 设置会员等级
     * @param $uid 用户uid
     * @param $level_id 等级id
     * @return UserLevel|bool|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function setUserLevel($uid, $level_id, $order_id){
        $level_ret = SystemUserLevel::getSytemList(['id'=>$level_id]);
        if($level_ret['code'] != 0 || empty($level_ret['data'])) {
            return DataReturn('invalid level', -1);
        }
        $levelInfo = self::getUserLevel($uid);
        return self::setUserLevelInternal($levelInfo, $level_ret['data'][0], 1, $order_id);
    }

    /**
     * 获取当前用户会员等级返回当前用户等级id
     * @param $uid 用户uid
     * @param int $grade 会员id
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserLevel($uid)
    {
        // if ($grade) $model = $model->where('grade', '<', $grade);
        $levelInfo = self::valiWhere()->where('id', $uid)->field('id, level_no, level_money, level_id, grade, begin_time, valid_time, level_value, level_type, status')->find();
        if (!$levelInfo || $levelInfo['level_id'] == 0) 
        {
            Log::write('getUserLevel init user level');
            return self::initUserLevelInfo($uid, $levelInfo);
        }
        if ($levelInfo['valid_time'] == -1) 
        {//永久有效
            return $levelInfo;
        }
        if ($levelInfo['valid_time'] != 0 && time() > $levelInfo['valid_time']){
            //会员已经过期. 执行降级策略
            return self::downgradeUserLevel($levelInfo);
        }else{
            //会员没有过期
            return $levelInfo;
        }
    }

    public static function downgradeUserLevel($levelInfo)
    {
        $current_grade = $levelInfo['grade'];
        $list = SystemUserLevel::getLevelListAndGrade();
        $init = true;
        for ($i = count($list)-1; $i >=0; $i--) {
            if($list[$i]['grade'] >= $current_grade){
                continue;
            }
            if($levelInfo['level_value'] >= $list[$i]['point']){
                $ret = self::setUserLevelInternal($levelInfo, $list[$i], 0);
                if($ret['code'] == 0){
                    return $ret['data'];
                }
            }
        }
        if($init){
            $levelInfo['status'] = 1;
            $levelInfo['valid_time'] = 0;
            $levelInfo['level_value'] = 0;
            $levelInfo['level_id'] = 0;
            $levelInfo['grade'] = 0;
            $levelInfo['level_type'] = 0;
            $levelInfo['upd_time'] = time();
        }
        Db::name(self::$name)->where('id',$uid)->update($levelInfo);
        // insert user level log
        return $levelInfo;
    }

    public static function getValidTime($level, $now=0){
        $valid_time = 0;
        if($level['is_forever']){
            $valid_time = -1;
        }
        else{
            if($now){
                $date=date_create(date("Y-m-d", $now));
            }else{
                $date=date_create(date("Y-m-d"));
            }
            $interval = $level['valid_date'];
            if($level['valid_type'] == 0){
                //day
                $valid_time = date_add($date, date_interval_create_from_date_string("$interval day"))->getTimestamp();
            }elseif ($level['valid_type'] == 1) {
                //year
                $valid_time = date_add($date, date_interval_create_from_date_string("$interval year"))->getTimestamp();
            }else{
                $valid_time = date_add($date, date_interval_create_from_date_string("$interval month"))->getTimestamp();
            }
        }
        
        return $valid_time;
    }
    
    /**
     * 获取会员等级列表
     * @param $uid 用户uid
     * @param bool $leveNowId
     * @return UserLevel|bool|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLevelList($params)
    {
        if(empty($params['user'])){
            return DataReturn('无效用户', -1);
        }
        $level=self::getUserLevel($params['user']['id']);
        
        $list = SystemUserLevel::getLevelListAndGrade();
        $index = 0;
        foreach ($list as $key => &$item){
            $item['image'] = ResourcesService::AttachmentPathViewHandle($item['image']);
            if($item['id'] == $level['level_id']){
                $index = $key;
            }
            $up_money = $item['money'] - $level['level_money'];
            $item['up_money'] = $up_money > 0 ? PriceBeautify(PriceNumberFormat($up_money)) : 0;
        }

        $ret = ['list' => $list, 'index'=>$index, 'level' => $level];
        return DataReturn('处理成功', 0, $ret);
    }

    public static function getLevelValues($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::name('UserLevelValueLog')->where($where)->limit($m, $n)->order($order_by)->select();
        if(!empty($data))
        {
            $common_integral_log_type_list = lang('common_integral_log_type_list');
            foreach($data as &$v)
            {
                // 操作类型
                $v['type_name'] = $common_integral_log_type_list[$v['type']]['name'];

                // 时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = date('Y-m-d', $v['add_time']);
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    public static function getLevelValuesTotal($where = [])
    {
        return (int) Db::name('UserLevelValueLog')->where($where)->count();
    }

    public static function UserLevelValueLogListWhere($params = [])
    {
        // 条件初始化
        $where = [];

        // 用户id
        if(!empty($params['user']))
        {
            $where[] = ['uid', '=', $params['user']['id']];
        }

        if(!empty($params['keywords']))
        {
            $where[] = ['msg', 'like', '%'.$params['keywords'] . '%'];
        }

        // 是否更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            if(isset($params['type']) && $params['type'] > -1)
            {
                $where[] = ['type', '=', intval($params['type'])];
            }

            // 时间
            if(!empty($params['time_start']))
            {
                $where[] = ['add_time', '>', strtotime($params['time_start'])];
            }
            if(!empty($params['time_end']))
            {
                $where[] = ['add_time', '<', strtotime($params['time_end'])];
            }
        }

        return $where;
    }

    public static function getTashList($params)
    {
        $uid = $params['user']['id'];
        return UserTaskService::getTashList($uid);
    }

    public static function initUserLevelInfo($uid, $levelInfo = [])
    {
        $isNew = false;
        $list = SystemUserLevel::getLevelListAndGrade();
        if($levelInfo && isset($levelInfo['id'])){
            $isNew = false;
        }else{
            $levelInfo = [
                'id' => $uid,
                'status' => 1,
                'valid_time' => 0,
                'add_time' => time(),
                'upd_time' => time(),
            ];
            $isNew = true;
        }
        if(count($list) > 0 && $list[0]['point'] == 0){
            $levelInfo['level_id'] = $list[0]['id'];
            $levelInfo['grade'] = $list[0]['grade'];
            $levelInfo['begin_time'] = time();
            $levelInfo['valid_time'] = self::getValidTime($list[0]);
            $levelInfo['level_value'] = 0;
            $levelInfo['level_type'] = 0;
            $levelInfo['level_money'] = $list[0]['money'];
        }else{
            Log::write('initUserLevelInfo not level available');
        }
        if($isNew){
            $exist = true;
            $level_no = '';
            while($exist){
                $level_no = date('Ymd').GetNumberCode(6);
                $exist = Db::name(self::$name)->where('level_no', $level_no)->find();
            }
            $levelInfo['level_no'] = $level_no;
            Db::name(self::$name)->insertGetId($levelInfo);
        }else{
            Db::name(self::$name)->where('id',$uid)->update($levelInfo);
        }
        
        return $levelInfo;
    }

    public static function OrderLevelValueGiving($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'order_id',
                'error_msg'         => '订单id有误',
            ]
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 订单
        $order = Db::name('Order')->field('id,user_id,status,price,preferential_price')->find(intval($params['order_id']));
        if(empty($order))
        {
            return DataReturn('订单不存在或已删除，中止操作', 0);
        }
        if(!in_array($order['status'], [4]))
        {
            return DataReturn('当前订单状态不允许操作['.$params['order_id'].'-'.$order['status'].']', 0);
        }

        // 获取用户信息
        $user = Db::name('User')->field('id')->find(intval($order['user_id']));
        if(empty($user))
        {
            return DataReturn('用户不存在或已删除，中止操作', 0);
        }

        // 获取订单商品
        $diff = $order['price'] - $order['preferential_price'];
        $rate = MyC('common_level_value_rate', 1, false);
        $diff = $diff/$rate;
        if($diff <= 0){
            return DataReturn('没有需要操作的数据', 0);
        }

        $levelInfo = self::getUserLevel($user['id']);
        $orginal_value = $levelInfo['level_value'];

        // 开始事务
        Db::startTrans();

        if(!Db::name(self::$name)->where(['id'=>$user['id']])->setInc('level_value', $diff))
        {
            return DataReturn('用户积分赠送失败['.$params['order_id'].'-'.$goods_id.']', -10);
        }

        // 积分日志
        $valueLog = [
                    'uid' => $user['id'],
                    'delta_value' => $diff,
                    'orginal_value' => $orginal_value,
                    'level_value' => $orginal_value + $diff,
                    'delta_type' => 0,
                    'order_id' => $order['id'],
                    'mark' => '订单商品完成赠送',
                    'add_time' => time()
                ];

        
        $log_id = Db::name('UserLevelValueLog')->insertGetId($valueLog);
        if($log_id <= 0){
            Db::rollback();
            return DataReturn('成长值日志添加失败', -1);
        }

        $next = SystemUserLevel::getNextLevel($levelInfo['level_id']);
        if($next && $next['point'] <= ($orginal_value + $diff)){
            self::setUserLevelInternal($levelInfo, $next);
        }
        Db::commit();

        return DataReturn('操作成功', 0);
        
    }
}