<?php
require_once __DIR__ . '/../includes/db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => '未授权访问']));
}

$db = Database::getInstance();
$site_id = $_GET['id'] ?? null;

if (!$site_id) {
    http_response_code(400);
    die('缺少站点ID');
}

try {
    // 获取站点信息
    $site = $db->query("SELECT * FROM sites WHERE id = ?", [$site_id])->fetch();
    if (!$site) {
        throw new Exception('站点不存在');
    }

    // 记录访问日志
    $db->insert('logs', [
        'type' => 'operation',
        'message' => "访问站点: {$site['name']}"
    ]);

    // 重定向到站点
    header('Location: ' . $site['domain']);
    exit;

} catch (Exception $e) {
    // 记录错误日志
    $db->insert('logs', [
        'type' => 'error',
        'message' => "访问站点失败: {$e->getMessage()}"
    ]);
    
    die('访问站点失败: ' . $e->getMessage());
}
