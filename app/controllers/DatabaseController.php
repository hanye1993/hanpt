<?php
namespace app\controllers;

use app\helpers\DatabaseHelper;

/**
 * 数据库管理控制器
 * 
 * 处理与数据库管理相关的请求。
 */
class DatabaseController extends Controller
{
    /**
     * 数据库工具类
     * 
     * @var DatabaseHelper
     */
    protected $dbHelper;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->dbHelper = new DatabaseHelper();
    }
    
    /**
     * 数据库备份列表页面
     * 
     * @return void
     */
    public function index()
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        // 获取备份文件列表
        $backupFiles = $this->dbHelper->getBackupFiles();
        
        // 设置页面标题和当前页面
        $page_title = '数据库管理';
        $current_page = 'database';
        
        // 渲染视图
        $this->render('database/index', [
            'page_title' => $page_title,
            'current_page' => $current_page,
            'backupFiles' => $backupFiles
        ]);
    }
    
    /**
     * 创建数据库备份
     * 
     * @return void
     */
    public function backup()
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/database');
        }
        
        try {
            // 创建备份
            $backupFile = $this->dbHelper->backup();
            
            // 记录操作日志
            $userId = $this->getUserId();
            $userModel = new \app\models\User();
            $userModel->logAction($userId, 'database_backup', '创建数据库备份: ' . basename($backupFile));
            
            $this->setFlash('success', '数据库备份创建成功');
        } catch (\Exception $e) {
            $this->setFlash('error', '数据库备份创建失败: ' . $e->getMessage());
        }
        
        $this->redirect('/database');
    }
    
    /**
     * 恢复数据库备份
     * 
     * @return void
     */
    public function restore()
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/database');
        }
        
        // 获取备份文件名
        $filename = $_POST['filename'] ?? '';
        
        if (empty($filename)) {
            $this->setFlash('error', '请选择要恢复的备份文件');
            $this->redirect('/database');
        }
        
        // 获取备份文件路径
        $backupDir = __DIR__ . '/../../storage/backups';
        $backupFile = $backupDir . '/' . $filename;
        
        try {
            // 恢复备份
            $this->dbHelper->restore($backupFile);
            
            // 记录操作日志
            $userId = $this->getUserId();
            $userModel = new \app\models\User();
            $userModel->logAction($userId, 'database_restore', '恢复数据库备份: ' . $filename);
            
            $this->setFlash('success', '数据库备份恢复成功');
        } catch (\Exception $e) {
            $this->setFlash('error', '数据库备份恢复失败: ' . $e->getMessage());
        }
        
        $this->redirect('/database');
    }
    
    /**
     * 删除数据库备份
     * 
     * @return void
     */
    public function delete()
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/database');
        }
        
        // 获取备份文件名
        $filename = $_POST['filename'] ?? '';
        
        if (empty($filename)) {
            $this->setFlash('error', '请选择要删除的备份文件');
            $this->redirect('/database');
        }
        
        try {
            // 删除备份
            $result = $this->dbHelper->deleteBackup($filename);
            
            if ($result) {
                // 记录操作日志
                $userId = $this->getUserId();
                $userModel = new \app\models\User();
                $userModel->logAction($userId, 'database_delete', '删除数据库备份: ' . $filename);
                
                $this->setFlash('success', '数据库备份删除成功');
            } else {
                $this->setFlash('error', '数据库备份删除失败');
            }
        } catch (\Exception $e) {
            $this->setFlash('error', '数据库备份删除失败: ' . $e->getMessage());
        }
        
        $this->redirect('/database');
    }
    
    /**
     * 下载数据库备份
     * 
     * @param string $filename 备份文件名
     * @return void
     */
    public function download($filename = '')
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        if (empty($filename)) {
            $this->setFlash('error', '请选择要下载的备份文件');
            $this->redirect('/database');
        }
        
        // 获取备份文件路径
        $backupDir = __DIR__ . '/../../storage/backups';
        $backupFile = $backupDir . '/' . $filename;
        
        if (!file_exists($backupFile)) {
            $this->setFlash('error', '备份文件不存在');
            $this->redirect('/database');
        }
        
        // 记录操作日志
        $userId = $this->getUserId();
        $userModel = new \app\models\User();
        $userModel->logAction($userId, 'database_download', '下载数据库备份: ' . $filename);
        
        // 设置响应头
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($backupFile));
        
        // 输出文件内容
        readfile($backupFile);
        exit;
    }
    
    /**
     * 格式化文件大小
     * 
     * @param int $bytes 字节数
     * @param int $precision 精度
     * @return string 格式化后的大小
     */
    public function formatFileSize($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
} 