<?php
require_once '../includes/db.php';
require_once '../includes/qbittorrent.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

function send_json($data) {
    echo json_encode($data);
    exit;
}

function get_instance_data($downloader) {
    try {
        if ($downloader['type'] === 'transmission') {
            require_once '../includes/transmission.php';
            $transmission = new Transmission($downloader['domain'], $downloader['username'], $downloader['password']);
            $result = $transmission->getTorrents();
            if (!$result['success']) {
                error_log("获取Transmission数据失败: " . ($result['error'] ?? '未知错误'));
                return null;
            }
            $torrents = $result['torrents'];
        } else {
            require_once '../includes/qbittorrent.php';
            $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
            $torrents = $qb->getTorrents();
        }
        
        // 过滤活动种子（下载中或做种中）
        $active_torrents = array_filter($torrents, function($torrent) {
            return in_array($torrent['state'], ['downloading', 'uploading', 'stalledDL', 'stalledUP']);
        });
        
        return $active_torrents;
    } catch (Exception $e) {
        error_log("获取下载器数据失败: " . $e->getMessage());
        return null;
    }
}

function format_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function format_speed($bytes_per_sec) {
    return format_size($bytes_per_sec) . '/s';
}

switch ($action) {
    case 'stats':
        try {
            // 获取统计数据
            $stats = $db->query("
                SELECT 
                    COUNT(*) as total_peers,
                    (SELECT COUNT(*) FROM peer_bans WHERE unban_time IS NULL) as banned_peers,
                    (SELECT COUNT(*) FROM peer_bans WHERE unban_time IS NOT NULL) as unbanned_peers,
                    (SELECT COUNT(*) FROM peer_bans WHERE unban_time > NOW()) as active_bans
                FROM peer_bans
            ")->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // 如果表不存在，使用默认值
            $stats = [
                'total_peers' => 0,
                'banned_peers' => 0,
                'unbanned_peers' => 0,
                'active_bans' => 0
            ];
        }
        
        // 获取下载器状态
        $downloaders = $db->query("SELECT * FROM downloaders WHERE status = 1")->fetchAll();
        $downloaders_status = [];
        
        foreach ($downloaders as $downloader) {
            $active_torrents = get_instance_data($downloader);
            $connected_peers = 0;
            
            if ($active_torrents) {
                foreach ($active_torrents as $torrent) {
                    if (isset($torrent['num_leechs'])) {
                        $connected_peers += $torrent['num_leechs'];
                    }
                    if (isset($torrent['num_seeds'])) {
                        $connected_peers += $torrent['num_seeds'];
                    }
                }
            }
            
            $downloaders_status[] = [
                'id' => $downloader['id'],
                'activeTorrents' => $active_torrents ? count($active_torrents) : 0,
                'connectedPeers' => $connected_peers
            ];
        }
        
        send_json([
            'success' => true,
            'stats' => array_values($stats),
            'downloaders' => $downloaders_status
        ]);
        break;
        
    case 'list':
        $downloader_id = $_GET['id'] ?? 0;
        
        // 获取下载器信息
        $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
        if (!$downloader) {
            send_json([
                'success' => false,
                'error' => '下载器不存在或已禁用'
            ]);
        }
        
        // 获取种子列表
        $active_torrents = get_instance_data($downloader);
        if ($active_torrents === null) {
            send_json([
                'success' => false,
                'error' => '获取种子列表失败'
            ]);
        }
        
        // 格式化种子信息
        $formatted_torrents = [];
        foreach ($active_torrents as $torrent) {
            // 获取种子的吸血比例
            $vampire_ratio = 0;
            try {
                // 查询最近的检查记录中的最大吸血比例
                $result = $db->query("
                    SELECT MAX(vampire_ratio) as max_ratio 
                    FROM peer_checks 
                    WHERE torrent_hash = ? 
                    AND check_time > DATE_SUB(NOW(), INTERVAL 1 DAY)
                ", [$torrent['hash']])->fetch();
                
                if ($result && isset($result['max_ratio'])) {
                    $vampire_ratio = floatval($result['max_ratio']);
                }
                
                // 如果没有找到记录，尝试计算当前的吸血比例
                if ($vampire_ratio == 0 && isset($torrent['dlspeed']) && isset($torrent['upspeed'])) {
                    if ($torrent['upspeed'] > 0 && $torrent['dlspeed'] > 0) {
                        // 计算下载/上传比例
                        $ratio = $torrent['dlspeed'] / $torrent['upspeed'];
                        $vampire_ratio = round($ratio * 100, 2); // 转换为百分比并保留两位小数
                    }
                }
            } catch (Exception $e) {
                error_log("获取吸血比例失败: " . $e->getMessage());
            }
            
            $formatted_torrents[] = [
                'name' => $torrent['name'],
                'speed' => format_speed($torrent['dlspeed']) . ' ↓ ' . format_speed($torrent['upspeed']) . ' ↑',
                'size' => format_size($torrent['size']),
                'hash' => $torrent['hash'],
                'hash_short' => substr($torrent['hash'], 0, 6) . '...',
                'progress' => round($torrent['progress'] * 100, 2),
                'peers' => ($torrent['num_seeds'] ?? 0) . '/' . ($torrent['num_leechs'] ?? 0),
                'vampire_ratio' => $vampire_ratio,
                'dlspeed' => $torrent['dlspeed'],
                'upspeed' => $torrent['upspeed']
            ];
        }
        
        send_json([
            'success' => true,
            'torrents' => $formatted_torrents
        ]);
        break;
        
    case 'peers':
        $id = $_GET['id'] ?? null;
        $hash = $_GET['hash'] ?? null;
        
        if (!$id || !$hash) {
            die(json_encode([
                'success' => false,
                'message' => '缺少必要参数'
            ]));
        }
        
        try {
            // 获取下载器信息
            $downloader = $db->query("SELECT * FROM downloaders WHERE id = ?", [$id])->fetch();
            if (!$downloader) {
                die(json_encode([
                    'success' => false,
                    'message' => '下载器不存在'
                ]));
            }
            
            // 获取peers信息
            if ($downloader['type'] === 'transmission') {
                require_once '../includes/transmission.php';
                $transmission = new Transmission($downloader['domain'], $downloader['username'], $downloader['password']);
                $result = $transmission->getTorrentPeers($hash);
                if (!$result['success']) {
                    die(json_encode([
                        'success' => false,
                        'message' => '获取Peers失败: ' . ($result['error'] ?? '未知错误')
                    ]));
                }
                $peers = $result['peers'];
                $trackerPeers = $result['trackerPeers'] ?? [];
            } else {
                require_once '../includes/qbittorrent.php';
                $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
                $peers = $qb->getTorrentPeers($hash);
                $trackerPeers = $qb->getTorrentTrackers($hash);
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
            
            // 格式化peers数据
            $formattedPeers = [];
            
            // 处理已连接的peers
            foreach ($peers as $peer) {
                $ip = $peer['ip'];
                $formattedPeers[] = [
                    'ip' => $ip,
                    'client' => $peer['client'] ?? 'Unknown',
                    'up_speed' => $peer['up_speed'] ?? 0,
                    'dl_speed' => $peer['dl_speed'] ?? 0,
                    'progress' => round(($peer['progress'] ?? 0) * 100, 1),
                    'is_banned' => isset($bannedIps[$ip]),
                    'status' => 'connected',
                    'flags' => $peer['flags'] ?? []
                ];
            }
            
            // 处理Tracker返回的peers
            if (!empty($trackerPeers)) {
                foreach ($trackerPeers as $peer) {
                    // 检查是否已经在连接列表中
                    $exists = false;
                    foreach ($formattedPeers as $existingPeer) {
                        if ($existingPeer['ip'] === $peer['ip']) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if (!$exists) {
                        $ip = $peer['ip'];
                        $formattedPeers[] = [
                            'ip' => $ip,
                            'client' => 'Unknown',
                            'up_speed' => 0,
                            'dl_speed' => 0,
                            'progress' => 0,
                            'is_banned' => isset($bannedIps[$ip]),
                            'status' => $peer['status'] ?? 'not_connected',
                            'flags' => $peer['flags'] ?? []
                        ];
                    }
                }
            }
            
            die(json_encode([
                'success' => true,
                'peers' => $formattedPeers
            ]));
            
        } catch (Exception $e) {
            error_log("获取Peers失败: " . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => '获取Peers失败: ' . $e->getMessage()
            ]));
        }
        
    case 'downloader_info':
        $downloader_id = $_GET['id'] ?? 0;
        
        // 获取下载器信息
        $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
        if (!$downloader) {
            send_json([
                'success' => false,
                'error' => '下载器不存在或已禁用'
            ]);
        }
        
        send_json([
            'success' => true,
            'downloader' => [
                'id' => $downloader['id'],
                'name' => $downloader['name'],
                'type' => $downloader['type'],
                'domain' => $downloader['domain']
            ]
        ]);
        break;
        
    case 'ban':
        $ip = $_GET['ip'] ?? '';
        $downloader_id = isset($_GET['downloader_id']) && !empty($_GET['downloader_id']) ? intval($_GET['downloader_id']) : null;
        $reason = $_GET['reason'] ?? '手动封禁';
        
        if (empty($ip)) {
            send_json([
                'success' => false,
                'error' => '缺少IP参数'
            ]);
        }
        
        try {
            // 检查表是否存在
            try {
                $db->query("SELECT 1 FROM peer_bans LIMIT 1");
            } catch (PDOException $e) {
                // 如果表不存在，创建表并添加所有必要的列
                $db->query("
                    CREATE TABLE IF NOT EXISTS `peer_bans` (
                      `id` INT PRIMARY KEY AUTO_INCREMENT,
                      `ip` VARCHAR(45) NOT NULL COMMENT 'Peer IP',
                      `ban_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '封禁时间',
                      `unban_time` TIMESTAMP NULL DEFAULT NULL COMMENT '解封时间',
                      `auto_unban_time` TIMESTAMP NULL DEFAULT NULL COMMENT '自动解封时间',
                      `reason` VARCHAR(255) DEFAULT '手动封禁' COMMENT '封禁原因',
                      `downloader_id` INT NULL COMMENT '下载器ID',
                      KEY `ip_index` (`ip`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
                error_log("创建 peer_bans 表");
            }
            
            // 检查 ban_time 列是否存在
            try {
                $db->query("SELECT ban_time FROM peer_bans LIMIT 1");
            } catch (PDOException $e) {
                // 如果列不存在，添加该列
                $db->query("ALTER TABLE peer_bans ADD COLUMN ban_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '封禁时间' AFTER ip");
                error_log("添加 ban_time 列到 peer_bans 表");
            }
            
            // 检查 unban_time 列是否存在
            try {
                $db->query("SELECT unban_time FROM peer_bans LIMIT 1");
            } catch (PDOException $e) {
                // 如果列不存在，添加该列
                $db->query("ALTER TABLE peer_bans ADD COLUMN unban_time TIMESTAMP NULL DEFAULT NULL COMMENT '解封时间' AFTER ban_time");
                error_log("添加 unban_time 列到 peer_bans 表");
            }
            
            // 检查 auto_unban_time 列是否存在
            try {
                $db->query("SELECT auto_unban_time FROM peer_bans LIMIT 1");
            } catch (PDOException $e) {
                // 如果列不存在，添加该列
                $db->query("ALTER TABLE peer_bans ADD COLUMN auto_unban_time TIMESTAMP NULL DEFAULT NULL COMMENT '自动解封时间' AFTER unban_time");
                error_log("添加 auto_unban_time 列到 peer_bans 表");
            }
            
            // 检查IP是否已经被封禁
            $existing = $db->query("SELECT id FROM peer_bans WHERE ip = ? AND (unban_time IS NULL OR unban_time > NOW())", [$ip])->fetch();
            
            if ($existing) {
                send_json([
                    'success' => false,
                    'error' => 'IP已经被封禁'
                ]);
            }
            
            // 获取封禁持续时间设置
            $ban_duration = 86400; // 默认1天
            try {
                $ban_duration_setting = $db->query("SELECT value FROM settings WHERE name = 'vampire_ban_duration'")->fetchColumn();
                if ($ban_duration_setting) {
                    $ban_duration = intval($ban_duration_setting);
                }
            } catch (Exception $e) {
                error_log("获取封禁持续时间设置失败: " . $e->getMessage());
            }
            
            // 计算自动解封时间
            $ban_time = date('Y-m-d H:i:s');
            $auto_unban_time = date('Y-m-d H:i:s', time() + $ban_duration);
            
            // 添加封禁记录
            $db->query("
                INSERT INTO peer_bans (ip, ban_time, auto_unban_time, reason, downloader_id)
                VALUES (?, ?, ?, ?, ?)
            ", [$ip, $ban_time, $auto_unban_time, $reason, $downloader_id]);
            
            $ban_id = $db->lastInsertId();
            
            // 尝试在下载器中封禁IP
            if (isset($_GET['downloader_id'])) {
                $downloader = $db->query("SELECT * FROM downloaders WHERE id = ?", [$_GET['downloader_id']])->fetch();
                
                if ($downloader) {
                    if ($downloader['type'] === 'transmission') {
                        // Transmission 不支持直接封禁IP
                    } else {
                        require_once '../includes/qbittorrent.php';
                        $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
                        $qb->banPeer($ip);
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
                    error_log("更新封禁计数失败: " . $e2->getMessage());
                }
            }
            
            send_json([
                'success' => true,
                'message' => 'IP封禁成功',
                'ban_time' => $ban_time,
                'auto_unban_time' => $auto_unban_time
            ]);
        } catch (Exception $e) {
            send_json([
                'success' => false,
                'error' => '封禁失败: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'unban':
        $ip = $_GET['ip'] ?? '';
        
        if (empty($ip)) {
            send_json([
                'success' => false,
                'error' => '缺少IP参数'
            ]);
        }
        
        try {
            // 检查 ban_time 列是否存在
            try {
                $db->query("SELECT ban_time FROM peer_bans LIMIT 1");
            } catch (PDOException $e) {
                // 如果列不存在，添加该列
                $db->query("ALTER TABLE peer_bans ADD COLUMN ban_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '封禁时间' AFTER ip");
                error_log("添加 ban_time 列到 peer_bans 表");
            }
            
            // 检查 unban_time 列是否存在
            try {
                $db->query("SELECT unban_time FROM peer_bans LIMIT 1");
            } catch (PDOException $e) {
                // 如果列不存在，添加该列
                $db->query("ALTER TABLE peer_bans ADD COLUMN unban_time TIMESTAMP NULL DEFAULT NULL COMMENT '解封时间' AFTER ban_time");
                error_log("添加 unban_time 列到 peer_bans 表");
            }
            
            // 获取当前时间
            $unban_time = date('Y-m-d H:i:s');
            
            // 更新封禁记录
            $result = $db->query("
                UPDATE peer_bans 
                SET unban_time = ? 
                WHERE ip = ? AND (unban_time IS NULL OR unban_time > NOW())
            ", [$unban_time, $ip]);
            
            $affected_rows = $result->rowCount();
            
            // 解除所有下载器的封禁
            $downloaders = $db->query("SELECT * FROM downloaders WHERE status = 1")->fetchAll();
            $unbanSuccess = false;
            $errors = [];
            
            foreach ($downloaders as $downloader) {
                if ($downloader['type'] === 'transmission') {
                    // Transmission 不支持直接解封IP
                    continue;
                }
                
                try {
                    $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
                    $result = $qb->unbanPeer($ip);
                    if ($result) {
                        $unbanSuccess = true;
                    }
                } catch (Exception $e) {
                    $errors[] = "在下载器 {$downloader['name']} 中解封IP失败: " . $e->getMessage();
                }
            }
            
            // 更新统计信息
            if ($affected_rows > 0) {
                try {
                    // 增加解封计数
                    $db->query("
                        UPDATE settings 
                        SET value = value + ? 
                        WHERE name = 'vampire_unban_count'
                    ", [$affected_rows]);
                } catch (Exception $e) {
                    // 如果设置不存在，创建它
                    try {
                        $db->query("
                            INSERT INTO settings (name, value) 
                            VALUES ('vampire_unban_count', ?)
                        ", [$affected_rows]);
                    } catch (Exception $e2) {
                        error_log("更新解封计数失败: " . $e2->getMessage());
                    }
                }
            }
            
            send_json([
                'success' => true,
                'message' => 'IP解封成功' . ($errors ? '，但在部分下载器中解封失败' : ''),
                'unban_time' => $unban_time,
                'affected_rows' => $affected_rows
            ]);
        } catch (Exception $e) {
            send_json([
                'success' => false,
                'error' => '解封失败: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'delete_torrent':
        $hash = $_GET['hash'] ?? '';
        $downloader_id = $_GET['downloader_id'] ?? 0;
        
        if (empty($hash) || empty($downloader_id)) {
            send_json(['success' => false, 'error' => '缺少必要参数']);
        }
        
        try {
            // 获取下载器信息
            $downloader = $db->query("SELECT * FROM downloaders WHERE id = ?", [$downloader_id])->fetch();
            
            if (!$downloader) {
                send_json(['success' => false, 'error' => '下载器不存在']);
            }
            
            // 删除种子
            if ($downloader['type'] === 'transmission') {
                require_once '../includes/transmission.php';
                $transmission = new Transmission($downloader['domain'], $downloader['username'], $downloader['password']);
                $result = $transmission->removeTorrent($hash);
                
                if (!$result['success']) {
                    send_json(['success' => false, 'error' => '删除种子失败: ' . ($result['error'] ?? '未知错误')]);
                }
            } else {
                $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
                $result = $qb->deleteTorrent($hash);
                
                if (!$result) {
                    send_json(['success' => false, 'error' => '删除种子失败']);
                }
            }
            
            send_json(['success' => true, 'message' => '种子删除成功']);
        } catch (Exception $e) {
            send_json(['success' => false, 'error' => '删除种子失败: ' . $e->getMessage()]);
        }
        break;
        
    case 'update_setting':
        // 检查参数
        if (!isset($_GET['name']) || !isset($_GET['value'])) {
            send_json(['success' => false, 'error' => '缺少必要参数']);
        }
        
        $name = $_GET['name'];
        $value = $_GET['value'];
        
        // 验证设置名称
        $allowed_settings = [
            'vampire_enabled', 
            'vampire_refresh_interval', 
            'vampire_ban_duration', 
            'vampire_min_ratio', 
            'vampire_min_upload', 
            'vampire_check_interval', 
            'vampire_ban_threshold',
            'vampire_ratio_threshold',
            'vampire_rules_url',
            'vampire_auto_fetch_rules',
            'vampire_rules_fetch_interval'
        ];
        
        if (!in_array($name, $allowed_settings)) {
            send_json(['success' => false, 'error' => '不允许修改此设置']);
        }
        
        try {
            // 检查设置是否存在
            $exists = $db->query("SELECT COUNT(*) FROM settings WHERE name = ?", [$name])->fetchColumn() > 0;
            
            if ($exists) {
                // 更新设置
                $db->query("UPDATE settings SET value = ? WHERE name = ?", [$value, $name]);
            } else {
                // 插入设置
                $db->query("INSERT INTO settings (name, value) VALUES (?, ?)", [$name, $value]);
            }
            
            send_json(['success' => true, 'message' => '设置已更新']);
        } catch (PDOException $e) {
            send_json(['success' => false, 'error' => '数据库错误: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_settings':
        try {
            // 获取所有吸血相关设置
            $settings = $db->query("
                SELECT name, value FROM settings 
                WHERE name LIKE 'vampire_%'
            ")->fetchAll(PDO::FETCH_KEY_PAIR);
            
            send_json(['success' => true, 'settings' => $settings]);
        } catch (PDOException $e) {
            send_json(['success' => false, 'error' => '获取设置失败: ' . $e->getMessage()]);
        }
        break;
        
    case 'banned_peers_from_checks':
        try {
            // 查询peer_checks表中的所有记录，不限制is_banned=1
            $query = "SELECT * FROM peer_checks ORDER BY check_time DESC LIMIT 100";
            $peers = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
            
            send_json([
                'success' => true,
                'peers' => $peers
            ]);
        } catch (PDOException $e) {
            send_json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    default:
        send_json([
            'success' => false,
            'error' => '未知的操作类型'
        ]);
}
?>
