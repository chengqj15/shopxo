<?php


namespace app\service;

use think\Db;
use think\facade\Hook;

/**
 * TODO 设置会员等级Model
 * Class SystemUserLevel
 */
class SystemUserLevel
{
    /**
     * 模型名称
     * @var string
     */
    protected static $name = 'SystemUserLevel';

    /*
     * 获取查询条件
     * @param string $alert 别名
     * @param object $model 模型
     * @return object
     * */
    public static function setWhere($alias='',$model=null)
    {
        $model=$model===null ? Db::name(self::$name) : $model;
        if($alias) $model=$model->alias($alias);
        $alias=$alias ? $alias.'.': '';
        return $model->where("{$alias}is_show",1)->where("{$alias}is_delete_time",0);
    }

    /*
     * 获取某个等级的折扣
     * */
    public static function getLevelDiscount($id=0)
    {
        $model=self::setWhere();
        if($id) $model=$model->where('id',$id);
        else $model=$model->order('grade asc');
        return $model->value('discount');
    }

    /**
     * 获取会员等级级别
     * @param $leval_id 等级id
     * @return mixed
     */
    public static function getLevelGrade($leval_id)
    {
        return self::setWhere()->where('id',$leval_id)->value('grade');
    }

    /**
     * 获取会员等级列表
     * @param $leval_id 用户等级
     * @param $isArray 是否查找任务列表
     * @param int $expire
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLevelListAndGrade()
    {
        $list = self::setWhere()->order('grade asc')->select();
        return $list;
    }

    /**
     *
     * @param $leval_id
     * @param null $list
     * @return bool
     */
    public static function getClear($leval_id,$list=null)
    {
        $list= $list === null ?  self::getLevelListAndGrade($leval_id,false) : $list;
        foreach ($list as $item){
            if($item['id'] == $leval_id) return $item['is_clear'];
        }
        return false;
    }


    /**
     * 获取当前vipid 的下一个会员id
     * @param $leval_id 当前用户的会员id
     * @return int|mixed
     */
    public static function getNextLevelId($leval_id)
    {
        $list=self::getLevelListAndGrade($leval_id,false);
        $grade=0;
        $leveal=[];
        foreach ($list as $item){
            if($item['id']==$leval_id) $grade=$item['grade'];
        }
        foreach ($list as $item){
            if($grade < $item['grade']) array_push($leveal,$item['id']);
        }
        return isset($leveal[0]) ? $leveal[0] : 0;
    }

    /**
     * 查找系统设置的会员等级列表
     * @param $where
     * @return array
     */
    public static function getSytemList($where)
    {
        $data = Db::name(self::$name)->where($where)->order('grade asc')->select();
        return DataReturn('处理成功', 0, $data);
    }


    /**
     * 用户列表条件
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     * @param    [array]          $params [输入参数]
     */
    public static function listWhere($params = [])
    {
        $where = [];
        if(!empty($params['keywords']))
        {
            $where[] =['name', 'like', '%'.$params['keywords'].'%'];
        }

        if(isset($params['gender']) && $params['gender'] > -1)
        {
            $where[] = ['gender', '=', intval($params['gender'])];
        }
        return $where;
    }

    /**
     * 用户信息保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     * @param    [array]          $params [输入参数]
     */
    public static function LevelSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'admin',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '2,30',
                'is_checked'        => 1,
                'error_msg'         => '等级名称 2~30 个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'is_forever',
                'checked_data'      => '30',
                'is_checked'        => 1,
                'error_msg'         => '用户昵称格式最多 30 个字符之间',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'explain',
                'is_checked'        => 1,
                'error_msg'         => '请输入等级说明',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'grade',
                'error_msg'         => '请输入等级',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'point',
                'is_checked'        => 1,
                'error_msg'         => '成长值不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'image',
                'is_checked'        => 1,
                'error_msg'         => '会员背景不能为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        $is_forever = isset($params['is_forever']) ? $params['is_forever'] : 0;
        $valid_date = isset($params['valid_date']) ? $params['valid_date'] : 0;
        if($is_forever==0 && !$valid_date) 
        {
            return DataReturn('请输入有效时间');
        }
        $is_pay = isset($params['is_pay']) ? $params['is_pay'] : 0;
        $money = isset($params['money']) ? intval($params['money']) : 0;
        if($is_pay && !$money) 
        {
            return DataReturn('请输入购买金额');
        }

        $where = [['is_delete_time', '=', 0], ['grade', '=', $params['grade']]];
        if(!empty($params['id'])){
            array_push($where, ['id','<>',$params['id']]);
        }
        if(0 < Db::name(self::$name)->where($where)->count())
        {
            return DataReturn('已检测到您设置过的会员等级，此等级不可重复');
        }

        // 更新数据
        $data = [
            'name'              => isset($params['name']) ? $params['name'] :  '',
            'grade'             => isset($params['grade']) ? $params['grade'] :  0,
            'explain'           => isset($params['explain']) ? $params['explain'] :  '',
            'is_forever'        => $is_forever,
            'valid_date'        => $valid_date,
            'valid_type'        => isset($params['valid_type']) ? $params['valid_type'] :  0,
            'is_pay'            => $is_pay,
            'point'             => $params['point'],
            'is_show'           => isset($params['is_show']) ? $params['is_show'] :  1,
            'money'             => $money,
            'discount'          => isset($params['discount']) ? intval($params['discount']) :  0,
            'free_package_count'=> isset($params['free_package_count']) ? intval($params['free_package_count']) :  0,
            'icon'              => '',
            'image'             => isset($params['image']) ? $params['image'] : '',
            'upd_time'          => time(),
        ];

        // 更新/添加
        if(!empty($params['id']))
        {
            // 获取用户信息
            $user = Db::name(self::$name)->field('id')->find($params['id']);
            if(empty($user))
            {
                return DataReturn('会员等级不存在', -10);
            }

            $data['upd_time'] = time();
            if(Db::name(self::$name)->where(['id'=>$params['id']])->update($data))
            {
                $user_id = $params['id'];
            }
        } else {
            $data['add_time'] = time();
            $user_id = Db::name(self::$name)->insertGetId($data);
        }

        // 状态
        if(isset($user_id))
        {
            return DataReturn('操作成功', 0);
        }
        return DataReturn('操作失败', -100);
    }

    /**
     * 用户删除
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     * @param    [array]          $params [输入参数]
     */
    public static function LevelDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '删除id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
           
        // 删除操作
        if(Db::name(self::$name)->delete(intval($params['id'])))
        {
            return DataReturn('删除成功');
        }
        return DataReturn('删除失败或资源不存在', -100);
    }

    /**
     * 任务类型
     * type 记录在数据库中用来区分任务
     * name 任务名 (任务名中的{$num}会自动替换成设置的数字 + 单位)
     * max_number 最大设定数值 0为不限定
     * min_number 最小设定数值
     * unit 单位
     * @var array
     */
    public static $TaskType=[
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

    /**
     * 获取某个任务
     * @param $type
     * @return mixed
     */
    public static function getTaskType($type)
    {
        foreach (self::$TaskType as $item){
            if($item['type']==$type) return $item;
        }
    }

    /**
     * 设置任务名
     * @param $type
     * @param $num
     * @return mixed
     */
    public static function setTaskName($type,$num)
    {
        $systemType=self::getTaskType($type);
        return str_replace('{$num}',$num.$systemType['unit'],$systemType['name']);
    }

    /**
     * 获取等级会员任务列表
     * @param $level_id
     * @param $page
     * @param $limit
     * @return array
     */
    public static function getTaskList($where)
    {
        $data=Db::name('SystemUserTask')->where($where)->order('sort desc,add_time desc')->select();
        foreach ($data as &$item){
            $level_name = Db::name(self::$name)->where('id',$item['level_id'])->value('name');
            $item['level_name']= $level_name;
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 用户信息保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     * @param    [array]          $params [输入参数]
     */
    public static function LevelTaskSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'admin',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'level_id',
                'is_checked'        => 1,
                'error_msg'         => '请选择会员等级',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'task_type',
                'is_checked'        => 1,
                'error_msg'         => '请选择任务类型',
            ],
            [
                'checked_type'      => 'min',
                'key_name'          => 'number',
                'checked_data'      => 1,
                'is_checked'        => 1,
                'error_msg'         => '请输入限定数量,数量不能小于1',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        $tash=self::getTaskType($params['task_type']);
        if($tash['max_number']!=0 && $params['number'] > $tash['max_number'])
        {
            return DataReturn('您设置的限定数量超出最大限制,最大限制为:'.$tash['max_number'], -1);
        } 
        
        // 更新数据
        $data = [
            'task_type'      => $params['task_type'],
            'number'         => isset($params['number']) ? $params['number'] :  0,
            'sort'           => isset($params['sort']) ? $params['sort'] :  0,
            'is_must'        => isset($params['is_must']) ? $params['is_must'] :  0,
            'illustrate'     => isset($params['illustrate']) ? $params['illustrate'] :  '',
            'is_show'        => isset($params['is_show']) ? $params['is_show'] :  1,
            'upd_time'       => time(),
            'name'           => self::setTaskName($params['task_type'],$params['number']),
            'real_name'      => $tash['real_name']
        ];

        // 更新/添加
        if(!empty($params['id']))
        {
            // 获取用户信息
            $exist_task = Db::name('SystemUserTask')->field('id')->find($params['id']);
            if(empty($exist_task))
            {
                return DataReturn('任务不存在', -10);
            }

            $data['upd_time'] = time();
            if(Db::name('SystemUserTask')->where(['id'=>$params['id']])->update($data))
            {
                $task_id = $params['id'];
            }
        } else {
            $data['add_time'] = time();
            $data['level_id']=$params['level_id'];
            $task_id = Db::name('SystemUserTask')->insertGetId($data);
        }

        // 状态
        if(isset($task_id))
        {
            return DataReturn('操作成功', 0);
        }
        return DataReturn('操作失败', -100);
    }

    /**
     * 用户删除
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     * @param    [array]          $params [输入参数]
     */
    public static function LevelTaskDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '删除id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
           
        // 删除操作
        if(Db::name('SystemUserTask')->delete(intval($params['id'])))
        {
            return DataReturn('删除成功');
        }
        return DataReturn('删除失败或资源不存在', -100);
    }

}