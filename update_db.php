<?php
require_once 'includes/db.php';

// 设置响应头
header('Content-Type: text/html; charset=utf-8');

// 检查是否已安装
if (!file_exists(__DIR__ . '/config/installed.php')) {
    die('系统尚未安装，请先运行安装程序。');
}

try {
    // 获取数据库连接
    $db = Database::getInstance();
    
    // 读取并执行SQL更新文件
    $sql = file_get_contents(__DIR__ . '/database/update_downloaders.sql');
    $db->query($sql);
    
    echo '数据库更新成功！<br>';
    echo '<a href="index.php">返回首页</a>';
} catch (Exception $e) {
    die('数据库更新失败：' . $e->getMessage());
} 