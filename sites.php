<?php
if (!file_exists(__DIR__ . '/config/installed.php')) {
    header('Location: install.php');
    exit;
}

require_once 'layouts/header.php';
require_once 'includes/db.php';

$db = Database::getInstance();

// 获取所有站点
$sites = $db->query("SELECT * FROM sites ORDER BY name")->fetchAll();

// 获取今日签到状态
$today = date('Y-m-d');
$attendance = [];

// 检查attendance表是否存在
$tables = $db->query("SHOW TABLES LIKE 'attendance'")->fetchAll();
if (!empty($tables)) {
    $attendance = $db->query("
        SELECT site_id, status 
        FROM attendance 
        WHERE DATE(created_at) = ? 
        ORDER BY created_at DESC", 
        [$today]
    )->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>

<div class="dashboard">
    <div class="card">
        <div class="card-header">
            <h2>站点管理</h2>
        </div>
        
        <div class="site-grid">
            <?php foreach($sites as $site): ?>
            <div class="site-card" id="site-<?= $site['id'] ?>">
                <div class="site-header">
                    <img src="assets/sites/<?= strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $site['domain'])) ?>.ico" 
                         onerror="this.src='assets/sites/default.png'" 
                         alt="<?= htmlspecialchars($site['name']) ?>" 
                         class="site-icon">
                    <h3><?= htmlspecialchars($site['name']) ?></h3>
                </div>
                <div class="site-info">
                    <p><strong>域名：</strong><?= htmlspecialchars($site['domain']) ?></p>
                    <div class="site-stats">
                        <span class="stat" title="上传量"><i class="fas fa-upload"></i> <span class="upload">-</span></span>
                        <span class="stat" title="下载量"><i class="fas fa-download"></i> <span class="download">-</span></span>
                        <span class="stat" title="分享率"><i class="fas fa-exchange-alt"></i> <span class="ratio">-</span></span>
                        <span class="stat" title="魔力值"><i class="fas fa-bolt"></i> <span class="bonus">-</span></span>
                    </div>
                    <div class="attendance-status">
                        签到状态：<span class="status <?= isset($attendance[$site['id']]) ? ($attendance[$site['id']] ? 'success' : 'error') : '' ?>">
                            <?= isset($attendance[$site['id']]) ? ($attendance[$site['id']] ? '已签到' : '签到失败') : '未签到' ?>
                        </span>
                    </div>
                </div>
                <div class="card-actions">
                    <button class="btn btn-primary" onclick="visitSite(<?= $site['id'] ?>)">访问</button>
                    <button class="btn btn-success" onclick="checkAttendance(<?= $site['id'] ?>)">签到</button>
                    <button class="btn btn-info" onclick="showRss(<?= $site['id'] ?>)">RSS</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- RSS模态框 -->
<div id="rssModal" class="modal">
    <div class="modal-content">
        <h2>RSS内容</h2>
        <div id="rssContent" class="rss-list"></div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="hideRssModal()">关闭</button>
        </div>
    </div>
</div>

<style>
.site-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.site-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.site-info {
    margin: 15px 0;
}

.site-stats {
    display: flex;
    justify-content: space-between;
    margin: 15px 0;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat {
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat i {
    color: #666;
}

.attendance-status {
    margin: 10px 0;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.status {
    font-weight: 500;
}

.status.success {
    color: #28a745;
}

.status.error {
    color: #dc3545;
}

.card-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    position: relative;
    background: white;
    margin: 50px auto;
    padding: 20px;
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.rss-list {
    margin: 20px 0;
}

.rss-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.rss-item:last-child {
    border-bottom: none;
}

.rss-item h4 {
    margin: 0 0 5px 0;
}

.rss-item p {
    margin: 5px 0;
    color: #666;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}

.site-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.site-icon {
    width: 24px;
    height: 24px;
    object-fit: contain;
}

.site-header h3 {
    margin: 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}
</style>

<script>
async function updateSiteStats(siteId) {
    try {
        const response = await fetch(`/services/site_stats.php?id=${siteId}`);
        const data = await response.json();
        
        if (data.success) {
            const card = document.getElementById(`site-${siteId}`);
            card.querySelector('.upload').textContent = data.stats.upload;
            card.querySelector('.download').textContent = data.stats.download;
            card.querySelector('.ratio').textContent = data.stats.ratio;
            card.querySelector('.bonus').textContent = data.stats.bonus;
        }
    } catch (error) {
        console.error('获取站点统计信息失败:', error);
    }
}

function visitSite(siteId) {
    window.open(`/services/visit_site.php?id=${siteId}`, '_blank');
}

async function checkAttendance(siteId) {
    try {
        const response = await fetch('/services/attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: siteId })
        });
        
        const data = await response.json();
        const statusElement = document.querySelector(`#site-${siteId} .status`);
        
        statusElement.className = `status ${data.success ? 'success' : 'error'}`;
        statusElement.textContent = data.success ? '已签到' : '签到失败';
        
        if (data.message) {
            alert(data.message);
        }
    } catch (error) {
        console.error('签到失败:', error);
        alert('签到请求失败，请稍后重试');
    }
}

async function showRss(siteId) {
    try {
        const response = await fetch(`/services/vampire.php?id=${siteId}`);
        const data = await response.json();
        
        const rssContent = document.getElementById('rssContent');
        rssContent.innerHTML = '';
        
        if (data.success && data.items) {
            data.items.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'rss-item';
                itemElement.innerHTML = `
                    <h4>${item.title}</h4>
                    <p>发布时间：${item.pubDate}</p>
                    <p>大小：${item.size}</p>
                    <p>种子数：${item.seeders} / 下载数：${item.leechers}</p>
                    <a href="${item.link}" target="_blank" class="btn btn-sm btn-primary">下载</a>
                `;
                rssContent.appendChild(itemElement);
            });
        } else {
            rssContent.innerHTML = '<p class="text-center">获取RSS内容失败</p>';
        }
        
        document.getElementById('rssModal').style.display = 'block';
    } catch (error) {
        console.error('获取RSS内容失败:', error);
        alert('获取RSS内容失败，请稍后重试');
    }
}

function hideRssModal() {
    document.getElementById('rssModal').style.display = 'none';
}

// 初始化：获取所有站点的统计信息
document.addEventListener('DOMContentLoaded', () => {
    // 获取站点图标
    fetch('/services/fetch_favicon.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 刷新所有站点图标
                document.querySelectorAll('.site-icon').forEach(img => {
                    const currentSrc = img.src;
                    img.src = currentSrc + '?t=' + new Date().getTime();
                });
            }
        })
        .catch(error => console.error('获取站点图标失败:', error));

    // 获取站点统计信息
    const siteCards = document.querySelectorAll('.site-card');
    siteCards.forEach(card => {
        const siteId = card.id.replace('site-', '');
        updateSiteStats(siteId);
    });
});

// 防止点击模态框外部关闭
document.getElementById('rssModal').addEventListener('click', function(e) {
    if (e.target === this) {
        // 不做任何事情，保持模态框打开
    }
});
</script>

<?php
require_once 'layouts/footer.php';
?>
