<?php
namespace app\plugins\db_backup\admin;


use app\service\PluginsAdminService;
use app\service\PluginsService;
use think\Controller;

/**
 * 数据备份 - 后台管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin extends Controller
{
    //数据备份路径
    private $backup_dir = ROOT.'backup/database/';

    // 后台管理入口
    public function index()
    {
        $data_list = $this->get_backup_file($this->backup_dir);
        $is_enable = PluginsService::PluginsStatus('db_backup');

        // 数组组装
        $this->assign('data_list', $data_list['data']);
        $this->assign('is_enable', $is_enable);
        return $this->fetch('../../../plugins/view/db_backup/admin/admin/index');
    }

    //开启插件
    public function open()
    {
        if (PluginsService::PluginsStatus('db_backup'))
        {
            return DataReturn('插件已启动',0);
        }
        $params = ['id' => 'db_backup', 'state' => 1];

        return PluginsAdminService::PluginsStatusUpdate($params);
    }

    //备份操作
    public function backup()
    {
        if(!file_exists(ROOT.'config/database.php'))
        {
            return ['msg'=>'配置未找到','code'=>-400];
        }
        set_time_limit(1800); // 30分钟
        $data_list = $this->get_backup_file($this->backup_dir);
        $this->assign('data_list', $data_list['data']);
        $db_config = include ROOT.'config/database.php';

        $backup = new backup($db_config['hostname'],$db_config['username'],$db_config['password'],$db_config['database'],$this->backup_dir,$db_config['charset']);
        $backup ->output = true;//显示备份过程
        $res = $backup ->backupTables('*');

        if (!$res)
        {
            $this->error('备份失败,请确保根目录可写');
        }
        $this->success('操作成功',null,'',3);
        exit();
    }

    /**
     * 获取备份
     * @param $dir string 查看的路径
     * @return array
     */
    private function get_backup_file($dir)
    {
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                $i = 0;
                while (($file = readdir($dh)) !== false) {
                    if ($file != "." && $file != "..") {
                        $files[$i]["name"] = $file;//获取文件名称
                        $files[$i]["size"] = round((filesize($dir.$file)/1024),2).' Kb';//获取文件大小
                        $files[$i]["time"] = date("Y-m-d H:i:s",filemtime($dir.$file));//获取文件最近修改日期
                        $i++;
                    }
                }
            }
            closedir($dh);
            if (empty($files)) {
                return DataReturn('暂无数据',-100,[]);
            }
            foreach($files as $k=>$v){
                $size[$k] = $v['size'];
                $time[$k] = $v['time'];
                $name[$k] = $v['name'];
            }
            array_multisort($time,SORT_DESC,SORT_STRING, $files);//按时间排序
            //array_multisort($name,SORT_DESC,SORT_STRING, $files);//按名字排序
            //array_multisort($size,SORT_DESC,SORT_NUMERIC, $files);//按大小排序
            return DataReturn('获取成功',0,$files);
        }

        return DataReturn('无数据',-1,[]);
    }

    //删除备份文件
    public function del_file($params = [])
    {
        if (empty($params['id']))
        {
            return DataReturn('数据配置异常',-100);
        }

        if (!file_exists($this->backup_dir.$params['id']))
        {
            return DataReturn('备份已删除', -100);
        }

        if (!unlink($this->backup_dir.$params['id']))
        {
            return DataReturn('备份删除失败，请确保有权限', -100);
        }
        return DataReturn('操作成功', 0);
    }

    //下载文件
    public function down_file($params = [])
    {
        if (empty($params['id']))
        {
            return DataReturn('数据配置异常',-100);
        }

        if (!file_exists($this->backup_dir.$params['id']))
        {
            return DataReturn('备份已删除', -100);
        }
        $file = $this->backup_dir.$params['id'];
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        $this->success('备份已开始下载');
        exit();
    }
}
