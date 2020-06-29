<?php
namespace app\admin\controller;

use think\facade\Hook;
use app\service\SystemUserLevel;

/**
 * 会员设置
 * Class UserLevel
 */
class UserLevel extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-03T12:39:08+0800
     */
    public function __construct()
    {
        // 调用父类前置方法
        parent::__construct();

        // 登录校验
        $this->IsLogin();

        // 权限校验
        $this->IsPower();
    }

    /*
     * 等级展示
     * */
    public function index()
    {
        // 参数
        $params = input();

        // 条件
        $where = SystemUserLevel::listWhere($params);
        // 获取列表
        $data = SystemUserLevel::getSytemList($where);

        $this->assign('params', $params);
        $this->assign('data_list', $data['data']);
        return $this->fetch();
    }

    /**
     * [SaveInfo 用户添加/编辑页面]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-14T21:37:02+0800
     */
    public function SaveInfo()
    {
        // 参数
        $params = input();

        // 用户编辑
        $data = [];
        if(!empty($params['id']))
        {
            $where = ['id'=>$params['id']];
            $ret = SystemUserLevel::getSytemList($where);
            if(empty($ret['data'][0]))
            {
                return $this->error('会员等级信息不存在', MyUrl('admin/userlevel/index'));
            }         
            $data = $ret['data'][0];
        }

        // 数据
        $this->assign('data', $data);
        $this->assign('params', $params);
        return $this->fetch();
    }

    
    /*
     * 会员等级添加或者修改
     * @param $id 修改的等级id
     * @return json
     * */
    public function Save()
    {
        // 是否ajax
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始操作
        $params = input('post.');
        $params['admin'] = $this->admin;


        return SystemUserLevel::LevelSave($params);
    }

    /*
     * 删除会员等级
     * @param int $id
     * */
    public function Delete()
    {
        // 是否ajax
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始操作
        $params = input('post.');
        $params['admin'] = $this->admin;
        return SystemUserLevel::LevelDelete($params);
    }

    /*
     * 等级任务列表
     * @return json
     * */
    public function TaskList()
    {
        // 参数
        $params = input();

        // 用户编辑
        $data = [];
        $level_id = $params['level_id'];
        if(empty($level_id))
        {
            return $this->error('请选择会员等级', MyUrl('admin/userlevel/index'));

        }
        $where = ['level_id'=>$level_id];
        $ret = SystemUserLevel::getTaskList($where);         
        // 数据
        $this->assign('data_list', $ret['data']);
        $this->assign('task_type', SystemUserLevel::$TaskType);
        $this->assign('params', $params);
        return $this->fetch();
    }

    /*
     * 等级任务页面
     * @return json
     * */
    public function TaskInfo()
    {
        // 参数
        $params = input();

        // 用户编辑
        $data = [];
        $level_id = $params['level_id'];
        if(empty($level_id))
        {
            return $this->error('请选择会员等级', MyUrl('admin/userlevel/tasklist', $params));

        }
        if(!empty($params['id']))
        {
            $where = ['id'=>$params['id']];
            $ret = SystemUserLevel::getTaskList($where);
            if(empty($ret['data'][0]))
            {
                return $this->error('等级任务不存在', MyUrl('admin/userlevel/tasklist', $params));
            }         
            $data = $ret['data'][0];
        }
        $list = SystemUserLevel::$TaskType;
        $menus=[];
        foreach ($list as $menu){
            $menus[] = ['value'=>$menu['type'],'label'=>$menu['name'].'----单位['.$menu['unit'].']'];
        }

        // 数据
        $this->assign('data', $data);
        $this->assign('task_type', $menus);
        $this->assign('params', $params);
        return $this->fetch();
    }

    /*
     * 保存或者修改任务
     * @param int $id 任务id
     * @param int $vip_id 会员id
     * */
    public function SaveLevelTask()
    {
        // 是否ajax
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始操作
        $params = input('post.');
        $params['admin'] = $this->admin;


        return SystemUserLevel::LevelTaskSave($params);
    }

    /*
     * 删除等级任务
     * @param int $id
     * */
    public function DeleteLevelTask()
    {
        // 是否ajax
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始操作
        $params = input('post.');
        $params['admin'] = $this->admin;
        return SystemUserLevel::LevelTaskDelete($params);
    }

}