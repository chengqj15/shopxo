<?php
/**
 * Created by CRMEB.
 * Copyright (c) 2017~2019 http://www.crmeb.com All rights reserved.
 * Author: liaofei <136327134@qq.com>
 * Date: 2019/3/27 21:42
 */

namespace app\models\user;

use common\traits\ModelTrait;
use common\basic\BaseModel;

/**
 * TODO 用户等级完成任务记录 model
 * Class UserTaskFinish
 * @package app\models\user
 */
class UserTaskFinish extends BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'user_task_finish';

    use ModelTrait;

    /**
     * 设置任务完成情况
     * @param $uid 用户uid
     * @param $task_id 任务id
     * @return UserTaskFinish|bool|\think\Model
     */
    public static function setFinish($uid,$task_id)
    {
        $add_time=time();
        if(0<Db::('UserTaskFinish')->where(['uid'=>$uid,'task_id'=>$task_id])->count()){
            return true;
        }
        return self::create(compact('uid','task_id','add_time'));
    }
}