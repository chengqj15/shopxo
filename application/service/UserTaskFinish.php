<?php
/**
 * Created by CRMEB.
 * Copyright (c) 2017~2019 http://www.crmeb.com All rights reserved.
 * Author: liaofei <136327134@qq.com>
 * Date: 2019/3/27 21:42
 */

namespace app\service;

use think\Db;
use think\facade\Hook;

/**
 * TODO 用户等级完成任务记录 model
 * Class UserTaskFinish
 * @package app\models\user
 */
class UserTaskFinish
{
    /**
     * 设置任务完成情况
     * @param $uid 用户uid
     * @param $task_id 任务id
     * @return UserTaskFinish|bool|\think\Model
     */
    public static function setFinish($uid,$task_id)
    {
        $add_time=time();
        if(0<Db::name('UserTaskFinish')->where(['uid'=>$uid,'task_id'=>$task_id])->count()){
            return true;
        }
        $data = [
            'uid'      => $uid,
            'task_id'         => $task_id,
            'add_time'       => $add_time,
            'upd_time'       => $add_time
        ];
        return Db::name('UserTaskFinish')->insertGetId($data);
    }

    public static function countTask($where=[])
    {
        return Db::name('UserTaskFinish')->group('task_id')->where($where)->count();
    }
}