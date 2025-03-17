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
    die(json_encode(['error' => '缺少站点ID']));
}

try {
    // 获取站点信息
    $site = $db->query("SELECT * FROM sites WHERE id = ?", [$site_id])->fetch();
    if (!$site) {
        throw new Exception('站点不存在');
    }

    // 使用curl访问站点获取数据
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $site['domain']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIE, $site['cookie']);
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        throw new Exception('无法访问站点');
    }

    // 解析HTML获取统计信息（示例使用正则表达式，实际应根据具体站点调整）
    $stats = [
        'upload' => '0',
        'download' => '0',
        'ratio' => '0',
        'bonus' => '0'
    ];

    // 上传量
    if (preg_match('/上[传傳]量?[：:]\s*([0-9\.]+\s*[KMGT]?i?B)/i', $response, $matches)) {
        $stats['upload'] = $matches[1];
    }

    // 下载量
    if (preg_match('/下[载載]量?[：:]\s*([0-9\.]+\s*[KMGT]?i?B)/i', $response, $matches)) {
        $stats['download'] = $matches[1];
    }

    // 分享率
    if (preg_match('/分享率[：:]\s*([0-9\.]+)/i', $response, $matches)) {
        $stats['ratio'] = $matches[1];
    }

    // 魔力值
    if (preg_match('/(魔力值?|积分)[：:]\s*([0-9\.]+)/i', $response, $matches)) {
        $stats['bonus'] = $matches[2];
    }

    // 更新数据库
    $db->query("
        INSERT INTO site_stats (site_id, upload, download, ratio, bonus)
        VALUES (?, ?, ?, ?, ?)
    ", [
        $site_id,
        $stats['upload'],
        $stats['download'],
        $stats['ratio'],
        $stats['bonus']
    ]);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    // 记录错误日志
    $db->insert('logs', [
        'type' => 'error',
        'message' => "获取站点统计失败: {$e->getMessage()}"
    ]);
}
