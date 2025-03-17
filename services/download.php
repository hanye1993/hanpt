<?php
require_once __DIR__ . '/../includes/db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => '未授权访问']));
}

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['url']) || !isset($data['downloader'])) {
    http_response_code(400);
    die(json_encode(['error' => '参数错误']));
}

$url = $data['url'];
$downloader_id = $data['downloader'];

try {
    $db = Database::getInstance();
    
    // 获取下载器配置
    $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
    if (!$downloader) {
        throw new Exception('下载器不存在或已禁用');
    }

    // 根据下载器类型处理下载请求
    switch ($downloader['type']) {
        case 'transmission':
            $result = addToTransmission($url, $downloader);
            break;
        case 'qbittorrent':
            $result = addToQBittorrent($url, $downloader);
            break;
        case 'direct':
            $result = directDownload($url);
            break;
        default:
            throw new Exception('不支持的下载器类型');
    }

    // 记录下载历史
    $db->insert('download_history', [
        'user_id' => $_SESSION['user_id'],
        'url' => $url,
        'downloader_id' => $downloader_id,
        'status' => $result['success'] ? 'success' : 'failed',
        'message' => $result['message'] ?? '',
        'created_at' => date('Y-m-d H:i:s')
    ]);

    echo json_encode([
        'success' => $result['success'],
        'message' => $result['message'] ?? ''
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Transmission下载器
function addToTransmission($url, $downloader) {
    $config = json_decode($downloader['config'], true);
    if (!$config) {
        throw new Exception('下载器配置格式错误');
    }

    $rpc_url = $config['rpc_url'];
    $username = $config['username'];
    $password = $config['password'];

    // 构建RPC请求
    $data = [
        'method' => 'torrent-add',
        'arguments' => [
            'filename' => $url
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $rpc_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Transmission-Session-Id: ' . getTransmissionSessionId($rpc_url, $username, $password)
    ]);
    
    if ($username && $password) {
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    }
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        throw new Exception('Transmission请求失败');
    }

    $result = json_decode($response, true);
    return [
        'success' => $result['result'] === 'success',
        'message' => $result['result'] === 'success' ? '已添加到Transmission' : $result['result']
    ];
}

// 获取Transmission Session ID
function getTransmissionSessionId($url, $username, $password) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($username && $password) {
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    }
    
    $response = curl_exec($ch);
    curl_close($ch);

    if (preg_match('/X-Transmission-Session-Id: ([^\n]+)/', $response, $matches)) {
        return trim($matches[1]);
    }
    
    throw new Exception('无法获取Transmission Session ID');
}

// qBittorrent下载器
function addToQBittorrent($url, $downloader) {
    $config = json_decode($downloader['config'], true);
    if (!$config) {
        throw new Exception('下载器配置格式错误');
    }

    $api_url = $config['api_url'];
    $username = $config['username'];
    $password = $config['password'];

    // 登录qBittorrent
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '/api/v2/auth/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => $username,
        'password' => $password
    ]));
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($status !== 200 || $response !== 'Ok.') {
        throw new Exception('qBittorrent登录失败');
    }

    // 获取Cookie
    $cookie = curl_getinfo($ch, CURLINFO_COOKIELIST);
    curl_close($ch);

    // 添加种子
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '/api/v2/torrents/add');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie[0]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'urls' => $url
    ]);
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        throw new Exception('添加种子到qBittorrent失败');
    }

    return [
        'success' => true,
        'message' => '已添加到qBittorrent'
    ];
}

// 直接下载
function directDownload($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        throw new Exception('下载种子文件失败');
    }

    // 生成文件名
    $filename = 'torrent_' . date('YmdHis') . '.torrent';
    $downloadDir = __DIR__ . '/../downloads/';
    
    // 确保下载目录存在
    if (!file_exists($downloadDir)) {
        mkdir($downloadDir, 0777, true);
    }

    // 保存文件
    if (file_put_contents($downloadDir . $filename, $response) === false) {
        throw new Exception('保存种子文件失败');
    }

    return [
        'success' => true,
        'message' => '种子文件已保存到downloads目录'
    ];
} 