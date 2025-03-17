<?php
session_start();

if (isset($_SESSION['user_id'])) {
    require_once 'includes/db.php';
    $db = Database::getInstance();
    
    // 记录退出日志
    $db->insert('logs', [
        'type' => 'operation',
        'message' => "用户 {$_SESSION['username']} 退出登录"
    ]);
}

// 清除所有会话数据
session_destroy();

// 重定向到登录页面
header('Location: login.php');
exit;
