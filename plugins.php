<?php
require_once 'layouts/header.php';
require_once 'includes/db.php';

$db = Database::getInstance();
?>

<div class="dashboard">
    <div class="card">
        <div class="card-header">
            <h2>插件管理</h2>
        </div>
        
        <div class="development-notice">
            <div class="notice-icon">
                <i class="fas fa-code"></i>
            </div>
            <div class="notice-content">
                <h3>功能开发中</h3>
                <p>插件管理功能正在开发中，敬请期待！</p>
                <p class="notice-date">预计上线时间：待定</p>
            </div>
        </div>
    </div>
</div>

<style>
.development-notice {
    display: flex;
    align-items: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.notice-icon {
    font-size: 60px;
    color: #007bff;
    margin-right: 30px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.notice-content {
    flex: 1;
}

.notice-content h3 {
    color: #333;
    margin-bottom: 15px;
    font-size: 24px;
}

.notice-content p {
    color: #666;
    font-size: 16px;
    margin-bottom: 10px;
}

.notice-date {
    color: #999;
    font-style: italic;
    margin-top: 20px;
}
</style>

<?php
require_once 'layouts/footer.php';
?> 