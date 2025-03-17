<?php
require_once __DIR__ . '/../includes/db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => '未授权访问']));
}

$db = Database::getInstance();

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$site_id = $data['id'] ?? null;

if (!$site_id) {
    http_response_code(400);
    die(json_encode(['error' => '缺少站点ID']));
}

try {
    // 检查今日是否已签到
    $today = date('Y-m-d');
    $attendance = $db->query("
        SELECT * FROM attendance 
        WHERE site_id = ? AND DATE(created_at) = ?
        ORDER BY created_at DESC 
        LIMIT 1
    ", [$site_id, $today])->fetch();

    if ($attendance) {
        die(json_encode([
            'success' => $attendance['status'],
            'message' => $attendance['status'] ? '今日已签到' : '今日签到失败，请稍后重试'
        ]));
    }

    // 获取站点信息
    $site = $db->query("SELECT * FROM sites WHERE id = ?", [$site_id])->fetch();
    if (!$site) {
        throw new Exception('站点不存在');
    }

    // 构建签到URL
    $attendance_url = rtrim($site['domain'], '/') . '/attendance.php';

    // 使用curl访问签到页面
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $attendance_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIE, $site['cookie']);
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 检查签到结果
    $success = false;
    $message = '';

    if ($status === 200) {
        // 检查返回内容是否包含成功信息（根据具体站点调整）
        if (
            stripos($response, '签到成功') !== false ||
            stripos($response, '已签到') !== false ||
            stripos($response, 'success') !== false
        ) {
            $success = true;
            $message = '签到成功';
        } else {
            $message = '签到失败：返回内容不包含成功信息';
        }
    } else {
        $message = "签到失败：HTTP状态码 {$status}";
    }

    // 记录签到结果
    $db->query("
        INSERT INTO attendance (site_id, status, message)
        VALUES (?, ?, ?)
    ", [$site_id, $success, $message]);

    // 记录操作日志
    $db->insert('logs', [
        'type' => 'operation',
        'message' => "站点 {$site['name']} 签到" . ($success ? '成功' : '失败')
    ]);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
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
        'message' => "签到出错: {$e->getMessage()}"
    ]);
}
