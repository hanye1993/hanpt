<?php
require_once '../includes/db.php';
require_once '../includes/qbittorrent.php';
require_once '../includes/transmission.php';

// 临时移除会话验证以进行测试
/*
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => '未授权访问']));
}
*/

// 设置响应头
header('Content-Type: application/json');

// 获取请求参数
$action = $_GET['action'] ?? '';

// 检查必要参数
if (empty($action)) {
    die(json_encode([
        'success' => false,
        'error' => '缺少必要参数: action'
    ]));
}

// 获取数据库实例
$db = Database::getInstance();

function qb_login($instance) {
    $cookiePath = sys_get_temp_dir() . '/qbit_cookies/' . md5($instance['id']) . '.txt';
    if (!file_exists(dirname($cookiePath))) {
        mkdir(dirname($cookiePath), 0755, true);
    }

    $ch = curl_init($instance['domain'] . '/api/v2/auth/login');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'username' => $instance['username'],
            'password' => $instance['password']
        ]),
        CURLOPT_COOKIEJAR => $cookiePath,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($result === false || $httpCode !== 200) {
        error_log("qBittorrent login failed for instance {$instance['id']}: HTTP $httpCode, Error: $error");
        return false;
    }
    return true;
}

function qb_request($instance, $endpoint, $params = [], $method = 'GET') {
    $cookiePath = sys_get_temp_dir() . '/qbit_cookies/' . md5($instance['id']) . '.txt';
    
    if (!qb_login($instance)) {
        error_log("Failed to login to qBittorrent instance {$instance['id']}");
        return null;
    }
    
    // 添加API v2前缀
    $url = $instance['domain'] . '/api/v2/' . ltrim($endpoint, '/');
    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_COOKIEFILE => $cookiePath,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CUSTOMREQUEST => $method
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($result === false || $httpCode >= 400) {
        error_log("qBittorrent API request failed: $error, HTTP $httpCode, Response: $result");
        return null;
    }
    
    return $result;
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

function get_state_text($state) {
    $states = [
        'error' => '错误',
        'missingFiles' => '文件丢失',
        'uploading' => '做种中',
        'pausedUP' => '暂停做种',
        'queuedUP' => '等待做种',
        'stalledUP' => '等待连接',
        'checkingUP' => '校验中',
        'forcedUP' => '强制做种',
        'allocating' => '分配空间',
        'downloading' => '下载中',
        'metaDL' => '获取元数据',
        'pausedDL' => '暂停下载',
        'queuedDL' => '等待下载',
        'stalledDL' => '等待连接',
        'checkingDL' => '校验中',
        'forcedDL' => '强制下载',
        'checkingResumeData' => '恢复数据',
        'moving' => '移动中'
    ];
    
    return $states[$state] ?? $state;
}

// 处理不同的操作
switch ($action) {
    case 'add':
        // 添加下载器
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $domain = $_POST['domain'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // 检查必要参数
        if (empty($name) || empty($type) || empty($domain)) {
            die(json_encode([
                'success' => false,
                'message' => '缺少必要参数'
            ]));
        }
        
        // 检查下载器类型
        if (!in_array($type, ['qbittorrent', 'transmission'])) {
            die(json_encode([
                'success' => false,
                'message' => '不支持的下载器类型'
            ]));
        }
        
        // 添加下载器
        try {
            $db->query(
                "INSERT INTO downloaders (name, type, domain, username, password, status) VALUES (?, ?, ?, ?, ?, 1)",
                [$name, $type, $domain, $username, $password]
            );
            
            echo json_encode([
                'success' => true,
                'message' => '添加下载器成功'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '添加下载器失败: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'edit':
        // 编辑下载器
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $domain = $_POST['domain'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // 检查必要参数
        if (empty($id) || empty($name) || empty($type) || empty($domain)) {
            die(json_encode([
                'success' => false,
                'message' => '缺少必要参数'
            ]));
        }
        
        // 检查下载器类型
        if (!in_array($type, ['qbittorrent', 'transmission'])) {
            die(json_encode([
                'success' => false,
                'message' => '不支持的下载器类型'
            ]));
        }
        
        // 检查下载器是否存在
        $downloader = $db->query("SELECT * FROM downloaders WHERE id = ?", [$id])->fetch();
        if (!$downloader) {
            die(json_encode([
                'success' => false,
                'message' => '下载器不存在'
            ]));
        }
        
        // 更新下载器
        try {
            // 如果密码为空，保持原密码不变
            if (empty($password)) {
                $db->query(
                    "UPDATE downloaders SET name = ?, type = ?, domain = ?, username = ? WHERE id = ?",
                    [$name, $type, $domain, $username, $id]
                );
            } else {
                $db->query(
                    "UPDATE downloaders SET name = ?, type = ?, domain = ?, username = ?, password = ? WHERE id = ?",
                    [$name, $type, $domain, $username, $password, $id]
                );
            }
            
            echo json_encode([
                'success' => true,
                'message' => '更新下载器成功'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '更新下载器失败: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'delete':
        // 删除下载器
        $id = $_POST['id'] ?? '';
        
        // 检查必要参数
        if (empty($id)) {
            die(json_encode([
                'success' => false,
                'message' => '缺少必要参数: id'
            ]));
        }
        
        // 检查下载器是否存在
        $downloader = $db->query("SELECT * FROM downloaders WHERE id = ?", [$id])->fetch();
        if (!$downloader) {
            die(json_encode([
                'success' => false,
                'message' => '下载器不存在'
            ]));
        }
        
        // 删除下载器
        try {
            $db->query("DELETE FROM downloaders WHERE id = ?", [$id]);
            
            echo json_encode([
                'success' => true,
                'message' => '删除下载器成功'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '删除下载器失败: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'list':
        // 获取下载器列表
        try {
            $downloaders = $db->query("SELECT id, name, type, domain, username, status FROM downloaders ORDER BY name")->fetchAll();
            
            echo json_encode([
                'success' => true,
                'downloaders' => $downloaders
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '获取下载器列表失败: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'get':
        // 获取单个下载器信息
        $id = $_GET['id'] ?? '';
        
        // 检查必要参数
        if (empty($id)) {
            die(json_encode([
                'success' => false,
                'message' => '缺少必要参数: id'
            ]));
        }
        
        // 获取下载器信息
        try {
            $downloader = $db->query("SELECT id, name, type, domain, username, status FROM downloaders WHERE id = ?", [$id])->fetch();
            
            if (!$downloader) {
                die(json_encode([
                    'success' => false,
                    'message' => '下载器不存在'
                ]));
            }
            
            echo json_encode([
                'success' => true,
                'downloader' => $downloader
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '获取下载器信息失败: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'stats':
        try {
            $downloaders = $db->query("SELECT * FROM downloaders WHERE status = 1")->fetchAll();
            $stats = [];
            
            foreach ($downloaders as $downloader) {
                $transfer = json_decode(qb_request($downloader, '/transfer/info'), true);
                $torrents = json_decode(qb_request($downloader, '/torrents/info'), true);
                
                if ($transfer && is_array($torrents)) {
                    $total_size = array_sum(array_column($torrents, 'size'));
                    $stats[] = [
                        'id' => $downloader['id'],
                        'upload_speed' => $transfer['up_info_speed'] ?? 0,
                        'download_speed' => $transfer['dl_info_speed'] ?? 0,
                        'torrent_count' => count($torrents),
                        'total_size' => $total_size
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'downloaders' => $stats
            ]);
        } catch (Exception $e) {
            error_log("Error in stats action: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case 'pause':
        try {
            if ((!isset($_POST['hash']) && !isset($_POST['hashes'])) || !$downloader_id) {
                throw new Exception('参数错误');
            }
            
            $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
            if (!$downloader) {
                throw new Exception('下载器不存在或已禁用');
            }
            
            // 使用 hashes 参数，如果不存在则使用 hash
            $hash = $_POST['hashes'] ?? $_POST['hash'];
            
            $result = qb_request($downloader, 'torrents/pause', ['hashes' => $hash], 'POST');
            if ($result === null) {
                throw new Exception('暂停种子失败');
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case 'resume':
        try {
            if ((!isset($_POST['hash']) && !isset($_POST['hashes'])) || !$downloader_id) {
                throw new Exception('参数错误');
            }
            
            $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
            if (!$downloader) {
                throw new Exception('下载器不存在或已禁用');
            }
            
            // 使用 hashes 参数，如果不存在则使用 hash
            $hash = $_POST['hashes'] ?? $_POST['hash'];
            
            $result = qb_request($downloader, 'torrents/resume', ['hashes' => $hash], 'POST');
            if ($result === null) {
                throw new Exception('恢复种子失败');
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case 'delete_torrent':
        try {
            if ((!isset($_POST['hash']) && !isset($_POST['hashes'])) || !$downloader_id) {
                throw new Exception('参数错误');
            }
            
            $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
            if (!$downloader) {
                throw new Exception('下载器不存在或已禁用');
            }
            
            // 使用 hashes 参数，如果不存在则使用 hash
            $hash = $_POST['hashes'] ?? $_POST['hash'];
            $deleteFiles = isset($_POST['deleteFiles']) ? (bool)$_POST['deleteFiles'] : false;
            
            $params = [
                'hashes' => $hash,
                'deleteFiles' => $deleteFiles
            ];
            
            $result = qb_request($downloader, 'torrents/delete', $params, 'POST');
            if ($result === null) {
                throw new Exception('删除种子失败');
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case 'recheck':
        try {
            if ((!isset($_POST['hash']) && !isset($_POST['hashes'])) || !$downloader_id) {
                throw new Exception('参数错误');
            }
            
            $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
            if (!$downloader) {
                throw new Exception('下载器不存在或已禁用');
            }
            
            // 使用 hashes 参数，如果不存在则使用 hash
            $hash = $_POST['hashes'] ?? $_POST['hash'];
            
            $result = qb_request($downloader, 'torrents/recheck', [
                'hashes' => $hash
            ], 'POST');
            if ($result === null) {
                throw new Exception('校验种子失败');
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case 'reannounce':
        try {
            if ((!isset($_POST['hash']) && !isset($_POST['hashes'])) || !$downloader_id) {
                throw new Exception('参数错误');
            }
            
            $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
            if (!$downloader) {
                throw new Exception('下载器不存在或已禁用');
            }
            
            // 使用 hashes 参数，如果不存在则使用 hash
            $hash = $_POST['hashes'] ?? $_POST['hash'];
            
            $result = qb_request($downloader, 'torrents/reannounce', [
                'hashes' => $hash
            ], 'POST');
            if ($result === null) {
                throw new Exception('汇报种子失败');
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case 'batch_pause':
    case 'batch_resume':
    case 'batch_recheck':
    case 'batch_reannounce':
    case 'batch_delete':
        try {
            if (!isset($_POST['hashes']) || !is_array($_POST['hashes']) || !$downloader_id) {
                throw new Exception('参数错误');
            }
            
            $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
            if (!$downloader) {
                throw new Exception('下载器不存在或已禁用');
            }
            
            $api_action = str_replace('batch_', '', $action);
            $endpoint = 'torrents/' . ($api_action === 'delete' ? 'delete' : $api_action);
            $params = ['hashes' => implode('|', $_POST['hashes'])];
            
            if ($api_action === 'delete') {
                $params['deleteFiles'] = isset($_POST['deleteFiles']) ? (bool)$_POST['deleteFiles'] : false;
            }
            
            $result = qb_request($downloader, $endpoint, $params, 'POST');
            if ($result === null) {
                throw new Exception('批量操作失败');
            }
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case 'list_torrents':
        $downloader_id = $_GET['downloader_id'] ?? 0;
        
        // 获取下载器信息
        $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
        if (!$downloader) {
            echo json_encode([
                'success' => false,
                'error' => '下载器不存在或已禁用'
            ]);
            break;
        }
        
        try {
            // 获取种子列表
            if ($downloader['type'] === 'transmission') {
                $transmission = new Transmission($downloader['domain'], $downloader['username'], $downloader['password']);
                $result = $transmission->getTorrents();
                if (!$result['success']) {
                    echo json_encode([
                        'success' => false,
                        'error' => '获取种子列表失败: ' . ($result['error'] ?? '未知错误')
                    ]);
                    break;
                }
                $torrents = $result['torrents'];
            } else {
                $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
                $torrents = $qb->getTorrents();
            }
            
            // 格式化种子信息
            $formatted_torrents = [];
            foreach ($torrents as $torrent) {
                $formatted_torrents[] = [
                    'hash' => $torrent['hash'],
                    'name' => $torrent['name'],
                    'size' => format_size($torrent['size']),
                    'progress' => round($torrent['progress'] * 100, 2),
                    'download_speed' => format_speed($torrent['dlspeed']),
                    'upload_speed' => format_speed($torrent['upspeed']),
                    'num_seeds' => $torrent['num_seeds'] ?? 0,
                    'num_leechs' => $torrent['num_leechs'] ?? 0
                ];
            }
            
            echo json_encode([
                'success' => true,
                'torrents' => $formatted_torrents
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => '获取种子列表失败: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'select_torrent':
        // 修改为支持GET请求
        $hash = $_REQUEST['hash'] ?? '';
        $downloader_id = $_REQUEST['downloader_id'] ?? 0;
        
        // 检查参数
        if (empty($hash)) {
            echo json_encode([
                'success' => false,
                'error' => '未选择种子'
            ]);
            break;
        }
        
        if (empty($downloader_id)) {
            echo json_encode([
                'success' => false,
                'error' => '未选择下载器'
            ]);
            break;
        }
        
        // 获取下载器信息
        $downloader = $db->query("SELECT * FROM downloaders WHERE id = ? AND status = 1", [$downloader_id])->fetch();
        if (!$downloader) {
            echo json_encode([
                'success' => false,
                'error' => '下载器不存在或已禁用'
            ]);
            break;
        }
        
        try {
            // 获取种子信息
            if ($downloader['type'] === 'transmission') {
                $transmission = new Transmission($downloader['domain'], $downloader['username'], $downloader['password']);
                $result = $transmission->getTorrent($hash);
                if (!$result['success']) {
                    echo json_encode([
                        'success' => false,
                        'error' => '获取种子信息失败: ' . ($result['error'] ?? '未知错误')
                    ]);
                    break;
                }
                $torrent = $result['torrent'];
            } else {
                $qb = new QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
                $torrent = $qb->getTorrent($hash);
            }
            
            if (!$torrent) {
                echo json_encode([
                    'success' => false,
                    'error' => '种子不存在'
                ]);
                break;
            }
            
            echo json_encode([
                'success' => true,
                'torrent' => [
                    'hash' => $torrent['hash'],
                    'name' => $torrent['name'],
                    'size' => format_size($torrent['size']),
                    'progress' => round($torrent['progress'] * 100, 2),
                    'download_speed' => format_speed($torrent['dlspeed']),
                    'upload_speed' => format_speed($torrent['upspeed']),
                    'num_seeds' => $torrent['num_seeds'] ?? 0,
                    'num_leechs' => $torrent['num_leechs'] ?? 0
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => '获取种子信息失败: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        die(json_encode([
            'success' => false,
            'error' => '未知的操作类型'
        ]));
}
