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

    public static function setUserLevelInternal($levelInfo, $level, $type=0, $order_no=''){

        $valueLog = false;

        $levelLog = [
            'uid' => $uid,
            'grade' => $level['grade'],
            'level_id' => $level_id,
            'origin_level_id' => $levelInfo['level_id'],
            'origin_grade' => $levelInfo['grade'],
            'type' => $type,
            'order_no' => $order_no,
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
                'level_id' => $level_id,
                'valid_time'=>self::getValidTime($level)
            ];
            
            $up = $level['grade'] > $levelInfo['grade'];
            $levelLog['mark'] = '有效期延长 到 ' . $data['valid_time'];

            $delta_value = $level['point'] - $levelInfo['level_value'];
            if(（$delta_value > 0 && $up） || （$delta_value < 0 && !$up){
                //升级 或者 降级
                $orginal_value = $levelInfo['level_value'];
                $data['level_value'] = $level['point'];
                $mark = $up ? '会员等级升级到:'. $level['grade'] : '会员等级降级到:'. $level['grade']
                $valueLog = [
                    'uid' => $uid,
                    'delta_value' => $delta_value,
                    'orginal_value' => $orginal_value,
                    'level_value' => $level['point'],
                    'delta_type' => $type,
                    'order_no' => $order_no,
                    'mark' => $mark,
                    'add_time' => time()
                ];
                
            }
        }
        // 开始事务
        Db::startTrans();
        // 更新 level info
        Db::name(self::$name)->where('uid', $uid)->update($data);

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
    public static function setUserLevel($uid, $level_id, $order_no){
        $level = SystemUserLevel::getSytemList(['id'=>$level_id]);
        if(!$level) {
            return DataReturn('invalid level', -1);
        }
        $levelInfo = self::getUserLevel($uid);
        return self::setUserLevelInternal($levelInfo, $level, $order_no);
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
        $levelInfo = self::valiWhere()->where('id', $uid)->field('id, level_id, grade, begin_time, valid_time, level_value, level_type, status')->find();
        if (!$levelInfo) 
        {
            return self::initUserLevelInfo($uid)
        }
        if ($levelInfo->valid_time == -1) 
        {//永久有效
            return $levelInfo;
        }
        if ($levelInfo->valid_time != 0 && time() > $levelInfo->valid_time){
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

    public static function getValidTime($level, $now){
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
            if($item['id'] == $level['id']){
                $index = $key;
            }
        }

        $ret = ['list' => $list, 'index'=>$index, 'level' => $level];
        return DataReturn('处理成功', 0, $ret);
    }

    public static function getTashList($params)
    {
        $level_id = $params['level_id'];
        $uid = $params['user']['id'];
        $level = isset($params['level']) ? $params['level'] : null;
        return UserTaskService::getTashList($level_id, $uid, $level);
    }

    public static function initUserLevelInfo($uid)
    {
        $list = SystemUserLevel::getLevelListAndGrade();

        $levelInfo = [
            'id' => $uid,
            'status' => 1,
            'valid_time' => 0,
            'add_time' => time(),
            'upd_time' => time(),
        ];
        if(count($list) > 0 && $list[0]['point'] == 0){
            $levelInfo['level_id'] = $list[0]['id'];
            $levelInfo['grade'] = $list[0]['grade'];
            $levelInfo['begin_time'] = time();
            $levelInfo['valid_time'] = self::getValidTime($list[$i]);
            $levelInfo['level_value'] = 0;
            $levelInfo['level_type'] = 0;
        }
        
        Db::name(self::$name)->insertGetId($levelInfo);
        return $levelInfo;
    }

}