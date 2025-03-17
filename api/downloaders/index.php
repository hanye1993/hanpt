<?php
require_once '../../includes/db.php';

header('Content-Type: application/json');

// 获取请求路径
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

// 移除 'api' 和 'downloaders'
array_shift($segments); // 移除 'api'
array_shift($segments); // 移除 'downloaders'

// 检查是否有足够的段来处理请求
if (count($segments) < 1) {
    die(json_encode([
        'success' => false,
        'message' => '无效的请求路径'
    ]));
}

$db = Database::getInstance();

// 处理下载器请求
$downloaderName = urldecode($segments[0]);

// 获取下载器信息
$downloader = $db->query("SELECT * FROM downloaders WHERE name = ? AND status = 1", [$downloaderName])->fetch();
if (!$downloader) {
    die(json_encode([
        'success' => false,
        'message' => '下载器不存在或已禁用'
    ]));
}

// 处理种子请求
if (count($segments) >= 3 && $segments[1] === 'torrent') {
    $hash = $segments[2];
    
    // 处理 peers 请求
    if (count($segments) >= 4 && $segments[3] === 'peers') {
        // 获取种子的peers信息
        if ($downloader['type'] === 'transmission') {
            require_once '../../includes/transmission.php';
            $transmission = new Transmission($downloader['domain'], $downloader['username'], $downloader['password']);
            $result = $transmission->getTorrentPeers($hash);
            if (!$result['success']) {
                die(json_encode([
                    'success' => false,
                    'message' => '获取Peers失败: ' . ($result['error'] ?? '未知错误')
                ]));
            }
            
            // 检查peers是否被封禁
            try {
                $bannedIps = $db->query("
                    SELECT ip, unban_time 
                    FROM peer_bans 
                    WHERE unban_time IS NULL OR unban_time > NOW()
                ")->fetchAll(PDO::FETCH_KEY_PAIR);
            } catch (PDOException $e) {
                // 如果表不存在，使用空数组
                $bannedIps = [];
            }
            
            // 合并已连接和未连接的peers
            $allPeers = [];
            
            // 处理已连接的peers
            foreach ($result['peers'] as $peer) {
                $allPeers[] = [
                    'ip' => $peer['ip'],
                    'client' => $peer['client'] ?? 'Unknown',
                    'up_speed' => $peer['up_speed'] ?? 0,
                    'dl_speed' => $peer['dl_speed'] ?? 0,
                    'progress' => $peer['progress'] ?? 0,
                    'status' => 'connected'
                ];
            }
            
            // 处理未连接的peers
            foreach ($result['trackerPeers'] as $peer) {
                $allPeers[] = [
                    'ip' => $peer['ip'] ?? 'Unknown',
                    'client' => 'Unknown',
                    'up_speed' => 0,
                    'dl_speed' => 0,
                    'progress' => 0,
                    'status' => 'not_connected'
                ];
            }
            
            die(json_encode([
                'success' => true,
                'peers' => $allPeers
            ]));
        } else {
            require_once '../../includes/qbittorrent.php';
            $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
            $connectedPeers = $qb->getTorrentPeers($hash);
            $trackerPeers = $qb->getTorrentTrackers($hash);
            
            // 检查peers是否被封禁
            try {
                $bannedIps = $db->query("
                    SELECT ip, unban_time 
                    FROM peer_bans 
                    WHERE unban_time IS NULL OR unban_time > NOW()
                ")->fetchAll(PDO::FETCH_KEY_PAIR);
            } catch (PDOException $e) {
                // 如果表不存在，使用空数组
                $bannedIps = [];
            }
            
            // 合并已连接和未连接的peers
            $allPeers = [];
            
            // 处理已连接的peers
            foreach ($connectedPeers as $ip => $peer) {
                $allPeers[] = [
                    'ip' => $ip,
                    'client' => $peer['client'] ?? 'Unknown',
                    'up_speed' => $peer['up_speed'] ?? 0,
                    'dl_speed' => $peer['dl_speed'] ?? 0,
                    'progress' => $peer['progress'] ?? 0,
                    'status' => 'connected'
                ];
            }
            
            // 处理未连接的peers
            foreach ($trackerPeers as $peer) {
                $allPeers[] = [
                    'ip' => $peer['ip'] ?? 'Unknown',
                    'client' => 'Unknown',
                    'up_speed' => 0,
                    'dl_speed' => 0,
                    'progress' => 0,
                    'status' => 'not_connected'
                ];
            }
            
            die(json_encode([
                'success' => true,
                'peers' => $allPeers
            ]));
        }
    } else {
        // 获取种子详情
        if ($downloader['type'] === 'transmission') {
            require_once '../../includes/transmission.php';
            $transmission = new Transmission($downloader['domain'], $downloader['username'], $downloader['password']);
            $result = $transmission->getTorrent($hash);
            if (!$result['success']) {
                die(json_encode([
                    'success' => false,
                    'message' => '获取种子详情失败: ' . ($result['error'] ?? '未知错误')
                ]));
            }
            die(json_encode([
                'success' => true,
                'torrent' => $result['torrent']
            ]));
        } else {
            require_once '../../includes/qbittorrent.php';
            $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
            $torrent = $qb->getTorrent($hash);
            die(json_encode([
                'success' => true,
                'torrent' => $torrent
            ]));
        }
    }
} else {
    // 获取下载器信息
    if ($downloader['type'] === 'transmission') {
        require_once '../../includes/transmission.php';
        $transmission = new Transmission($downloader['domain'], $downloader['username'], $downloader['password']);
        $result = $transmission->getTorrents();
        if (!$result['success']) {
            die(json_encode([
                'success' => false,
                'message' => '获取种子列表失败: ' . ($result['error'] ?? '未知错误')
            ]));
        }
        die(json_encode([
            'success' => true,
            'torrents' => $result['torrents']
        ]));
    } else {
        require_once '../../includes/qbittorrent.php';
        $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
        $torrents = $qb->getTorrents();
        die(json_encode([
            'success' => true,
            'torrents' => $torrents
        ]));
    }
} 