<?php
require_once '../includes/db.php';

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
        <title>获取并应用封禁规则</title>
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
            .rule-item { margin-bottom: 5px; padding: 5px; border-radius: 4px; background-color: #f8f9fa; }
            .rule-ip { font-weight: bold; }
            .rule-comment { color: #6c757d; font-style: italic; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>获取并应用封禁规则</h1>';
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
        
        // 检查表结构
        $columns = [];
        $columnsResult = $db->query("SHOW COLUMNS FROM peer_bans");
        while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
            $columns[$column['Field']] = $column;
        }
        
        // 检查 ban_time 列是否存在
        if (!isset($columns['ban_time'])) {
            log_message('warning', 'peer_bans 表中的 ban_time 列不存在，尝试添加该列');
            try {
                $db->query("ALTER TABLE peer_bans ADD COLUMN ban_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '封禁时间' AFTER ip");
                log_message('success', '添加 ban_time 列成功');
            } catch (Exception $e2) {
                log_message('error', '添加 ban_time 列失败: ' . $e2->getMessage());
                return false;
            }
        }
        
        // 检查 unban_time 列是否存在
        if (!isset($columns['unban_time'])) {
            log_message('warning', 'peer_bans 表中的 unban_time 列不存在，尝试添加该列');
            try {
                $db->query("ALTER TABLE peer_bans ADD COLUMN unban_time TIMESTAMP NULL DEFAULT NULL COMMENT '解封时间' AFTER ban_time");
                log_message('success', '添加 unban_time 列成功');
            } catch (Exception $e2) {
                log_message('error', '添加 unban_time 列失败: ' . $e2->getMessage());
                return false;
            }
        }
        
        // 检查 auto_unban_time 列是否存在
        if (!isset($columns['auto_unban_time'])) {
            log_message('warning', 'peer_bans 表中的 auto_unban_time 列不存在，尝试添加该列');
            try {
                $db->query("ALTER TABLE peer_bans ADD COLUMN auto_unban_time TIMESTAMP NULL DEFAULT NULL COMMENT '自动解封时间' AFTER unban_time");
                log_message('success', '添加 auto_unban_time 列成功');
            } catch (Exception $e2) {
                log_message('error', '添加 auto_unban_time 列失败: ' . $e2->getMessage());
                return false;
            }
        }
        
        return true;
    } catch (Exception $e) {
        log_message('error', '检查数据库表失败: ' . $e->getMessage());
        return false;
    }
}

// 获取规则文件
function fetch_rules($url) {
    log_message('info', "正在从 $url 获取规则...");
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'PHP/BTN-Ban-Rules-Fetcher'
        ]
    ]);
    
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        log_message('error', '获取规则失败: ' . error_get_last()['message']);
        return false;
    }
    
    log_message('success', '获取规则成功，大小: ' . strlen($content) . ' 字节');
    return $content;
}

// 解析规则
function parse_rules($content) {
    log_message('info', '正在解析规则...');
    
    $lines = explode("\n", $content);
    $rules = [];
    $current_ip = null;
    $current_comments = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // 跳过头部信息
        if (strpos($line, '# [START]') === 0) continue;
        
        // 如果是注释行
        if (strpos($line, '#') === 0) {
            $current_comments[] = trim(substr($line, 1));
            continue;
        }
        
        // 提取IP/CIDR和注释
        $parts = explode('#', $line, 2);
        $ip = trim($parts[0]);
        $comment = isset($parts[1]) ? trim($parts[1]) : '';
        
        if (!empty($ip)) {
            if (!empty($comment)) {
                $current_comments[] = $comment;
            }
            
            $rules[] = [
                'ip' => $ip,
                'comments' => $current_comments
            ];
            
            $current_comments = [];
        }
    }
    
    log_message('success', '解析规则成功，共 ' . count($rules) . ' 条规则');
    return $rules;
}

// 应用规则到数据库
function apply_rules($rules) {
    global $db;
    
    log_message('info', '正在应用规则到数据库...');
    
    $success_count = 0;
    $skip_count = 0;
    $error_count = 0;
    
    // 获取当前已封禁的IP
    try {
        $banned_ips = $db->query("
            SELECT ip FROM peer_bans 
            WHERE (unban_time IS NULL OR unban_time > NOW())
            AND reason LIKE '%自动规则封禁%'
        ")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        log_message('error', '获取已封禁IP失败: ' . $e->getMessage());
        $banned_ips = [];
    }
    
    // 开始事务
    $db->beginTransaction();
    
    try {
        // 应用每条规则
        foreach ($rules as $index => $rule) {
            $ip = $rule['ip'];
            $comments = $rule['comments'];
            $reason = '自动规则封禁: ' . implode(' | ', $comments);
            
            // 如果IP已经被封禁，跳过
            if (in_array($ip, $banned_ips)) {
                $skip_count++;
                continue;
            }
            
            // 检查是否是CIDR格式
            $is_cidr = strpos($ip, '/') !== false;
            
            // 添加封禁记录
            try {
                $db->query("
                    INSERT INTO peer_bans (ip, ban_time, reason, downloader_id)
                    VALUES (?, NOW(), ?, NULL)
                ", [$ip, $reason]);
                
                $success_count++;
                
                if ($index < 10 || $index % 100 == 0) {
                    log_message('success', "封禁IP: $ip" . ($is_cidr ? ' (CIDR)' : '') . " - " . substr($reason, 0, 100) . (strlen($reason) > 100 ? '...' : ''));
                }
            } catch (Exception $e) {
                $error_count++;
                log_message('error', "封禁IP失败: $ip - " . $e->getMessage());
            }
        }
        
        // 提交事务
        $db->commit();
        
        log_message('success', "规则应用完成: 成功 $success_count 条，跳过 $skip_count 条，失败 $error_count 条");
        return true;
    } catch (Exception $e) {
        // 回滚事务
        $db->rollBack();
        log_message('error', '应用规则失败: ' . $e->getMessage());
        return false;
    }
}

// 在下载器中应用封禁
function apply_bans_to_downloaders() {
    global $db;
    
    log_message('info', '正在向下载器应用封禁...');
    
    try {
        // 获取所有启用的下载器
        $downloaders = $db->query("SELECT * FROM downloaders WHERE status = 1")->fetchAll();
        
        if (empty($downloaders)) {
            log_message('warning', '没有找到启用的下载器');
            return true;
        }
        
        // 获取最近添加的封禁IP（最多100个）
        $banned_ips = $db->query("
            SELECT ip FROM peer_bans 
            WHERE (unban_time IS NULL OR unban_time > NOW())
            AND reason LIKE '%自动规则封禁%'
            ORDER BY ban_time DESC
            LIMIT 100
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($banned_ips)) {
            log_message('warning', '没有找到需要封禁的IP');
            return true;
        }
        
        $success_count = 0;
        $error_count = 0;
        
        // 对每个下载器应用封禁
        foreach ($downloaders as $downloader) {
            log_message('info', "正在向下载器 {$downloader['name']} 应用封禁...");
            
            if ($downloader['type'] === 'transmission') {
                log_message('warning', "Transmission 不支持直接封禁IP，跳过");
                continue;
            }
            
            require_once '../includes/qbittorrent.php';
            $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
            
            $downloader_success = 0;
            $downloader_error = 0;
            
            // 对每个IP应用封禁
            foreach ($banned_ips as $ip) {
                try {
                    // 跳过CIDR格式，qBittorrent API不支持直接封禁CIDR
                    if (strpos($ip, '/') !== false) {
                        continue;
                    }
                    
                    $result = $qb->banPeer($ip);
                    if ($result) {
                        $downloader_success++;
                    } else {
                        $downloader_error++;
                    }
                } catch (Exception $e) {
                    $downloader_error++;
                    log_message('error', "在下载器 {$downloader['name']} 中封禁IP $ip 失败: " . $e->getMessage());
                }
            }
            
            log_message('success', "下载器 {$downloader['name']} 封禁完成: 成功 $downloader_success 个，失败 $downloader_error 个");
            $success_count += $downloader_success;
            $error_count += $downloader_error;
        }
        
        log_message('success', "所有下载器封禁完成: 成功 $success_count 个，失败 $error_count 个");
        return true;
    } catch (Exception $e) {
        log_message('error', '向下载器应用封禁失败: ' . $e->getMessage());
        return false;
    }
}

// 主函数
function main() {
    global $db;
    
    // 检查数据库表
    if (!check_database_tables()) {
        return false;
    }
    
    // 获取规则
    $rules_url = 'https://bcr.pbh-btn.ghorg.ghostchu-services.top/combine/all.txt';

    // 尝试从设置中获取规则URL
    try {
        $rules_url_setting = $db->query("SELECT value FROM settings WHERE name = 'vampire_rules_url'")->fetchColumn();
        if ($rules_url_setting) {
            $rules_url = $rules_url_setting;
        }
    } catch (Exception $e) {
        // 如果获取失败，使用默认URL
        log_message('warning', '从设置中获取规则URL失败，使用默认URL: ' . $e->getMessage());
    }
    $content = fetch_rules($rules_url);
    if ($content === false) {
        return false;
    }
    
    // 解析规则
    $rules = parse_rules($content);
    if (empty($rules)) {
        log_message('error', '没有找到有效的规则');
        return false;
    }
    
    // 显示部分规则
    log_message('info', '规则示例:');
    for ($i = 0; $i < min(5, count($rules)); $i++) {
        $rule = $rules[$i];
        log_message('info', "<div class='rule-item'><span class='rule-ip'>{$rule['ip']}</span> - <span class='rule-comment'>" . implode(' | ', $rule['comments']) . "</span></div>");
    }
    
    // 应用规则到数据库
    if (!apply_rules($rules)) {
        return false;
    }
    
    // 在下载器中应用封禁
    apply_bans_to_downloaders();
    
    return true;
}

// 执行主函数
$start_time = microtime(true);
$result = main();
$end_time = microtime(true);
$execution_time = round($end_time - $start_time, 2);

// 输出结果
if ($is_api) {
    // API响应
    echo json_encode([
        'success' => $result,
        'execution_time' => $execution_time,
        'logs' => $logs
    ]);
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