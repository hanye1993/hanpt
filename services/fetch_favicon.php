<?php
require_once '../includes/db.php';

function getFaviconUrl($domain) {
    // 移除协议前缀
    $domain = preg_replace('#^https?://#', '', $domain);
    
    // 尝试不同的favicon位置
    $possible_locations = [
        "https://{$domain}/favicon.ico",
        "https://{$domain}/favicon.png",
        "http://{$domain}/favicon.ico",
        "http://{$domain}/favicon.png"
    ];
    
    // 获取网站HTML内容
    $html = @file_get_contents("https://{$domain}");
    if ($html) {
        // 查找link标签中的favicon
        if (preg_match('/<link[^>]+rel=["\'](icon|shortcut icon)["\'][^>]+href=["\'](.*?)["\']/', $html, $matches)) {
            $favicon_url = $matches[2];
            // 如果是相对路径，转换为绝对路径
            if (strpos($favicon_url, 'http') !== 0) {
                $favicon_url = "https://{$domain}" . ($favicon_url[0] === '/' ? '' : '/') . $favicon_url;
            }
            array_unshift($possible_locations, $favicon_url);
        }
    }
    
    // 尝试获取每个可能的位置
    foreach ($possible_locations as $url) {
        $headers = @get_headers($url);
        if ($headers && strpos($headers[0], '200') !== false) {
            return $url;
        }
    }
    
    return null;
}

function downloadFavicon($url, $filename) {
    $content = @file_get_contents($url);
    if ($content === false) {
        return false;
    }
    
    // 确保目录存在
    $dir = __DIR__ . '/../assets/sites';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    return file_put_contents("{$dir}/{$filename}", $content) !== false;
}

// 主程序
try {
    $db = Database::getInstance();
    $sites = $db->query("SELECT * FROM sites")->fetchAll();
    
    // 确保默认图标存在
    if (!file_exists(__DIR__ . '/../assets/sites/default.png')) {
        // 创建一个简单的默认图标（16x16的灰色方块）
        $img = imagecreatetruecolor(16, 16);
        $gray = imagecolorallocate($img, 200, 200, 200);
        imagefill($img, 0, 0, $gray);
        imagepng($img, __DIR__ . '/../assets/sites/default.png');
        imagedestroy($img);
    }
    
    $response = ['success' => true, 'messages' => []];
    
    foreach ($sites as $site) {
        $domain = $site['domain'];
        $filename = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $domain)) . '.ico';
        
        // 如果文件已存在且不是很旧，跳过
        $filepath = __DIR__ . "/../assets/sites/{$filename}";
        if (file_exists($filepath) && (time() - filemtime($filepath)) < 86400) {
            continue;
        }
        
        // 获取favicon URL
        $favicon_url = getFaviconUrl($domain);
        if ($favicon_url) {
            if (downloadFavicon($favicon_url, $filename)) {
                $response['messages'][] = "成功下载 {$domain} 的图标";
            } else {
                $response['messages'][] = "下载 {$domain} 的图标失败";
            }
        } else {
            $response['messages'][] = "未找到 {$domain} 的图标";
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 