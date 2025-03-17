<?php
require_once '../includes/db.php';
require_once '../includes/qbittorrent.php';
require_once '../includes/transmission.php';

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 设置超时时间
ini_set('max_execution_time', 300); // 5分钟
set_time_limit(300);

// 设置内存限制
ini_set('memory_limit', '256M');

// 获取数据库连接
$db = Database::getInstance();

// 检查是否为API请求
$is_api = isset($_GET['api']) && $_GET['api'] == 1;

// 如果不是API请求，输出HTML头部
if (!$is_api) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>吸血检测</title>
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
        <style>
            body { padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; }
            .log { margin-bottom: 10px; padding: 10px; border-radius: 4px; }
            .success { background-color: #d4edda; color: #155724; }
            .error { background-color: #f8d7da; color: #721c24; }
            .info { background-color: #e2e3e5; color: #383d41; }
            .warning { background-color: #fff3cd; color: #856404; }
            pre { white-space: pre-wrap; word-wrap: break-word; }
            .debug-info { margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>吸血检测</h1>';
} else {
    // API请求返回JSON
    header('Content-Type: application/json');
}

// 记录日志
$logs = [];
function log_message($type, $message) {
    global $logs, $is_api;
    $logs[] = ['type' => $type, 'message' => $message];
    if (!$is_api) {
        echo "<div class='log $type'>$message</div>";
        ob_flush();
        flush();
    }
}

// 检查数据库表
function check_database_tables() {
    global $db;
    
    try {
        // 检查 peer_bans 表是否存在
        $result = $db->query("SHOW TABLES LIKE 'peer_bans'")->rowCount();
        if ($result == 0) {
            log_message('error', 'peer_bans 表不存在，请先运行 update_vampire_db.php 更新数据库');
            return false;
        }
        
        // 检查 peer_checks 表是否存在
        $result = $db->query("SHOW TABLES LIKE 'peer_checks'")->rowCount();
        if ($result == 0) {
            log_message('error', 'peer_checks 表不存在，请先运行 update_vampire_db.php 更新数据库');
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        log_message('error', '检查数据库表失败: ' . $e->getMessage());
        return false;
    }
}

// 获取设置
function get_settings() {
    global $db;
    
    try {
        $settings = $db->query("SELECT * FROM settings WHERE name LIKE 'vampire_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // 设置默认值
        $defaults = [
            'vampire_enabled' => '1',
            'vampire_min_ratio' => '2',
            'vampire_min_upload' => '1048576', // 1MB
            'vampire_ban_duration' => '86400',  // 24小时
            'vampire_ratio_threshold' => '5',   // 吸血比例阈值
            'vampire_check_interval' => '300',  // 检查间隔
            'vampire_ban_threshold' => '3'      // 封禁阈值
        ];
        
        // 合并默认值和数据库值
        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }
        
        return $settings;
    } catch (Exception $e) {
        log_message('error', '获取设置失败: ' . $e->getMessage());
        return [
            'vampire_enabled' => '1',
            'vampire_min_ratio' => '2',
            'vampire_min_upload' => '1048576', // 1MB
            'vampire_ban_duration' => '86400',  // 24小时
            'vampire_ratio_threshold' => '5',   // 吸血比例阈值
            'vampire_check_interval' => '300',  // 检查间隔
            'vampire_ban_threshold' => '3'      // 封禁阈值
        ];
    }
}

// 获取下载器信息
function get_downloaders() {
    global $db;
    
    try {
        return $db->query("SELECT * FROM downloaders WHERE status = 1")->fetchAll();
    } catch (Exception $e) {
        log_message('error', '获取下载器信息失败: ' . $e->getMessage());
        return [];
    }
}

// 检查Peer是否为吸血行为
function check_peer($peer, $settings) {
    // 计算吸血比例
    $vampire_ratio = 0;
    
    // 如果上传速度为0，下载速度大于0，则可能是吸血行为
    if ($peer['up_speed'] == 0 && $peer['dl_speed'] > 0) {
        // 无限大的吸血比例，设置为100000%
        $vampire_ratio = 100000;
        return ['is_vampire' => true, 'ratio' => $vampire_ratio];
    }
    
    // 如果上传/下载比例小于设定值，且下载量大于设定值，则可能是吸血行为
    if ($peer['dl_speed'] > 0 && $peer['up_speed'] > 0) {
        // 计算下载/上传比例（吸血比例）
        $ratio = $peer['dl_speed'] / $peer['up_speed'];
        $vampire_ratio = round($ratio * 100, 2); // 转换为百分比并保留两位小数
        
        // 使用设置中的吸血比例阈值
        $ratio_threshold = $settings['vampire_ratio_threshold'];
        
        // 如果吸血比例超过设置的阈值，则判定为吸血行为
        if ($vampire_ratio > $ratio_threshold && $peer['dl_speed'] > $settings['vampire_min_upload']) {
            return ['is_vampire' => true, 'ratio' => $vampire_ratio];
        }
    }
    
    return ['is_vampire' => false, 'ratio' => $vampire_ratio];
}

// 记录检查结果
function record_check($downloader_id, $torrent_hash, $ip, $dl_speed, $up_speed, $is_banned, $vampire_ratio = 0) {
    global $db;
    
    try {
        $db->query("
            INSERT INTO peer_checks (downloader_id, torrent_hash, ip, download_speed, upload_speed, is_banned, vampire_ratio, check_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ", [$downloader_id, $torrent_hash, $ip, $dl_speed, $up_speed, $is_banned ? 1 : 0, $vampire_ratio]);
        
        return true;
    } catch (Exception $e) {
        log_message('error', "记录检查结果失败: $ip - " . $e->getMessage());
        return false;
    }
}

// 检查是否需要封禁
function should_ban($ip, $settings) {
    global $db;
    
    try {
        // 检查最近的检查记录
        $count = $db->query("
            SELECT COUNT(*) FROM peer_checks 
            WHERE ip = ? AND is_banned = 0 AND check_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ", [$ip, $settings['vampire_check_interval']])->fetchColumn();
        
        return $count >= $settings['vampire_ban_threshold'];
    } catch (Exception $e) {
        log_message('error', "检查是否需要封禁失败: $ip - " . $e->getMessage());
        return false;
    }
}

// 封禁Peer
function ban_peer($ip, $downloader_id, $settings) {
    global $db;
    
    try {
        // 检查是否已经被封禁
        $existing = $db->query("
            SELECT id FROM peer_bans 
            WHERE ip = ? AND (unban_time IS NULL OR unban_time > NOW())
        ", [$ip])->fetch();
        
        if ($existing) {
            log_message('info', "IP $ip 已经被封禁，跳过");
            return true;
        }
        
        // 添加封禁记录
        $ban_time = date('Y-m-d H:i:s');
        $auto_unban_time = date('Y-m-d H:i:s', time() + $settings['vampire_ban_duration']);
        $db->query("
            INSERT INTO peer_bans (ip, ban_time, auto_unban_time, reason, downloader_id)
            VALUES (?, ?, ?, '自动检测到吸血行为', ?)
        ", [$ip, $ban_time, $auto_unban_time, $downloader_id]);
        
        $ban_id = $db->lastInsertId();
        
        log_message('success', "封禁IP: $ip, 封禁时间: $ban_time, 自动解封时间: $auto_unban_time");
        
        // 尝试在下载器中封禁
        $downloader = $db->query("SELECT * FROM downloaders WHERE id = ?", [$downloader_id])->fetch();
        if ($downloader) {
            if ($downloader['type'] === 'transmission') {
                // Transmission 不支持直接封禁IP
                log_message('warning', "Transmission 不支持直接封禁IP: $ip");
            } else {
                require_once '../includes/qbittorrent.php';
                $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
                $result = $qb->banPeer($ip);
                if ($result) {
                    log_message('success', "在下载器 {$downloader['name']} 中封禁IP成功: $ip");
                } else {
                    log_message('error', "在下载器 {$downloader['name']} 中封禁IP失败: $ip");
                }
            }
        }
        
        // 更新统计信息
        try {
            // 增加封禁计数
            $db->query("
                UPDATE settings 
                SET value = value + 1 
                WHERE name = 'vampire_ban_count'
            ");
        } catch (Exception $e) {
            // 如果设置不存在，创建它
            try {
                $db->query("
                    INSERT INTO settings (name, value) 
                    VALUES ('vampire_ban_count', '1')
                ");
            } catch (Exception $e2) {
                log_message('error', "更新封禁计数失败: " . $e2->getMessage());
            }
        }
        
        return true;
    } catch (Exception $e) {
        log_message('error', "封禁Peer失败: $ip - " . $e->getMessage());
        return false;
    }
}

// 自动解封过期的封禁
function auto_unban_expired() {
    global $db;
    
    try {
        // 查找需要自动解封的记录
        $expired_bans = $db->query("
            SELECT pb.*, d.name as downloader_name, d.type as downloader_type, 
                  d.domain, d.username, d.password
            FROM peer_bans pb
            LEFT JOIN downloaders d ON pb.downloader_id = d.id
            WHERE pb.unban_time IS NULL 
            AND pb.auto_unban_time IS NOT NULL 
            AND pb.auto_unban_time <= NOW()
        ")->fetchAll();
        
        if (empty($expired_bans)) {
            log_message('info', "没有需要自动解封的记录");
            return true;
        }
        
        log_message('info', "找到 " . count($expired_bans) . " 条需要自动解封的记录");
        $unban_count = 0;
        
        foreach ($expired_bans as $ban) {
            try {
                // 更新解封时间
                $unban_time = date('Y-m-d H:i:s');
                $db->query("
                    UPDATE peer_bans 
                    SET unban_time = ? 
                    WHERE id = ?
                ", [$unban_time, $ban['id']]);
                
                log_message('success', "自动解封IP: {$ban['ip']}, 解封时间: $unban_time");
                $unban_count++;
                
                // 尝试在下载器中解封
                if ($ban['downloader_id'] && $ban['downloader_type'] === 'qbittorrent') {
                    $qb = new QBittorrent($ban['domain'], $ban['username'], $ban['password']);
                    $result = $qb->unbanPeer($ban['ip']);
                    if ($result) {
                        log_message('success', "在下载器 {$ban['downloader_name']} 中解封IP成功: {$ban['ip']}");
                    } else {
                        log_message('error', "在下载器 {$ban['downloader_name']} 中解封IP失败: {$ban['ip']}");
                    }
                }
            } catch (Exception $e) {
                log_message('error', "自动解封失败: {$ban['ip']} - " . $e->getMessage());
            }
        }
        
        // 更新统计信息
        if ($unban_count > 0) {
            try {
                // 增加解封计数
                $db->query("
                    UPDATE settings 
                    SET value = value + ? 
                    WHERE name = 'vampire_unban_count'
                ", [$unban_count]);
            } catch (Exception $e) {
                // 如果设置不存在，创建它
                try {
                    $db->query("
                        INSERT INTO settings (name, value) 
                        VALUES ('vampire_unban_count', ?)
                    ", [$unban_count]);
                } catch (Exception $e2) {
                    log_message('error', "更新解封计数失败: " . $e2->getMessage());
                }
            }
        }
        
        return true;
    } catch (Exception $e) {
        log_message('error', "自动解封过期记录失败: " . $e->getMessage());
        return false;
    }
}

// 主函数
function main() {
    // 检查数据库表
    if (!check_database_tables()) {
        return false;
    }
    
    // 获取设置
    $settings = get_settings();
    if ($settings['vampire_enabled'] != '1') {
        log_message('info', "吸血检测功能未启用");
        return true;
    }
    
    log_message('info', "开始吸血检测，设置: " . json_encode($settings));
    
    // 自动解封过期的封禁
    auto_unban_expired();
    
    // 获取下载器信息
    $downloaders = get_downloaders();
    if (empty($downloaders)) {
        log_message('warning', "没有找到启用的下载器");
        return true;
    }
    
    $check_count = 0;
    $vampire_count = 0;
    $ban_count = 0;
    
    // 对每个下载器进行检测
    foreach ($downloaders as $downloader) {
        log_message('info', "检测下载器: {$downloader['name']} ({$downloader['type']})");
        
        try {
            // 获取活动种子
            $active_torrents = [];
            
            if ($downloader['type'] === 'transmission') {
                $transmission = new Transmission($downloader['domain'], $downloader['username'], $downloader['password']);
                $result = $transmission->getTorrents();
                if (!$result['success']) {
                    log_message('error', "获取Transmission数据失败: " . ($result['error'] ?? '未知错误'));
                    continue;
                }
                $active_torrents = $result['torrents'];
            } else {
                $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
                $active_torrents = $qb->getTorrents();
            }
            
            if (empty($active_torrents)) {
                log_message('info', "下载器 {$downloader['name']} 没有活动种子");
                continue;
            }
            
            log_message('info', "下载器 {$downloader['name']} 有 " . count($active_torrents) . " 个活动种子");
            
            // 对每个种子进行检测
            foreach ($active_torrents as $torrent) {
                $torrent_hash = $torrent['hash'];
                
                // 获取Peers
                $peers = [];
                
                if ($downloader['type'] === 'transmission') {
                    $result = $transmission->getTorrentPeers($torrent_hash);
                    if (!$result['success']) {
                        log_message('error', "获取Transmission Peers失败: " . ($result['error'] ?? '未知错误'));
                        continue;
                    }
                    $peers = $result['peers'];
                } else {
                    $peers = $qb->getTorrentPeers($torrent_hash);
                }
                
                if (empty($peers)) {
                    continue;
                }
                
                // 检查每个Peer
                foreach ($peers as $peer) {
                    $check_count++;
                    
                    $ip = $peer['ip'];
                    $dl_speed = $peer['dl_speed'] ?? 0;
                    $up_speed = $peer['up_speed'] ?? 0;
                    
                    // 检查是否为吸血行为
                    $result = check_peer($peer, $settings);
                    
                    if ($result['is_vampire']) {
                        $vampire_count++;
                        log_message('warning', "检测到吸血行为: $ip, 下载速度: $dl_speed, 上传速度: $up_speed, 吸血比例: {$result['ratio']}%");
                        
                        // 记录检查结果
                        record_check($downloader['id'], $torrent_hash, $ip, $dl_speed, $up_speed, false, $result['ratio']);
                        
                        // 检查是否需要封禁
                        if (should_ban($ip, $settings)) {
                            if (ban_peer($ip, $downloader['id'], $settings)) {
                                $ban_count++;
                                
                                // 更新检查记录为已封禁
                                global $db;
                                $db->query("
                                    UPDATE peer_checks 
                                    SET is_banned = 1 
                                    WHERE ip = ? AND is_banned = 0
                                ", [$ip]);
                            }
                        }
                    } else {
                        // 记录正常Peer的检查结果，但不标记为吸血
                        record_check($downloader['id'], $torrent_hash, $ip, $dl_speed, $up_speed, false, $result['ratio']);
                    }
                }
            }
        } catch (Exception $e) {
            log_message('error', "检测下载器 {$downloader['name']} 失败: " . $e->getMessage());
        }
    }
    
    // 更新统计信息
    try {
        global $db;
        
        // 更新检查计数
        try {
            $db->query("
                UPDATE settings 
                SET value = value + ? 
                WHERE name = 'vampire_check_count'
            ", [$check_count]);
        } catch (Exception $e) {
            // 如果设置不存在，创建它
            try {
                $db->query("
                    INSERT INTO settings (name, value) 
                    VALUES ('vampire_check_count', ?)
                ", [$check_count]);
            } catch (Exception $e2) {
                log_message('error', "更新检查计数失败: " . $e2->getMessage());
            }
        }
        
        // 更新吸血行为计数
        if ($vampire_count > 0) {
            try {
                $db->query("
                    UPDATE settings 
                    SET value = value + ? 
                    WHERE name = 'vampire_vampire_count'
                ", [$vampire_count]);
            } catch (Exception $e) {
                // 如果设置不存在，创建它
                try {
                    $db->query("
                        INSERT INTO settings (name, value) 
                        VALUES ('vampire_vampire_count', ?)
                    ", [$vampire_count]);
                } catch (Exception $e2) {
                    log_message('error', "更新吸血行为计数失败: " . $e2->getMessage());
                }
            }
        }
        
        // 更新封禁计数
        if ($ban_count > 0) {
            try {
                $db->query("
                    UPDATE settings 
                    SET value = value + ? 
                    WHERE name = 'vampire_ban_count'
                ", [$ban_count]);
            } catch (Exception $e) {
                // 如果设置不存在，创建它
                try {
                    $db->query("
                        INSERT INTO settings (name, value) 
                        VALUES ('vampire_ban_count', ?)
                    ", [$ban_count]);
                } catch (Exception $e2) {
                    log_message('error', "更新封禁计数失败: " . $e2->getMessage());
                }
            }
        }
    } catch (Exception $e) {
        log_message('error', "更新统计信息失败: " . $e->getMessage());
    }
    
    log_message('success', "吸血检测完成: 检查 $check_count 个Peer, 发现 $vampire_count 个吸血行为, 封禁 $ban_count 个IP");
    
    return [
        'success' => true,
        'check_count' => $check_count,
        'vampire_count' => $vampire_count,
        'ban_count' => $ban_count
    ];
}

// 执行主函数
$start_time = microtime(true);
$result = main();
$end_time = microtime(true);
$execution_time = round($end_time - $start_time, 2);

// 输出结果
if ($is_api) {
    // API响应
    if (is_array($result)) {
        echo json_encode([
            'success' => $result['success'],
            'execution_time' => $execution_time,
            'logs' => $logs,
            'check_count' => $result['check_count'] ?? 0,
            'vampire_count' => $result['vampire_count'] ?? 0,
            'ban_count' => $result['ban_count'] ?? 0
        ]);
    } else {
        echo json_encode([
            'success' => (bool)$result,
            'execution_time' => $execution_time,
            'logs' => $logs,
            'check_count' => 0,
            'vampire_count' => 0,
            'ban_count' => 0
        ]);
    }
} else {
    // HTML响应
    echo "<div class='debug-info'>
        <h3>执行信息</h3>
        <p>执行时间: {$execution_time} 秒</p>
        <p>PHP版本: " . phpversion() . "</p>
        <p>当前时间: " . date('Y-m-d H:i:s') . "</p>
        <p>服务器信息: " . $_SERVER['SERVER_SOFTWARE'] . "</p>
    </div>";
    
    echo "<p><a href='../vampire.php' class='btn btn-primary'>返回吸血鬼管理页面</a></p>";
    echo "</div></body></html>";
} 