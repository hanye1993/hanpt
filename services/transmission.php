<?php
require_once '../includes/db.php';
require_once '../includes/transmission.php';

// 设置响应头
header('Content-Type: application/json');

// 记录请求信息
error_log('Transmission API 请求: ' . json_encode($_REQUEST));

// 获取请求参数
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$id = $_GET['id'] ?? ($_POST['id'] ?? '');

// 检查必要参数
if (empty($action)) {
    error_log('Transmission API 错误: 缺少必要参数 action');
    die(json_encode([
        'success' => false,
        'message' => '缺少必要参数: action'
    ]));
}

if (empty($id)) {
    error_log('Transmission API 错误: 缺少必要参数 id');
    die(json_encode([
        'success' => false,
        'message' => '缺少必要参数: id'
    ]));
}

try {
    // 获取下载器信息
    $db = Database::getInstance();
    $downloader = $db->query("SELECT * FROM downloaders WHERE id = ?", [$id])->fetch();

    if (!$downloader) {
        error_log('Transmission API 错误: 下载器不存在 (ID: ' . $id . ')');
        die(json_encode([
            'success' => false,
            'message' => '下载器不存在'
        ]));
    }

    // 创建Transmission实例
    $transmission = new Transmission($downloader['domain'], $downloader['username'], $downloader['password']);

    // 处理不同的操作
    switch ($action) {
        case 'list':
            // 获取种子列表
            error_log('Transmission API: 获取种子列表');
            $result = $transmission->getTorrents();
            echo json_encode($result);
            break;
        
        case 'stats':
            // 获取下载器统计信息
            error_log('Transmission API: 获取下载器统计信息');
            $result = $transmission->getStats();
            
            // 获取 Transmission 版本信息
            try {
                $sessionInfo = $transmission->sendRequest('session-get');
                error_log('Transmission session-get response: ' . json_encode($sessionInfo));
                
                // 检查不同可能的数据结构
                if ($sessionInfo['success']) {
                    $version = null;
                    
                    // 尝试不同的数据路径
                    if (isset($sessionInfo['data']['arguments']['version'])) {
                        $version = $sessionInfo['data']['arguments']['version'];
                    } else if (isset($sessionInfo['arguments']['version'])) {
                        $version = $sessionInfo['arguments']['version'];
                    } else if (isset($sessionInfo['data']['version'])) {
                        $version = $sessionInfo['data']['version'];
                    } else if (isset($sessionInfo['version'])) {
                        $version = $sessionInfo['version'];
                    }
                    
                    if ($version) {
                        // 移除版本号中的括号部分
                        $version = preg_replace('/\s*\([^)]*\)/', '', $version);
                        // 更新数据库中的版本信息
                        $db->query("UPDATE downloaders SET version = ? WHERE id = ?", [$version, $id]);
                        error_log("Transmission版本已更新: $version");
                    } else {
                        // 如果在响应中找不到版本信息，尝试从数据库获取缓存的版本
                        $cachedVersion = $db->query("SELECT version FROM downloaders WHERE id = ?", [$id])->fetchColumn();
                        $version = $cachedVersion ?: '未知';
                        error_log("无法在响应中找到版本信息，使用缓存的Transmission版本: $version");
                    }
                } else {
                    // 如果API请求失败，尝试从数据库获取缓存的版本
                    $cachedVersion = $db->query("SELECT version FROM downloaders WHERE id = ?", [$id])->fetchColumn();
                    $version = $cachedVersion ?: '未知';
                    error_log("使用缓存的Transmission版本: $version");
                }
            } catch (Exception $e) {
                error_log('获取 Transmission 版本信息失败: ' . $e->getMessage());
                // 出错时尝试使用缓存的版本
                $cachedVersion = $db->query("SELECT version FROM downloaders WHERE id = ?", [$id])->fetchColumn();
                $version = $cachedVersion ?: '未知';
                error_log("使用缓存的Transmission版本: $version");
            }
            
            // 添加版本信息到统计数据
            if (isset($result['stats'])) {
                $result['stats']['version'] = $version;
            } else if (isset($result['data'])) {
                $result['data']['version'] = $version;
            } else {
                // 如果没有找到合适的位置，创建一个
                $result['stats'] = ['version' => $version];
            }
            
            error_log('Transmission API 统计信息结果: ' . json_encode($result));
            echo json_encode($result);
            break;
        
        case 'add':
            // 添加种子
            // 从POST数据或JSON数据中获取参数
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // 获取种子链接和保存路径
            $filename = $data['filename'] ?? ($_POST['torrent'] ?? '');
            $downloadDir = $data['download-dir'] ?? ($_POST['save_path'] ?? '');
            
            if (empty($filename)) {
                error_log('Transmission API 错误: 缺少必要参数 filename');
                die(json_encode([
                    'success' => false,
                    'message' => '缺少必要参数: filename'
                ]));
            }
            
            error_log('Transmission API: 添加种子，URL: ' . $filename . ', 保存路径: ' . ($downloadDir ?: '默认'));
            
            // 构建参数
            $arguments = [
                'filename' => $filename
            ];
            
            if (!empty($downloadDir)) {
                $arguments['download-dir'] = $downloadDir;
            }
            
            // 发送请求
            $response = $transmission->sendRequest('torrent-add', $arguments);
            
            if ($response['success']) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $response['error'] ?? '添加种子失败'
                ]);
            }
            break;
        
        case 'delete':
            // 删除种子
            // 从POST数据或JSON数据中获取参数
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // 获取ids参数（可能是单个id或数组）
            $ids = $data['ids'] ?? null;
            $deleteLocalData = $data['delete-local-data'] ?? false;
            
            if (empty($ids)) {
                error_log('Transmission API 错误: 缺少必要参数 ids');
                die(json_encode([
                    'success' => false,
                    'message' => '缺少必要参数: ids'
                ]));
            }
            
            error_log('Transmission API: 删除种子，IDs: ' . json_encode($ids) . ', 删除文件: ' . ($deleteLocalData ? 'true' : 'false'));
            
            // 构建参数
            $arguments = [
                'ids' => $ids,
                'delete-local-data' => $deleteLocalData
            ];
            
            // 发送请求
            $response = $transmission->sendRequest('torrent-remove', $arguments);
            
            if ($response['success']) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $response['error'] ?? '删除种子失败'
                ]);
            }
            break;
        
        case 'pause_resume':
            // 暂停/恢复种子
            // 从POST数据或JSON数据中获取参数
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // 获取ids参数（可能是单个id或数组）
            $ids = $data['ids'] ?? null;
            
            if (empty($ids)) {
                error_log('Transmission API 错误: 缺少必要参数 ids');
                die(json_encode([
                    'success' => false,
                    'message' => '缺少必要参数: ids'
                ]));
            }
            
            error_log('Transmission API: 暂停/恢复种子，IDs: ' . json_encode($ids));
            
            // 首先获取种子状态
            $arguments = [
                'ids' => $ids,
                'fields' => ['status']
            ];
            
            $torrentInfo = $transmission->sendRequest('torrent-get', $arguments);
            
            if (!$torrentInfo['success'] || empty($torrentInfo['data']['torrents'])) {
                echo json_encode([
                    'success' => false,
                    'error' => $torrentInfo['error'] ?? '获取种子状态失败'
                ]);
                break;
            }
            
            $torrent = $torrentInfo['data']['torrents'][0];
            $isPaused = $torrent['status'] === 0; // 0 = 暂停状态
            
            // 根据当前状态执行相反操作
            if ($isPaused) {
                $response = $transmission->sendRequest('torrent-start', ['ids' => $ids]);
            } else {
                $response = $transmission->sendRequest('torrent-stop', ['ids' => $ids]);
            }
            
            if ($response['success']) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $response['error'] ?? '操作失败'
                ]);
            }
            break;
        
        case 'pause':
            // 暂停种子
            $hash = $_POST['hash'] ?? '';
            
            if (empty($hash)) {
                die(json_encode([
                    'success' => false,
                    'message' => '缺少必要参数: hash'
                ]));
            }
            
            $result = $transmission->pauseTorrent($hash);
            echo json_encode($result);
            break;
        
        case 'resume':
            // 恢复种子
            $hash = $_POST['hash'] ?? '';
            
            if (empty($hash)) {
                die(json_encode([
                    'success' => false,
                    'message' => '缺少必要参数: hash'
                ]));
            }
            
            $result = $transmission->resumeTorrent($hash);
            echo json_encode($result);
            break;
        
        case 'recheck':
            // 校验种子
            // 从POST数据或JSON数据中获取参数
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // 获取ids参数（可能是单个id或数组）
            $ids = $data['ids'] ?? null;
            
            if (empty($ids)) {
                error_log('Transmission API 错误: 缺少必要参数 ids');
                die(json_encode([
                    'success' => false,
                    'message' => '缺少必要参数: ids'
                ]));
            }
            
            error_log('Transmission API: 校验种子，IDs: ' . json_encode($ids));
            
            // 发送请求
            $response = $transmission->sendRequest('torrent-verify', ['ids' => $ids]);
            
            if ($response['success']) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $response['error'] ?? '校验种子失败'
                ]);
            }
            break;
        
        case 'reannounce':
            // 重新汇报种子
            // 从POST数据或JSON数据中获取参数
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // 获取ids参数（可能是单个id或数组）
            $ids = $data['ids'] ?? null;
            
            if (empty($ids)) {
                error_log('Transmission API 错误: 缺少必要参数 ids');
                die(json_encode([
                    'success' => false,
                    'message' => '缺少必要参数: ids'
                ]));
            }
            
            error_log('Transmission API: 重新汇报种子，IDs: ' . json_encode($ids));
            
            // 发送请求
            $response = $transmission->sendRequest('torrent-reannounce', ['ids' => $ids]);
            
            if ($response['success']) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $response['error'] ?? '重新汇报种子失败'
                ]);
            }
            break;
        
        case 'peers':
            // 获取种子的Peers
            $hash = $_GET['hash'] ?? '';
            
            if (empty($hash)) {
                die(json_encode([
                    'success' => false,
                    'message' => '缺少必要参数: hash'
                ]));
            }
            
            $result = $transmission->getTorrentPeers($hash);
            echo json_encode($result);
            break;
        
        case 'free_space':
            // 获取可用空间
            $path = $_GET['path'] ?? '';
            
            if (empty($path)) {
                die(json_encode([
                    'success' => false,
                    'message' => '缺少必要参数: path'
                ]));
            }
            
            $result = $transmission->getFreeSpace($path);
            echo json_encode($result);
            break;
        
        default:
            error_log('Transmission API 错误: 不支持的操作: ' . $action);
            echo json_encode([
                'success' => false,
                'message' => '不支持的操作: ' . $action
            ]);
            break;
    }
} catch (Exception $e) {
    error_log('Transmission API 异常: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '处理请求时发生错误: ' . $e->getMessage()
    ]);
} 