<?php
namespace app\plugins\multilingual\admin;

use think\Controller;

/**
 * 多语言 - 后台管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin extends Controller
{
    // 后台管理入口
    public function index($params = [])
    {
        // 数组组装
        $this->assign('data', ['hello', 'world!']);
        $this->assign('msg', 'hello world! admin');
        return $this->fetch('../../../plugins/view/multilingual/admin/admin/index');
    }
}
?>