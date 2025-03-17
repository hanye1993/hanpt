<?php
require_once __DIR__ . '/../includes/db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => '未授权访问']));
}

$db = Database::getInstance();
$action = $_POST['action'] ?? '';

// 默认User Agent
$default_user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0';

try {
    switch ($action) {
        case 'save_vampire_settings':
            $enabled = isset($_POST['vampire_enabled']) ? 1 : 0;
            $interval = intval($_POST['vampire_refresh_interval']);
            $ban_duration = intval($_POST['vampire_ban_duration']);
            $min_ratio = floatval($_POST['vampire_min_ratio']);
            $min_upload = intval($_POST['vampire_min_upload']);
            $check_interval = intval($_POST['vampire_check_interval']);
            $ban_threshold = intval($_POST['vampire_ban_threshold']);
            $rules_url = $_POST['vampire_rules_url'];
            $auto_fetch_rules = isset($_POST['vampire_auto_fetch_rules']) ? 1 : 0;
            $rules_fetch_interval = intval($_POST['vampire_rules_fetch_interval']);
            
            // 验证刷新时间范围
            if ($interval < 30 || $interval > 3600) {
                throw new Exception('刷新时间必须在30-3600秒之间');
            }
            
            // 验证封禁时长范围
            if ($ban_duration < 300 || $ban_duration > 86400) {
                throw new Exception('封禁时长必须在300-86400秒之间');
            }
            
            // 验证最小吸血比例范围
            if ($min_ratio < 0.01 || $min_ratio > 1) {
                throw new Exception('最小吸血比例必须在0.01-1之间');
            }
            
            // 验证最小上传量范围
            if ($min_upload < 1048576 || $min_upload > 1073741824) {
                throw new Exception('最小上传量必须在1048576-1073741824字节之间');
            }
            
            // 验证检测间隔范围
            if ($check_interval < 30 || $check_interval > 3600) {
                throw new Exception('检测间隔必须在30-3600秒之间');
            }
            
            // 验证封禁阈值范围
            if ($ban_threshold < 1 || $ban_threshold > 10) {
                throw new Exception('封禁阈值必须在1-10之间');
            }
            
            // 验证封禁规则URL
            if (empty($rules_url) || !filter_var($rules_url, FILTER_VALIDATE_URL)) {
                throw new Exception('封禁规则URL必须是有效的URL');
            }
            
            // 验证封禁规则获取间隔范围
            if ($rules_fetch_interval < 300 || $rules_fetch_interval > 86400) {
                throw new Exception('封禁规则获取间隔必须在300-86400秒之间');
            }
            
            // 更新设置
            $db->query("
                INSERT INTO settings (name, value) VALUES 
                ('vampire_enabled', ?), ('vampire_refresh_interval', ?),
                ('vampire_ban_duration', ?), ('vampire_min_ratio', ?),
                ('vampire_min_upload', ?), ('vampire_check_interval', ?),
                ('vampire_ban_threshold', ?), ('vampire_rules_url', ?),
                ('vampire_auto_fetch_rules', ?), ('vampire_rules_fetch_interval', ?)
                ON DUPLICATE KEY UPDATE value = VALUES(value)
            ", [
                $enabled, $interval, 
                $ban_duration, $min_ratio, 
                $min_upload, $check_interval, 
                $ban_threshold, $rules_url, 
                $auto_fetch_rules, $rules_fetch_interval
            ]);
            
            $db->insert('logs', [
                'type' => 'operation',
                'message' => "更新了吸血检测设置：" . ($enabled ? '启用' : '禁用') . 
                              "，刷新时间：{$interval}秒，封禁时长：{$ban_duration}秒，" .
                              "最小吸血比例：{$min_ratio}，最小上传量：{$min_upload}字节，" .
                              "检测间隔：{$check_interval}秒，封禁阈值：{$ban_threshold}，" .
                              "自动获取封禁规则：" . ($auto_fetch_rules ? '启用' : '禁用') . 
                              "，封禁规则获取间隔：{$rules_fetch_interval}秒"
            ]);
            break;
            
        case 'save_downloader':
            $id = $_POST['id'] ?? '';
            $data = [
                'name' => $_POST['name'],
                'type' => $_POST['type'],
                'domain' => rtrim($_POST['domain'], '/'),
                'username' => $_POST['username']
            ];
            
            // 检查下载器类型
            if (!in_array($data['type'], ['qbittorrent', 'transmission'])) {
                throw new Exception('不支持的下载器类型');
            }
            
            // 只在添加新下载器或明确提供密码时才包含密码字段
            if (!$id || !empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }
            
            if ($id) {
                // 更新现有下载器
                $fields = [];
                foreach ($data as $key => $value) {
                    $fields[] = "`$key` = ?";
                }
                
                $sql = "UPDATE downloaders SET " . implode(', ', $fields) . " WHERE id = ?";
                $params = array_values($data);
                $params[] = $id;
                
                $db->query($sql, $params);
                
                $db->insert('logs', [
                    'type' => 'operation',
                    'message' => "更新了下载器: {$data['name']}"
                ]);
            } else {
                // 添加新下载器
                $db->insert('downloaders', $data);
                
                $db->insert('logs', [
                    'type' => 'operation',
                    'message' => "添加了新下载器: {$data['name']}"
                ]);
            }
            break;
            
        case 'save_site':
            $id = $_POST['id'] ?? '';
            $data = [
                'name' => $_POST['name'],
                'domain' => $_POST['domain'],
                'protocol' => $_POST['protocol'] ?: $default_user_agent,
                'rss_url' => $_POST['rss_url'],
                'cookie' => $_POST['cookie'] ?: null // 如果为空字符串则存储为null
            ];
            
            if ($id) {
                // 更新现有站点
                $fields = [];
                foreach ($data as $key => $value) {
                    $fields[] = "`$key` = ?";
                }
                
                $sql = "UPDATE sites SET " . implode(', ', $fields) . " WHERE id = ?";
                $params = array_values($data);
                $params[] = $id;
                
                $db->query($sql, $params);
                
                $db->insert('logs', [
                    'type' => 'operation',
                    'message' => "更新了站点: {$data['name']}"
                ]);
            } else {
                // 添加新站点
                $db->insert('sites', $data);
                
                $db->insert('logs', [
                    'type' => 'operation',
                    'message' => "添加了新站点: {$data['name']}"
                ]);
            }
            break;
            
        case 'delete_downloader':
            $id = $_POST['id'] ?? '';
            if ($id) {
                // 获取下载器名称用于日志
                $downloader = $db->query("SELECT name FROM downloaders WHERE id = ?", [$id])->fetch();
                
                $db->query("DELETE FROM downloaders WHERE id = ?", [$id]);
                
                if ($downloader) {
                    $db->insert('logs', [
                        'type' => 'operation',
                        'message' => "删除了下载器: {$downloader['name']}"
                    ]);
                }
            }
            break;
            
        case 'delete_site':
            $id = $_POST['id'] ?? '';
            if ($id) {
                // 获取站点名称用于日志
                $site = $db->query("SELECT name FROM sites WHERE id = ?", [$id])->fetch();
                
                $db->query("DELETE FROM sites WHERE id = ?", [$id]);
                
                if ($site) {
                    $db->insert('logs', [
                        'type' => 'operation',
                        'message' => "删除了站点: {$site['name']}"
                    ]);
                }
            }
            break;
            
        default:
            throw new Exception('未知操作');
    }
    
    // 重定向回设置页面，带上成功消息
    header('Location: /settings.php?success=操作成功');
    exit;
    
} catch (Exception $e) {
    // 记录错误日志
    $db->insert('logs', [
        'type' => 'error',
        'message' => $e->getMessage()
    ]);
    
    // 重定向回设置页面，带上错误消息
    header('Location: /settings.php?error=' . urlencode($e->getMessage()));
    exit;
}
