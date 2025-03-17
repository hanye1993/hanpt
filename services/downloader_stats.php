<?php
require_once __DIR__ . '/../includes/db.php';

// 设置响应头
header('Content-Type: application/json');

// 错误处理
function handleError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// 会话检查
session_start();
if (!isset($_SESSION['user_id'])) {
    handleError('未授权访问', 401);
}

try {
    $db = Database::getInstance();
    
    // 初始化数据
    $totalTorrents = 0;
    $totalSize = 0;
    $activeTasks = 0;
    $downloaderStats = [];
    
    // 获取所有下载器
    $downloaders = $db->query("SELECT * FROM downloaders WHERE status = 1")->fetchAll();
    
    if (empty($downloaders)) {
        // 如果没有下载器，直接返回零值
        echo json_encode([
            'success' => true,
            'stats' => [
                'torrents' => 0,
                'torrentSize' => 0,
                'activeTasks' => 0
            ],
            'downloaderStats' => []
        ]);
        exit;
    }
    
    // 遍历每个下载器获取统计信息
    foreach ($downloaders as $downloader) {
        $stats = [
            'id' => $downloader['id'],
            'name' => $downloader['name'],
            'type' => $downloader['type'],
            'torrents' => 0,
            'size' => 0,
            'active' => 0,
            'error' => null
        ];
        
        try {
            // 根据下载器类型选择不同的API处理方式
            if (strtolower($downloader['type']) === 'qbittorrent') {
                // 处理 qBittorrent 下载器
                $cookiePath = sys_get_temp_dir() . '/qbit_cookies/' . md5($downloader['id']) . '.txt';
                
                // 检查cookie文件是否存在
                if (!file_exists($cookiePath)) {
                    error_log("下载器 {$downloader['id']} ({$downloader['name']}): Cookie文件不存在: " . $cookiePath);
                    $stats['error'] = 'Cookie文件不存在';
                    $downloaderStats[] = $stats;
                    continue;
                }
                
                // 获取种子列表
                $ch = curl_init($downloader['domain'] . '/api/v2/torrents/info');
                curl_setopt_array($ch, [
                    CURLOPT_COOKIEFILE => $cookiePath,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_CONNECTTIMEOUT => 3
                ]);
                $response = curl_exec($ch);
                
                if ($response === false) {
                    $error = curl_error($ch);
                    error_log("下载器 {$downloader['id']} ({$downloader['name']}): cURL错误: " . $error);
                    $stats['error'] = 'cURL错误: ' . $error;
                    curl_close($ch);
                    $downloaderStats[] = $stats;
                    continue;
                }
                
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode != 200) {
                    error_log("下载器 {$downloader['id']} ({$downloader['name']}): HTTP错误: " . $httpCode);
                    $stats['error'] = 'HTTP错误: ' . $httpCode;
                    curl_close($ch);
                    $downloaderStats[] = $stats;
                    continue;
                }
                
                curl_close($ch);
                
                $torrentList = json_decode($response, true);
                
                if (!is_array($torrentList)) {
                    error_log("下载器 {$downloader['id']} ({$downloader['name']}): 返回的数据不是有效的JSON数组");
                    $stats['error'] = '返回的数据不是有效的JSON数组';
                    $downloaderStats[] = $stats;
                    continue;
                }
                
                // 计算统计数据
                $stats['torrents'] = count($torrentList);
                $stats['size'] = 0;
                $stats['active'] = 0;
                
                foreach ($torrentList as $torrent) {
                    if (isset($torrent['size'])) {
                        $stats['size'] += (int)$torrent['size'];
                    }
                    
                    if (isset($torrent['state']) && in_array($torrent['state'], ['downloading', 'uploading'])) {
                        $stats['active']++;
                    }
                }
                
                // 累加总数
                $totalTorrents += $stats['torrents'];
                $totalSize += $stats['size'];
                $activeTasks += $stats['active'];
                
                error_log("下载器 {$downloader['id']} ({$downloader['name']}): 获取成功，种子数量: {$stats['torrents']}, 体积: {$stats['size']}, 活跃任务: {$stats['active']}");
            } else if (strtolower($downloader['type']) === 'transmission') {
                // 处理 Transmission 下载器
                // 这里可以添加 Transmission 的处理逻辑，类似于 qBittorrent
                // 由于示例中没有提供 Transmission 的具体实现，这里只是一个占位符
                $stats['error'] = 'Transmission 支持尚未实现';
                error_log("下载器 {$downloader['id']} ({$downloader['name']}): Transmission 支持尚未实现");
            } else {
                $stats['error'] = '不支持的下载器类型: ' . $downloader['type'];
                error_log("下载器 {$downloader['id']} ({$downloader['name']}): 不支持的下载器类型: " . $downloader['type']);
            }
        } catch (Exception $e) {
            $stats['error'] = $e->getMessage();
            error_log("下载器 {$downloader['id']} ({$downloader['name']}): 处理时出错: " . $e->getMessage());
        }
        
        $downloaderStats[] = $stats;
    }
    
    error_log("汇总统计: 总种子数量: {$totalTorrents}, 总体积: {$totalSize}, 总活跃任务: {$activeTasks}");
    
    // 返回数据
    echo json_encode([
        'success' => true,
        'stats' => [
            'torrents' => $totalTorrents,
            'torrentSize' => $totalSize,
            'activeTasks' => $activeTasks
        ],
        'downloaderStats' => $downloaderStats
    ]);
    
} catch (Exception $e) {
    handleError($e->getMessage());
} 