<?php
namespace app\plugins\db_backup\admin;

class backup
{
    var $host;
    var $username;
    var $passwd;
    var $dbName;
    var $charset;
    var $conn;
    var $backupDir;
    var $backupFile;
    var $gzipBackupFile;
    var $output = false;

    public function __construct($host, $username, $passwd, $dbName, $backup_dir = '', $charset = 'utf8')
    {
        $this->host = $host;
        $this->username = $username;
        $this->passwd = $passwd;
        $this->dbName = $dbName;
        $this->charset = $charset;
        $this->conn = $this->initializeDatabase();
        $this->backupDir = $backup_dir ? $backup_dir : '.';
        $this->backupFile = date("YmdHis", time()) . '.sql';//备份文件名称
        $this->gzipBackupFile = defined('GZIP_BACKUP_FILE') ? GZIP_BACKUP_FILE : true; // 备份压缩的gzip格式，默认打开
    }

    /**
     * 初始化数据连接
     * @return array|mysqli
     */
    protected function initializeDatabase()
    {
        try {
            $conn = mysqli_connect($this->host, $this->username, $this->passwd, $this->dbName);
            if (mysqli_connect_errno()) {

                return array('code' => 1, 'msg' => mysqli_connect_error());
            }
            if (!mysqli_set_charset($conn, $this->charset)) {
                mysqli_query($conn, 'SET NAMES ' . $this->charset);
            }
        } catch (Exception $e) {
            return array('code' => -1, 'msg' => $e->getMessage());
        }

        return $conn;
    }

    /**
     * 备份整改系统的数据表或者仅备份某个表
     * 使用 “*”代表全部备份，或者备份某些用“table1 table2 table3...”
     *
     */
    public function backupTables($tables = '*')
    {
        try {
            /**
             * Tables to export
             */
            if ($tables == '*') {
                $tables = array();
                $result = mysqli_query($this->conn, 'SHOW TABLES');
                while ($row = mysqli_fetch_row($result)) {
                    $tables[] = $row[0];
                }
            } else {
                $tables = is_array($tables) ? $tables : explode(',', str_replace(' ', '', $tables));
            }

            $sql = 'CREATE DATABASE IF NOT EXISTS `' . $this->dbName . "`;\n\n";
            $sql .= 'USE `' . $this->dbName . "`;\n\n";

            /**
             * Iterate tables
             */
            foreach ($tables as $table) {
                if ($this->output) {
                    $this->obfPrint("正在备份 `" . $table . "` 数据表..." . str_repeat('.', 50 - strlen($table)), 0, 0);//显示当前备份表
                }

                /**
                 * CREATE TABLE
                 */
                $sql .= 'DROP TABLE IF EXISTS `' . $table . '`;';
                $row = mysqli_fetch_row(mysqli_query($this->conn, 'SHOW CREATE TABLE `' . $table . '`'));
                $sql .= "\n\n" . $row[1] . ";\n\n";

                /**
                 * INSERT INTO
                 */

                $row = mysqli_fetch_row(mysqli_query($this->conn, 'SELECT COUNT(*) FROM `' . $table . '`'));
                $numRows = $row[0];

                // Split table in batches in order to not exhaust system memory
                $batchSize = 1000; // Number of rows per batch
                $numBatches = intval($numRows / $batchSize) + 1; // Number of while-loop calls to perform
                for ($b = 1; $b <= $numBatches; $b++) {

                    $query = 'SELECT * FROM `' . $table . '` LIMIT ' . ($b * $batchSize - $batchSize) . ',' . $batchSize;
                    $result = mysqli_query($this->conn, $query);
                    $numFields = mysqli_num_fields($result);

                    for ($i = 0; $i < $numFields; $i++) {
                        $rowCount = 0;
                        while ($row = mysqli_fetch_row($result)) {
                            $sql .= 'INSERT INTO `' . $table . '` VALUES(';
                            for ($j = 0; $j < $numFields; $j++) {
                                if (isset($row[$j])) {
                                    $row[$j] = addslashes($row[$j]);
                                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                                    $sql .= '"' . $row[$j] . '"';
                                } else {
                                    $sql .= 'NULL';
                                }

                                if ($j < ($numFields - 1)) {
                                    $sql .= ',';
                                }
                            }

                            $sql .= ");\n";
                        }
                    }

                    $this->saveFile($sql);
                    $sql = '';
                }

                $sql .= "\n\n\n";
                if ($this->output) $this->obfPrint("成功");//显示备份结果

            }

            if ($this->gzipBackupFile) {
                $this->gzipBackupFile();
            } else {
                if ($this->output) $this->obfPrint('数据成功备份，备份文件完整路径： ' . $this->backupDir . '/' . $this->backupFile, 1, 1);//显示备份完成后的路径
            }
        } catch (Exception $e) {
            return false;
           // return array('code' => -1, 'msg' => $e->getMessage());
        }

        return $this->backupFile . '.gz';//返回备份文件名称
    }

    /**
     * 保存SQL到文件
     * @param string $sql
     */
    protected function saveFile(&$sql)
    {
        if (!$sql) return false;

        try {

            if (!file_exists($this->backupDir)) {
                @mkdir($this->backupDir, 0777, true);
            }

            @file_put_contents($this->backupDir . '/' . $this->backupFile, $sql, FILE_APPEND | LOCK_EX);

        } catch (Exception $e) {
            // print_r($e->getMessage());
            return false;
        }

        return true;
    }

    /*
     * Gzip 格式压缩备份
     *
     * @param integer $level GZIP 压缩级别(默认: 9)
     * @return string 备份的文件名称
     */
    protected function gzipBackupFile($level = 9)
    {
        if (!$this->gzipBackupFile) {
            return true;
        }

        $source = $this->backupDir . '/' . $this->backupFile;
        $dest = $source . '.gz';

        if ($this->output) $this->obfPrint('数据备份地址（gz压缩格式）：' . $dest . '... ', 1, 0);//正在备份的文件

        $mode = 'wb' . $level;
        if ($fpOut = gzopen($dest, $mode)) {
            if ($fpIn = fopen($source, 'rb')) {
                while (!feof($fpIn)) {
                    gzwrite($fpOut, fread($fpIn, 1024 * 256));
                }
                fclose($fpIn);
            } else {
                return false;
            }
            gzclose($fpOut);
            if (!unlink($source)) {
                return false;
            }
        } else {
            return false;
        }
        if ($this->output) $this->obfPrint('OK');//备份结果
        return $dest;
    }

    /**
     * 强制打印过程信息
     */
    public function obfPrint($msg = '', $lineBreaksBefore = 0, $lineBreaksAfter = 1)
    {
        if (!$msg) {
            return false;
        }

        $output = '';

        if (php_sapi_name() != "cli") {
            $lineBreak = "<br />";
        } else {
            $lineBreak = "\n";
        }

        if ($lineBreaksBefore > 0) {
            for ($i = 1; $i <= $lineBreaksBefore; $i++) {
                $output .= $lineBreak;
            }
        }

        $output .= $msg;

        if ($lineBreaksAfter > 0) {
            for ($i = 1; $i <= $lineBreaksAfter; $i++) {
                $output .= $lineBreak;
            }
        }


        // 打印页面用
        $this->output .= str_replace('<br />', '\n', $output);

        echo $output;


        if (php_sapi_name() != "cli") {
            ob_flush();
        }

        $this->output .= " ";

        flush();
    }

    /**
     * 设置页面进度信息
     *
     */
    public function getOutput()
    {
        $this->output = true;
        return $this->output;
    }
}