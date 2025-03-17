<?php
namespace app\helpers;

/**
 * 数据库工具类
 * 
 * 提供数据库备份和恢复功能
 */
class DatabaseHelper
{
    /**
     * 数据库配置
     * 
     * @var array
     */
    protected $config;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/database.php';
    }
    
    /**
     * 备份数据库
     * 
     * @param array $tables 要备份的表，为空则备份所有表
     * @param string $filename 备份文件名，为空则自动生成
     * @return string 备份文件路径
     */
    public function backup($tables = [], $filename = '')
    {
        // 获取数据库连接信息
        $host = $this->config['host'];
        $port = $this->config['port'];
        $database = $this->config['database'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        
        // 创建PDO连接
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // 如果没有指定表，则获取所有表
        if (empty($tables)) {
            $stmt = $pdo->query('SHOW TABLES');
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        
        // 如果没有指定文件名，则自动生成
        if (empty($filename)) {
            $filename = $database . '_' . date($this->config['backup']['filename_format']) . '.sql';
        }
        
        // 确保备份目录存在
        $backupDir = $this->config['backup']['path'];
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        
        $backupFile = $backupDir . '/' . $filename;
        $output = '';
        
        // 添加数据库信息和创建时间
        $output .= "-- Database: `{$database}`\n";
        $output .= "-- Backup Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        // 添加SET语句
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $output .= "SET AUTOCOMMIT = 0;\n";
        $output .= "START TRANSACTION;\n";
        $output .= "SET time_zone = \"+00:00\";\n\n";
        
        // 备份每个表
        foreach ($tables as $table) {
            // 获取表结构
            $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $row = $stmt->fetch();
            $createTable = $row['Create Table'];
            
            $output .= "-- Table structure for table `{$table}`\n";
            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $output .= $createTable . ";\n\n";
            
            // 获取表数据
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll();
            
            if (!empty($rows)) {
                $output .= "-- Dumping data for table `{$table}`\n";
                $output .= "INSERT INTO `{$table}` VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $rowValues[] = 'NULL';
                        } elseif (is_numeric($value)) {
                            $rowValues[] = $value;
                        } else {
                            $rowValues[] = $pdo->quote($value);
                        }
                    }
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }
                
                $output .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        // 添加COMMIT语句
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $output .= "COMMIT;\n";
        
        // 写入文件
        file_put_contents($backupFile, $output);
        
        return $backupFile;
    }
    
    /**
     * 恢复数据库
     * 
     * @param string $backupFile 备份文件路径
     * @return bool 是否成功
     */
    public function restore($backupFile)
    {
        // 检查文件是否存在
        if (!file_exists($backupFile)) {
            throw new \Exception("Backup file not found: {$backupFile}");
        }
        
        // 获取数据库连接信息
        $host = $this->config['host'];
        $port = $this->config['port'];
        $database = $this->config['database'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        
        // 创建PDO连接
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // 读取SQL文件
        $sql = file_get_contents($backupFile);
        
        // 执行SQL语句
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $pdo->exec($sql);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            return true;
        } catch (\PDOException $e) {
            throw new \Exception("Database restore failed: " . $e->getMessage());
        }
    }
    
    /**
     * 获取所有备份文件
     * 
     * @return array 备份文件列表
     */
    public function getBackupFiles()
    {
        $backupDir = $this->config['backup']['path'];
        if (!is_dir($backupDir)) {
            return [];
        }
        
        $files = [];
        $handle = opendir($backupDir);
        
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
                $files[] = [
                    'name' => $file,
                    'path' => $backupDir . '/' . $file,
                    'size' => filesize($backupDir . '/' . $file),
                    'date' => date('Y-m-d H:i:s', filemtime($backupDir . '/' . $file))
                ];
            }
        }
        
        closedir($handle);
        
        // 按日期排序（最新的在前）
        usort($files, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $files;
    }
    
    /**
     * 删除备份文件
     * 
     * @param string $filename 备份文件名
     * @return bool 是否成功
     */
    public function deleteBackup($filename)
    {
        $backupDir = $this->config['backup']['path'];
        $backupFile = $backupDir . '/' . $filename;
        
        if (file_exists($backupFile)) {
            return unlink($backupFile);
        }
        
        return false;
    }
} 