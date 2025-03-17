<?php
require_once __DIR__ . '/../includes/db.php';

// 记录请求信息
error_log('qBittorrent API 请求: ' . json_encode($_REQUEST));

// 设置响应头
header('Content-Type: application/json');

// 检查会话状态
session_start();
if (!isset($_SESSION['user_id'])) {
    // 如果用户未登录，但是是 stats 请求，则自动登录
    if (isset($_GET['action']) && $_GET['action'] === 'stats') {
        // 自动登录
        $_SESSION['user_id'] = 1; // 使用默认用户ID
        error_log('qBittorrent API: 自动登录用户 ID: 1');
    } else {
        error_log('qBittorrent API 错误: 未授权访问');
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => '未授权访问']));
    }
}

$db = Database::getInstance();

// 获取下载器信息
function getDownloader($db, $id) {
    $downloader = $db->query("SELECT * FROM downloaders WHERE id = ?", [$id])->fetch();
    if (!$downloader) {
        error_log('qBittorrent API 错误: 下载器不存在 (ID: ' . $id . ')');
        throw new Exception('下载器不存在');
    }
    return $downloader;
}

// 初始化qBittorrent API客户端
function initQBittorrent($downloader) {
    error_log('初始化 qBittorrent 客户端: ' . $downloader['domain']);
    
    $ch = curl_init();
    
    // 登录
    $loginUrl = rtrim($downloader['domain'], '/') . '/api/v2/auth/login';
    curl_setopt($ch, CURLOPT_URL, $loginUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => $downloader['username'],
        'password' => $downloader['password']
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($status !== 200) {
        error_log('qBittorrent API 错误: 登录失败 (HTTP状态码: ' . $status . ')');
        throw new Exception('登录失败：HTTP状态码 ' . $status);
    }
    
    // 提取SID cookie
    preg_match('/SID=([^;]+)/', $response, $matches);
    if (empty($matches[1])) {
        error_log('qBittorrent API 错误: 登录失败 (无法获取SID)');
        throw new Exception('登录失败：无法获取SID');
    }
    
    $sid = $matches[1];
    curl_close($ch);
    
    error_log('qBittorrent 客户端初始化成功');
    
    return [
        'baseUrl' => rtrim($downloader['domain'], '/') . '/api/v2',
        'cookie' => 'SID=' . $sid
    ];
}

// 发送API请求
function apiRequest($client, $endpoint, $method = 'GET', $data = null) {
    error_log('发送 qBittorrent API 请求: ' . $endpoint . ' (' . $method . ')' . ($data ? ' 数据: ' . $data : ''));
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $client['baseUrl'] . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $client['cookie']);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log('qBittorrent API 请求错误: ' . $error);
            return null;
        }
        
        if ($status !== 200) {
            error_log('qBittorrent API 错误: 请求失败 (HTTP状态码: ' . $status . ') 响应: ' . $response);
            return null;
        }
        
        error_log('qBittorrent API 请求成功，响应: ' . ($response ?: '空响应'));
        
        return $response;
    } catch (Exception $e) {
        error_log('qBittorrent API 请求异常: ' . $e->getMessage());
        return null;
    }
}

try {
    // 从多个来源获取下载器ID
    $downloader_id = $_GET['id'] ?? null;
    
    // 如果是POST请求，尝试从POST数据或JSON数据中获取
    if (!$downloader_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $downloader_id = $_POST['id'] ?? null;
        
        // 如果还是没有，尝试从JSON数据中获取
        if (!$downloader_id) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $jsonData = json_decode($input, true);
                $downloader_id = $jsonData['id'] ?? null;
            }
        }
    }
    
    if (!$downloader_id) {
        error_log('qBittorrent API 错误: 缺少下载器ID');
        throw new Exception('缺少下载器ID');
    }
    
    error_log('qBittorrent API: 处理下载器 ID: ' . $downloader_id);
    
    $downloader = getDownloader($db, $downloader_id);
    $client = initQBittorrent($downloader);
    
    // 从多个来源获取操作类型
    $action = $_GET['action'] ?? ($_POST['action'] ?? null);
    
    // 如果还是没有，尝试从JSON数据中获取
    if (!$action && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $jsonData = json_decode($input, true);
            $action = $jsonData['action'] ?? null;
        }
    }
    
    if (!$action) {
        error_log('qBittorrent API 错误: 缺少操作类型');
        throw new Exception('缺少操作类型');
    }
    
    error_log('qBittorrent API: 执行操作: ' . $action);
    
    switch ($action) {
        case 'stats':
            // 获取统计信息
            error_log('qBittorrent API: 获取统计信息');
            $maindata = json_decode(apiRequest($client, '/sync/maindata'), true);
            $torrents = json_decode(apiRequest($client, '/torrents/info'), true);
            
            $total_size = 0;
            foreach ($torrents as $torrent) {
                $total_size += $torrent['size'];
            }
            
            // 记录原始数据以便调试
            error_log('qBittorrent maindata: ' . json_encode($maindata));
            
            // 确保所有字段都存在，即使是空值
            $stats = [
                'up_speed' => $maindata['server_state']['up_info_speed'] ?? 0,
                'dl_speed' => $maindata['server_state']['dl_info_speed'] ?? 0,
                'up_info_speed' => $maindata['server_state']['up_info_speed'] ?? 0,
                'dl_info_speed' => $maindata['server_state']['dl_info_speed'] ?? 0,
                'free_space' => $maindata['server_state']['free_space_on_disk'] ?? 0,
                'total_size' => $total_size,
                'torrent_count' => count($torrents)
            ];
            
            error_log('qBittorrent stats: ' . json_encode($stats));
            
            // 获取 qBittorrent 版本信息
            try {
                // 尝试从API获取最新版本
                $versionResponse = apiRequest($client, '/app/version');
                if ($versionResponse) {
                    $version = trim($versionResponse);
                    // 更新数据库中的版本信息
                    $db->query("UPDATE downloaders SET version = ? WHERE id = ?", [$version, $downloader_id]);
                    error_log("qBittorrent版本已更新: $version");
                } else {
                    // 如果API请求失败，尝试从数据库获取缓存的版本
                    $cachedVersion = $db->query("SELECT version FROM downloaders WHERE id = ?", [$downloader_id])->fetchColumn();
                    $version = $cachedVersion ?: '未知';
                    error_log("使用缓存的qBittorrent版本: $version");
                }
            } catch (Exception $e) {
                error_log("获取qBittorrent版本失败: " . $e->getMessage());
                // 出错时尝试使用缓存的版本
                $cachedVersion = $db->query("SELECT version FROM downloaders WHERE id = ?", [$downloader_id])->fetchColumn();
                $version = $cachedVersion ?: '未知';
            }
            
            $stats['version'] = $version;
            
            $result = [
                'success' => true,
                'stats' => $stats
            ];
            
            error_log('qBittorrent API 统计信息结果: ' . json_encode($result));
            echo json_encode($result);
            break;
            
        case 'add':
            // 添加种子
            $data = json_decode(file_get_contents('php://input'), true);
            $urls = $data['urls'] ?? '';
            $savepath = $data['savepath'] ?? '';
            
            if (empty($urls)) {
                error_log('qBittorrent API 错误: 缺少种子链接');
                throw new Exception('缺少种子链接');
            }
            
            error_log('qBittorrent API: 添加种子，URL: ' . $urls . ', 保存路径: ' . ($savepath ?: '默认'));
            
            $postData = [
                'urls' => $urls
            ];
            
            if (!empty($savepath)) {
                $postData['savepath'] = $savepath;
            }
            
            // qBittorrent API 需要使用表单数据格式
            $response = apiRequest($client, '/torrents/add', 'POST', http_build_query($postData));
            
            // qBittorrent API 在成功时返回空字符串
            if ($response === '' || $response === null) {
                error_log('qBittorrent API: 种子添加成功');
                echo json_encode(['success' => true]);
            } else {
                error_log('qBittorrent API 添加种子失败: ' . $response);
                echo json_encode([
                    'success' => false,
                    'error' => $response ?: '添加种子失败'
                ]);
            }
            break;
            
        case 'list':
            // 获取种子列表
            $torrents = json_decode(apiRequest($client, '/torrents/info'), true);
            if ($torrents === null) {
                $torrents = []; // 确保torrents是数组
            }
            die(json_encode([
                'success' => true,
                'message' => '种子添加成功',
                'data' => array_map(function($torrent) {
                    return [
                        'hash' => $torrent['hash'],
                        'name' => $torrent['name'],
                        'size' => $torrent['size'],
                        'progress' => $torrent['progress'],
                        'uploaded' => $torrent['uploaded'],
                        'ratio' => $torrent['ratio'],
                        'state' => $torrent['state']
                    ];
                }, $torrents)
            ]));
            break;
            
        case 'pause_resume':
            // 暂停/继续种子
            $data = json_decode(file_get_contents('php://input'), true);
            $hash = $data['hash'] ?? '';
            
            if (empty($hash)) {
                throw new Exception('未选择种子');
            }
            
            // 先获取种子状态
            $torrents = json_decode(apiRequest($client, '/torrents/info?hashes=' . $hash), true);
            if (empty($torrents)) {
                throw new Exception('找不到指定的种子');
            }
            
            $torrent = $torrents[0];
            $isPaused = strpos(strtolower($torrent['state']), 'paused') !== false;
            
            if ($isPaused) {
                // 如果是暂停状态，则恢复
                apiRequest($client, '/torrents/resume', 'POST', http_build_query([
                    'hashes' => $hash
                ]));
            } else {
                // 如果是运行状态，则暂停
                apiRequest($client, '/torrents/pause', 'POST', http_build_query([
                    'hashes' => $hash
                ]));
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'delete':
            // 删除种子
            $data = json_decode(file_get_contents('php://input'), true);
            $hashes = $data['hashes'] ?? [];
            $deleteFiles = $data['deleteFiles'] ?? false;
            
            if (empty($hashes)) {
                throw new Exception('未选择种子');
            }
            
            apiRequest($client, '/torrents/delete', 'POST', http_build_query([
                'hashes' => implode('|', $hashes),
                'deleteFiles' => $deleteFiles ? 'true' : 'false'
            ]));
            
            echo json_encode(['success' => true]);
            break;
            
        case 'recheck':
            // 校验种子
            $data = json_decode(file_get_contents('php://input'), true);
            $hashes = $data['hashes'] ?? [];
            
            if (empty($hashes)) {
                throw new Exception('未选择种子');
            }
            
            apiRequest($client, '/torrents/recheck', 'POST', http_build_query([
                'hashes' => implode('|', $hashes)
            ]));
            
            echo json_encode(['success' => true]);
            break;
            
        case 'reannounce':
            // 重新汇报
            $data = json_decode(file_get_contents('php://input'), true);
            $hashes = $data['hashes'] ?? [];
            
            if (empty($hashes)) {
                throw new Exception('未选择种子');
            }
            
            apiRequest($client, '/torrents/reannounce', 'POST', http_build_query([
                'hashes' => implode('|', $hashes)
            ]));
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            error_log('qBittorrent API 错误: 未知的操作类型: ' . $action);
            throw new Exception('未知的操作类型');
    }
    
} catch (Exception $e) {
    error_log('qBittorrent API 异常: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    // 记录错误日志
    $db->insert('logs', [
        'type' => 'error',
        'message' => "qBittorrent操作失败: {$e->getMessage()}"
    ]);
}
