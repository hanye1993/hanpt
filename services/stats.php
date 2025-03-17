<?php
require_once __DIR__ . '/../includes/db.php';

// 设置响应头
header('Content-Type: application/json');

// 错误处理
function handleError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
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
    $downloaders = 0;
    $sites = 0;
    $torrents = 0;
    $torrentSize = 0;
    $activeTasks = 0;
    
    // 获取下载器数量
    try {
        $downloaders = $db->query("SELECT COUNT(*) as count FROM downloaders")->fetch()['count'];
    } catch (Exception $e) {
        error_log("获取下载器数量失败: " . $e->getMessage());
        $downloaders = 0;
    }
    
    // 获取站点数量
    try {
        $sites = $db->query("SELECT COUNT(*) as count FROM sites")->fetch()['count'];
    } catch (Exception $e) {
        error_log("获取站点数量失败: " . $e->getMessage());
        $sites = 0;
    }
    
    // 获取种子数量和大小
    try {
        $instances = $db->query("SELECT * FROM downloaders")->fetchAll();
        
        // 如果没有下载器，设置默认值
        if (empty($instances)) {
            $torrents = 0;
            $torrentSize = 0;
        } else {
            // 初始化每个下载器的统计数据
            $downloaderStats = [];
            
            // 遍历每个下载器获取数据
            foreach ($instances as $instance) {
                try {
                    $cookiePath = sys_get_temp_dir() . '/qbit_cookies/' . md5($instance['id']) . '.txt';
                    
                    // 检查cookie文件是否存在
                    if (!file_exists($cookiePath)) {
                        error_log("下载器 {$instance['id']} ({$instance['name']}): Cookie文件不存在: " . $cookiePath);
                        $downloaderStats[$instance['id']] = [
                            'name' => $instance['name'],
                            'torrents' => 0,
                            'size' => 0,
                            'active' => 0,
                            'error' => 'Cookie文件不存在'
                        ];
                        continue;
                    }
                    
                    error_log("下载器 {$instance['id']} ({$instance['name']}): 开始获取数据...");
                    
                    $ch = curl_init($instance['domain'] . '/api/v2/torrents/info');
                    curl_setopt_array($ch, [
                        CURLOPT_COOKIEFILE => $cookiePath,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_TIMEOUT => 5, // 设置超时时间为5秒
                        CURLOPT_CONNECTTIMEOUT => 3 // 连接超时时间为3秒
                    ]);
                    $response = curl_exec($ch);
                    
                    if ($response === false) {
                        $error = curl_error($ch);
                        error_log("下载器 {$instance['id']} ({$instance['name']}): cURL错误: " . $error);
                        $downloaderStats[$instance['id']] = [
                            'name' => $instance['name'],
                            'torrents' => 0,
                            'size' => 0,
                            'active' => 0,
                            'error' => 'cURL错误: ' . $error
                        ];
                        curl_close($ch);
                        continue;
                    }
                    
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($httpCode != 200) {
                        error_log("下载器 {$instance['id']} ({$instance['name']}): HTTP错误: " . $httpCode);
                        $downloaderStats[$instance['id']] = [
                            'name' => $instance['name'],
                            'torrents' => 0,
                            'size' => 0,
                            'active' => 0,
                            'error' => 'HTTP错误: ' . $httpCode
                        ];
                        curl_close($ch);
                        continue;
                    }
                    
                    curl_close($ch);
                    
                    $torrentList = json_decode($response, true);
                    
                    if (!is_array($torrentList)) {
                        error_log("下载器 {$instance['id']} ({$instance['name']}): 返回的数据不是有效的JSON数组");
                        $downloaderStats[$instance['id']] = [
                            'name' => $instance['name'],
                            'torrents' => 0,
                            'size' => 0,
                            'active' => 0,
                            'error' => '返回的数据不是有效的JSON数组'
                        ];
                        continue;
                    }
                    
                    // 计算当前下载器的统计数据
                    $downloaderTorrents = count($torrentList);
                    $downloaderSize = 0;
                    $downloaderActive = 0;
                    
                    foreach ($torrentList as $torrent) {
                        if (isset($torrent['size'])) {
                            $downloaderSize += (int)$torrent['size'];
                        }
                        
                        if (isset($torrent['state']) && in_array($torrent['state'], ['downloading', 'uploading'])) {
                            $downloaderActive++;
                        }
                    }
                    
                    // 保存当前下载器的统计数据
                    $downloaderStats[$instance['id']] = [
                        'name' => $instance['name'],
                        'torrents' => $downloaderTorrents,
                        'size' => $downloaderSize,
                        'active' => $downloaderActive,
                        'error' => null
                    ];
                    
                    error_log("下载器 {$instance['id']} ({$instance['name']}): 获取成功，种子数量: {$downloaderTorrents}, 体积: {$downloaderSize}, 活跃任务: {$downloaderActive}");
                    
                } catch (Exception $e) {
                    error_log("下载器 {$instance['id']} ({$instance['name']}): 处理时出错: " . $e->getMessage());
                    $downloaderStats[$instance['id']] = [
                        'name' => $instance['name'],
                        'torrents' => 0,
                        'size' => 0,
                        'active' => 0,
                        'error' => $e->getMessage()
                    ];
                    // 继续处理下一个下载器
                    continue;
                }
            }
            
            // 汇总所有下载器的统计数据
            $torrents = 0;
            $torrentSize = 0;
            $activeTasks = 0;
            
            foreach ($downloaderStats as $stat) {
                $torrents += $stat['torrents'];
                $torrentSize += $stat['size'];
                $activeTasks += $stat['active'];
            }
            
            error_log("汇总统计: 总种子数量: {$torrents}, 总体积: {$torrentSize}, 总活跃任务: {$activeTasks}");
        }
    } catch (Exception $e) {
        error_log("获取种子信息失败: " . $e->getMessage());
        // 设置默认值，但不中断执行
        $torrents = 0;
        $torrentSize = 0;
    }
    
    // 获取公告信息
    $announcements = [];
    $announcementFile = __DIR__ . '/../storage/announcements.txt';
    if (file_exists($announcementFile)) {
        $content = file_get_contents($announcementFile);
        $announcements = array_filter(explode("\n", $content));
    }
    
    // 返回数据
    echo json_encode([
        'downloaders' => (int)$downloaders,
        'sites' => (int)$sites,
        'activeTasks' => (int)$activeTasks,
        'torrents' => (int)$torrents,
        'torrentSize' => (int)$torrentSize,
        'announcements' => $announcements
    ]);
    
} catch (Exception $e) {
    handleError($e->getMessage());
}
