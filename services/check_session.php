<?php
// 设置响应头
header('Content-Type: application/json');

// 启动会话
session_start();

// 检查用户是否已登录
$logged_in = isset($_SESSION['user_id']);

// 返回会话状态
echo json_encode([
    'success' => true,
    'logged_in' => $logged_in,
    'session_id' => session_id(),
    'time' => date('Y-m-d H:i:s')
]); 