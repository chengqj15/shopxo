<?php
namespace app\service;

use think\Db;
use think\facade\Log;

use app\service\UserTaskFinish;
use app\service\SystemUserLevel;

/**
 * TODO 设置等级任务Model
 * Class SystemUserTask
 * @package app\models\system
 */
class UserTaskService
{
    /**
     * 模型名称
     * @var string
     */
    protected static $name = 'SystemUserTask';

    /**
     * 任务类型
     * type 记录在数据库中用来区分任务
     * name 任务名 (任务名中的{$num}会自动替换成设置的数字 + 单位)
     * max_number 最大设定数值 0为不限定
     * min_number 最小设定数值
     * unit 单位
     * */
    protected static $TaskType=[
        [
            'type'=>'SatisfactionIntegral',
            'name'=>'满足积分{$num}',
            'real_name'=>'积分数',
            'max_number'=>0,
            'min_number'=>0,
            'unit'=>'分'
        ],
        [
            'type'=>'ConsumptionAmount',
            'name'=>'消费满{$num}',
            'real_name'=>'消费金额',
            'max_number'=>0,
            'min_number'=>0,
            'unit'=>'元'
        ],
        [
            'type'=>'ConsumptionFrequency',
            'name'=>'消费{$num}',
            'real_name'=>'消费次数',
            'max_number'=>0,
            'min_number'=>0,
            'unit'=>'次'
        ],
        [
            'type'=>'CumulativeAttendance',
            'name'=>'累计签到{$num}',
            'real_name'=>'累计签到',
            'max_number'=>365,
            'min_number'=>1,
            'unit'=>'天'
        ],
        [
            'type'=>'SharingTimes',
            'name'=>'分享给朋友{$num}',
            'real_name'=>'分享给朋友',
            'max_number'=>1000,
            'min_number'=>1,
            'unit'=>'次'
        ],
        [
            'type'=>'InviteGoodFriends',
            'name'=>'邀请好友{$num}成为下线',
            'real_name'=>'邀请好友成为下线',
            'max_number'=>1000,
            'min_number'=>1,
            'unit'=>'人'
        ],
        [
            'type'=>'InviteGoodFriendsLevel',
            'name'=>'邀请好友{$num}成为会员',
            'real_name'=>'邀请好友成为会员',
            'max_number'=>1000,
            'min_number'=>1,
            'unit'=>'人'
        ],
    ];

    public function profile()
    {
        return $this->hasOne('SystemUserLevel','level_id','id')->field('name');
    }

    public static function getTaskTypeAll()
    {
        return self::$TaskType;
    }

    /**
     * 获取某个任务
     * @param string $type 任务类型
     * @return array
     * */
    public static function getTaskType($type)
    {
        foreach (self::$TaskType as $item){
            if($item['type']==$type) return $item;
        }
    }

    /**
     * 设置任务名
     * @param string $type 任务类型
     * @param int $num 预设值
     * @return string
     * */
    public static function setTaskName($type,$num)
    {
        $systemType=self::getTaskType($type);
        return str_replace('{$num}',$num.$systemType['unit'],$systemType['name']);
    }

    /**
     * 累计消费金额
     * @param int $task_id 任务id
     * @param int $uid 用户id
     * @param int $start_time 开始时间
     * @param int $number 限定时间
     * @return boolean
     * */
    public static function ConsumptionAmount($task_id,$uid=0,$start_time=0,$number=0)
    {
        $isComplete=false;
        $SumPayPrice = StoreOrder::where('paid', 1)->where('refund_status', 0)->where('is_del', 0)->where('uid', $uid)->where('add_time','>',$start_time)->sum('pay_price');
        if($SumPayPrice >= $number) $isComplete=UserTaskFinish::setFinish($uid,$task_id) ? true : false;
        return ['还需消费{$num}元',$SumPayPrice,$isComplete];
    }

    /**
     * 累计消费次数
     * @param int $task_id 任务id
     * @param int $uid 用户id
     * @param int $start_time 开始时间
     * @param int $number 限定时间
     * @return boolean
     * */
    public static function ConsumptionFrequency($task_id,$uid=0,$start_time=0,$number=0)
    {
        $isComplete=false;
        $countPay = StoreOrder::where('paid', 1)->where('refund_status', 0)->where('is_del', 0)->where('uid', $uid)->where('add_time','>',$start_time)->count();
        if($countPay >= $number) $isComplete=UserTaskFinish::setFinish($uid,$task_id) ? true : false;
        return ['还需消费{$num}次',$countPay,$isComplete];
    }

    /**
     * 邀请好友成为会员
     * @param int $task_id 任务id
     * @param int $uid 用户id
     * @param int $start_time 开始时间
     * @param int $number 限定时间
     * @return boolean
     * */
    public static function InviteGoodFriendsLevel($task_id,$uid=0,$start_time=0,$number=0)
    {
        $isComplete=false;
        $uids=User::where('spread_uid',$uid)->where('spread_time','>',$start_time)->column('uid','uid');
        $levelCount=count($uids) ? UserLevel::setUserLevelCount($uids) : 0;
        if($levelCount >= $number) $isComplete = UserTaskFinish::setFinish($uid,$task_id) ? true : false;
        return ['还需邀请{$num}人成为会员',$levelCount,$isComplete];
    }

    /**
     * 邀请好友成为下线
     * @param int $task_id 任务id
     * @param int $uid 用户id
     * @param int $start_time 查询开始时间
     * @param int $number 限定数量
     * */
    public static function InviteGoodFriends($task_id,$uid=0,$start_time=0,$number=0)
    {
        $isComplete=false;
        $spreadCount=User::where('spread_uid',$uid)->where('spread_time','>',$start_time)->count();
        if($spreadCount >= $number) $isComplete=UserTaskFinish::setFinish($uid,$task_id) ? true : false;
        return ['还需邀请{$num}人成为下线',$spreadCount,$isComplete];
    }

    /**
     * 满足积分
     * @param int $task_id 任务id
     * @param int $uid 用户id
     * @param int $start_time 查询开始时间
     * @param int $number 限定数量
     * @return Boolean
     * */
    public static function SatisfactionIntegral($task_id,$uid=0,$start_time=0,$number=0)
    {
        $isComplete = false;
        $sumNumber = UserBill::where('uid', $uid)->where('category', 'integral')->where('pm', 1)->where('add_time','>',$start_time)->where('type','in',['system_add','sign','gain'])->sum('number');
        if($sumNumber >= $number) $isComplete=UserTaskFinish::setFinish($uid,$task_id) ? true : false;
        return ['还需要{$num}经验',$sumNumber,$isComplete];
    }

    /**
     * 分享给朋友次数完成情况
     * @param int $task_id 任务id
     * @param int $uid 用户id
     * @param int $start_time 查询开始时间
     * @param int $number 限定数量
     * @return Boolean
     * */
    public static function SharingTimes($task_id,$uid=0,$start_time=0,$number=0)
    {
        $isComplete=false;
        $sumCount=UserBill::where('uid', $uid)->where('category', 'share')->where('pm', 1)->where('add_time','>',$start_time)->where('type','share')->count();
        if($sumCount >= $number) $isComplete=UserTaskFinish::setFinish($uid,$task_id) ? true :false;
        return ['还需分享{$num}次',$sumCount,$isComplete];
    }

    /**
     * 累计签到
     * @param int $task_id 任务id
     * @param int $uid 用户id
     * @param int $start_time 查询开始时间
     * @param int $number 限定数量
     * @return Boolean
     * */
    public static function CumulativeAttendance($task_id,$uid=0,$start_time=0,$number=0)
    {
        $isComplete=false;
        $sumCount=UserBill::where('uid', $uid)->where('category', 'integral')->where('pm', 1)->where('add_time','>',$start_time)->where('type','sign')->count();
        if($sumCount >= $number) $isComplete=UserTaskFinish::setFinish($uid,$task_id) ? true : false;
        return ['还需签到{$num}天',$sumCount,$isComplete];
    }

    /**
     * 设置任务完成情况
     * @param int $task_id 任务id
     * @param int $uid 用户uid
     * @param int $start_time 查询开始时间
     * @return Boolean
     * */
    public static function setTaskFinish($task_id=0,$uid=0,$start_time=0)
    {
        if(!$task_id) return self::setErrorInfo('缺少任务id参数');
        if(!$uid) return self::setErrorInfo('缺少用户uid');
        $task=self::where('id',$task_id)->where('is_show',1)->find();
        if(!$task) return self::setErrorInfo('任务不存在');
        $task_type=$task->task_type;
        if($task_type && method_exists(self::class,$task_type)){
            try{
                $start_time=User::getCleanTime($uid);
                return self::$task_type($task_id,$uid,$start_time,$task->number);
            }catch (\Exception $e){
                return self::setErrorInfo($e->getMessage());
            }
        }
        return self::setErrorInfo('没有此任务');
    }

    /**
     * 设置任务显示条件
     * @param string $alert 表别名
     * @param object $model 模型实例
     * @return object
     * */
    public static function visibleWhere($alert='',$model=null)
    {
        $model=$model===null ? Db::name(self::$name) : $model;
        if($alert) $model=$model->alias($alert);
        $alert=$alert ? $alert.'.': '';
        return $model->where("{$alert}is_show",1);
    }

    /**
     * 获取等级会员任务列表
     * @param int $level_id 会员等级id
     * @param int $uid 用户id
     * @return array
     * */
    public static function getTashList($uid=0)
    {
        $list = self::visibleWhere()->field('name,real_name,task_type,illustrate,number,id')->order('sort desc')->select();
        Log::write('getTashList list:' . json_encode($list));

        if($uid == 0) return $list;

        $task_ids = array_column($list, 'id');
        $where = [
                    ['status', '=', 0],
                    ['uid', '=', $uid],
                    ['task_id','in',implode(',', $task_ids)]
                ];
        $finish_tasks = Db::name('UserTaskFinish')->where($where)->select();
        $finish_ids = array_column($finish_tasks, 'task_id');
        foreach ($list as $key => &$value) {
            if(in_array($value['id'], $finish_ids)) {
                $value['finish'] = 1;
            }else{
                $value['finish'] = 0;
            }
        }

        return $list;
    }

    /**
     * 获取未完成任务的详细值
     * @param array $item 任务
     * @param int $uid 用户id
     * @param int $startTime 开始时间
     * @return array
     * */
    protected static function set_task_type($item,$uid,$startTime=0){
        $task=['task_type_title'=>'','new_number'=>0,'speed'=>0,'finish'=>0];
        $task_type=$item['task_type'];
        switch ($task_type) {
            case 'SatisfactionIntegral':
            case 'ConsumptionAmount':
            case 'ConsumptionFrequency':
            case 'CumulativeAttendance':
            case 'SharingTimes':
            case 'InviteGoodFriends':
            case 'InviteGoodFriendsLevel':
                try{
                    list($task_type_title,$num,$isComplete)=self::$task_type($item['id'],$uid,$startTime,$item['number']);
                    if($isComplete){
                        $task['finish']=1;
                        $task['speed']=100;
                        $task['speed']=$item['number'];
                        $task['new_number']=$item['number'];
                    }else{
                        $numdata=bcsub($item['number'],$num,0);
                        $task['task_type_title']=str_replace('{$num}',$numdata,$task_type_title);
                        $task['speed']=bcdiv($num,$item['number'],2);
                        $task['speed']=bcmul($task['speed'],100,0);
                        $task['new_number']=$num;
                    }
                }catch (\Exception $e){}
                break;
        }
        return [$task['new_number'],$task['speed'],$task['task_type_title'],$task['finish']];
    }


    /**
     * 设置任务完成状态,已被使用
     * @param int $level_id 会员id
     * @param int $uid 用户id
     * @return Boolean
     * */
    public static function setTarkStatus($level_id,$uid)
    {
        $taskIds=self::visibleWhere()->where('level_id',$level_id)->column('id','id');
        if(!count($taskIds)) return true;
        return UserTaskFinish::where('uid',$uid)->where('task_id','in',$taskIds)->update(['status'=>1]);
    }


}