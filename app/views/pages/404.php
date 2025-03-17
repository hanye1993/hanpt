<?php
// 404页面

// 设置HTTP状态码
http_response_code(404);

// 设置页面标题
$page_title = '页面未找到';
$current_page = '';

// 开始输出缓冲
ob_start();
?>

<div class="error-container">
    <div class="error-code">404</div>
    <div class="error-title">页面未找到</div>
    <div class="error-message">您请求的页面不存在或已被移除。</div>
    <div class="error-actions">
        <a href="/" class="btn btn-primary">返回首页</a>
        <a href="javascript:history.back()" class="btn btn-secondary">返回上一页</a>
    </div>
</div>

<style>
.error-container {
    text-align: center;
    padding: 100px 20px;
    max-width: 600px;
    margin: 0 auto;
}

.error-code {
    font-size: 120px;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 20px;
    line-height: 1;
}

.error-title {
    font-size: 32px;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 20px;
}

.error-message {
    font-size: 18px;
    color: var(--text-muted);
    margin-bottom: 40px;
}

.error-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.error-actions .btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.error-actions .btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: none;
}

.error-actions .btn-primary:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
}

.error-actions .btn-secondary {
    background-color: transparent;
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.error-actions .btn-secondary:hover {
    background-color: var(--border-color);
    transform: translateY(-2px);
}
</style>

<?php
// 获取输出缓冲内容
$content = ob_get_clean();

// 包含主布局文件
include_once __DIR__ . '/../layouts/main.php';
?> 