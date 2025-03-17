<?php
require_once '../includes/db.php';

// 设置响应头
header('Content-Type: application/json');

// 获取请求参数
$action = $_GET['action'] ?? '';

// 检查必要参数
if (empty($action)) {
    die(json_encode([
        'success' => false,
        'message' => '缺少必要参数: action'
    ]));
}

// 获取数据库实例
$db = Database::getInstance();

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
        
    default:
        echo json_encode([
            'success' => false,
            'message' => '不支持的操作: ' . $action
        ]);
        break;
} 