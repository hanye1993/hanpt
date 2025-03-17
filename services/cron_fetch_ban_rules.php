<?php
// 设置为无限执行时间
set_time_limit(0);

// 设置内存限制
ini_set('memory_limit', '256M');

// 禁用输出缓冲
ob_implicit_flush(true);
ob_end_flush();

// 记录开始时间
$start_time = microtime(true);

// 记录日志
function log_message($message) {
    $date = date('Y-m-d H:i:s');
    echo "[$date] $message" . PHP_EOL;
    
    // 同时写入日志文件
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/ban_rules_' . date('Y-m-d') . '.log';
    file_put_contents($log_file, "[$date] $message" . PHP_EOL, FILE_APPEND);
}

// 记录开始日志
log_message("开始执行自动获取和应用封禁规则");

// 构建API URL
$api_url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . 
           $_SERVER['HTTP_HOST'] . 
           dirname($_SERVER['PHP_SELF']) . 
           '/fetch_ban_rules.php?api=1';

// 替换多个斜杠为单个斜杠
$api_url = preg_replace('#([^:])//+#', '$1/', $api_url);

log_message("API URL: $api_url");

// 设置cURL选项
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5分钟超时
curl_setopt($ch, CURLOPT_USERAGENT, 'PHP/BTN-Ban-Rules-Cron');

// 执行cURL请求
log_message("正在调用API...");
$response = curl_exec($ch);

// 检查是否有错误
if (curl_errno($ch)) {
    log_message("cURL错误: " . curl_error($ch));
    exit(1);
}

// 获取HTTP状态码
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
log_message("HTTP状态码: $http_code");

// 关闭cURL会话
curl_close($ch);

// 解析JSON响应
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    log_message("JSON解析错误: " . json_last_error_msg());
    log_message("响应内容: " . substr($response, 0, 1000) . (strlen($response) > 1000 ? '...' : ''));
    exit(1);
}

// 输出日志
if (isset($data['logs']) && is_array($data['logs'])) {
    foreach ($data['logs'] as $log) {
        log_message("[{$log['type']}] {$log['message']}");
    }
}

// 输出结果
if (isset($data['success'])) {
    if ($data['success']) {
        log_message("任务执行成功");
    } else {
        log_message("任务执行失败");
        exit(1);
    }
} else {
    log_message("响应中没有success字段");
    exit(1);
}

// 计算执行时间
$end_time = microtime(true);
$execution_time = round($end_time - $start_time, 2);
log_message("总执行时间: {$execution_time} 秒");

exit(0); 