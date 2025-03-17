<?php
// 检查是否已安装
if (!file_exists(__DIR__ . '/config/installed.php')) {
    header('Location: install.php');
    exit;
}

require_once 'layouts/header.php';
require_once 'includes/db.php';

$db = Database::getInstance();

// 获取所有下载器和站点
$downloaders = $db->query("SELECT * FROM downloaders ORDER BY name")->fetchAll();
$sites = $db->query("SELECT * FROM sites ORDER BY name")->fetchAll();

// 获取吸血检测设置
$vampire_settings = $db->query("
    SELECT name, value FROM settings 
    WHERE name IN ('vampire_enabled', 'vampire_refresh_interval', 'vampire_ban_duration', 'vampire_min_ratio', 
                  'vampire_min_upload', 'vampire_check_interval', 'vampire_ban_threshold', 'vampire_rules_url', 
                  'vampire_auto_fetch_rules', 'vampire_rules_fetch_interval')
")->fetchAll(PDO::FETCH_KEY_PAIR);

$vampire_enabled = isset($vampire_settings['vampire_enabled']) ? (bool)$vampire_settings['vampire_enabled'] : false;
$vampire_refresh_interval = $vampire_settings['vampire_refresh_interval'] ?? 300; // 默认5分钟
$vampire_ban_duration = $vampire_settings['vampire_ban_duration'] ?? 86400; // 默认1天
$vampire_min_ratio = $vampire_settings['vampire_min_ratio'] ?? 0.01; // 默认1%
$vampire_min_upload = $vampire_settings['vampire_min_upload'] ?? 104857600; // 默认100MB
$vampire_check_interval = $vampire_settings['vampire_check_interval'] ?? 60; // 默认60秒
$vampire_ban_threshold = $vampire_settings['vampire_ban_threshold'] ?? 3; // 默认3次
$vampire_rules_url = $vampire_settings['vampire_rules_url'] ?? 'https://bcr.pbh-btn.ghorg.ghostchu-services.top/combine/all.txt';
$vampire_auto_fetch_rules = isset($vampire_settings['vampire_auto_fetch_rules']) ? (bool)$vampire_settings['vampire_auto_fetch_rules'] : false;
$vampire_rules_fetch_interval = $vampire_settings['vampire_rules_fetch_interval'] ?? 86400; // 默认1天

$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

// 默认User Agent
$default_user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0';
?>

<div class="dashboard">
    <?php if ($success_message): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success_message) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error_message) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="settings-tabs">
            <button class="tab-button active" data-tab="downloaders">下载器管理</button>
            <button class="tab-button" data-tab="sites">站点管理</button>
            <button class="tab-button" data-tab="vampire">吸血检测设置</button>
            <button class="tab-button" data-tab="plugins">插件管理</button>
        </div>

        <!-- 下载器管理标签页 -->
        <div class="tab-content active" id="downloaders-tab">
            <div class="tab-header">
                <h2>下载器管理</h2>
                <button class="btn btn-primary" onclick="showDownloaderModal()">
                    <i class="fas fa-plus"></i> 添加下载器
                </button>
            </div>
            
            <div class="instance-grid">
                <?php foreach($downloaders as $downloader): ?>
                <div class="instance-card">
                    <div class="instance-header">
                        <img src="assets/<?= strtolower($downloader['type']) ?>.png" alt="<?= htmlspecialchars($downloader['name']) ?>" class="instance-icon">
                        <h3><?= htmlspecialchars($downloader['name']) ?></h3>
                        <span class="instance-type"><?= strtolower($downloader['type']) === 'qbittorrent' ? 'QB' : 'TR' ?></span>
                    </div>
                    <div class="instance-info">
                        <p><strong>域名：</strong><span><?= htmlspecialchars($downloader['domain']) ?></span></p>
                        <p><strong>用户名：</strong><span><?= htmlspecialchars($downloader['username']) ?></span></p>
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-secondary" onclick="editDownloader(<?= htmlspecialchars(json_encode($downloader)) ?>)">
                            <i class="fas fa-edit"></i> 编辑
                        </button>
                        <button class="btn btn-danger" onclick="deleteDownloader(<?= $downloader['id'] ?>, '<?= htmlspecialchars($downloader['name']) ?>')">
                            <i class="fas fa-trash"></i> 删除
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 站点管理标签页 -->
        <div class="tab-content" id="sites-tab">
            <div class="tab-header">
                <h2>站点管理</h2>
                <button class="btn btn-primary" onclick="showSiteModal()">
                    <i class="fas fa-plus"></i> 添加站点
                </button>
            </div>
            
            <div class="instance-grid">
                <?php foreach($sites as $site): ?>
                <div class="instance-card">
                    <div class="instance-header">
                        <img src="assets/sites/<?= strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $site['domain'])) ?>.ico" 
                             onerror="this.src='assets/sites/default.png'" 
                             alt="<?= htmlspecialchars($site['name']) ?>" 
                             class="instance-icon">
                        <h3><?= htmlspecialchars($site['name']) ?></h3>
                    </div>
                    <div class="instance-info">
                        <p><strong>域名：</strong><span><?= htmlspecialchars($site['domain']) ?></span></p>
                        <p><strong>RSS：</strong><span class="truncate-text"><?= htmlspecialchars($site['rss_url']) ?></span></p>
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-secondary" onclick="editSite(<?= htmlspecialchars(json_encode($site)) ?>)">
                            <i class="fas fa-edit"></i> 编辑
                        </button>
                        <button class="btn btn-danger" onclick="deleteSite(<?= $site['id'] ?>, '<?= htmlspecialchars($site['name']) ?>')">
                            <i class="fas fa-trash"></i> 删除
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 吸血检测设置标签页 -->
        <div class="tab-content" id="vampire-tab">
            <div class="tab-header">
                <h2>吸血检测设置</h2>
            </div>
            <div class="tab-body">
                <form id="settingsForm">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="vampire_enabled" name="vampire_enabled" <?= $vampire_enabled ? 'checked' : '' ?>>
                            启用吸血检测
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="vampire_refresh_interval">刷新间隔（秒）</label>
                        <input type="number" id="vampire_refresh_interval" name="vampire_refresh_interval" 
                               value="<?= htmlspecialchars($vampire_refresh_interval) ?>" min="60">
                    </div>
                    
                    <div class="form-group">
                        <label for="vampire_min_ratio">最小吸血比例</label>
                        <input type="number" id="vampire_min_ratio" name="vampire_min_ratio" 
                               value="<?= htmlspecialchars($vampire_min_ratio) ?>" step="0.1" min="1">
                        <span class="help-text">下载速度/上传速度的比值，超过此值视为吸血</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="vampire_min_upload">最小上传速度（字节/秒）</label>
                        <input type="number" id="vampire_min_upload" name="vampire_min_upload" 
                               value="<?= htmlspecialchars($vampire_min_upload) ?>" min="0">
                        <span class="help-text">只在上传速度超过此值时才进行吸血检测</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="vampire_ban_duration">封禁时长（秒）</label>
                        <input type="number" id="vampire_ban_duration" name="vampire_ban_duration" 
                               value="<?= htmlspecialchars($vampire_ban_duration) ?>" min="300">
                    </div>
                    
                    <div class="form-group">
                        <label for="vampire_ratio_threshold">吸血比例阈值</label>
                        <input type="number" id="vampire_ratio_threshold" name="vampire_ratio_threshold" 
                               value="<?= htmlspecialchars($vampire_min_ratio) ?>" step="0.1" min="1">
                        <span class="help-text">超过此比例时记录到peer_checks</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="vampire_check_interval">检查间隔（秒）</label>
                        <input type="number" id="vampire_check_interval" name="vampire_check_interval" 
                               value="<?= htmlspecialchars($vampire_check_interval) ?>" min="60">
                    </div>
                    
                    <div class="form-group">
                        <label for="vampire_ban_threshold">封禁阈值</label>
                        <input type="number" id="vampire_ban_threshold" name="vampire_ban_threshold" 
                               value="<?= htmlspecialchars($vampire_ban_threshold) ?>" min="1">
                        <span class="help-text">在检查间隔内超过吸血比例的次数达到此值时自动封禁</span>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">保存设置</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 插件管理标签页 -->
        <div class="tab-content" id="plugins-tab">
            <div class="tab-header">
                <h2>插件管理</h2>
                <button class="btn btn-primary" onclick="showPluginModal()">添加插件</button>
            </div>
            <div class="tab-body">
                <div class="empty-state">
                    <img src="assets/images/plugin.svg" alt="No plugins" class="empty-icon">
                    <h3>暂无插件</h3>
                    <p>点击"添加插件"按钮开始添加新的插件</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 下载器模态框 -->
<div id="downloaderModal" class="modal">
    <div class="modal-content">
        <h2 id="downloaderModalTitle">添加下载器</h2>
        <form id="downloaderForm" action="/services/settings.php" method="POST">
            <input type="hidden" name="action" value="save_downloader">
            <input type="hidden" name="id" id="downloaderId">
            
            <div class="form-group">
                <label class="required">名称</label>
                <input type="text" name="name" id="downloaderName" required placeholder="输入下载器名称">
            </div>
            
            <div class="form-group">
                <label class="required">类型</label>
                <select name="type" id="downloaderType" required>
                    <option value="qbittorrent">qBittorrent</option>
                    <option value="transmission">Transmission</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="required">域名</label>
                <input type="text" name="domain" id="downloaderDomain" required placeholder="例如: http://localhost:8080">
            </div>
            
            <div class="form-group">
                <label class="required">用户名</label>
                <input type="text" name="username" id="downloaderUsername" required placeholder="输入用户名">
            </div>
            
            <div class="form-group">
                <label class="required">密码</label>
                <input type="password" name="password" id="downloaderPassword" placeholder="输入密码">
                <small>编辑时留空表示不修改密码</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
                <button type="button" class="btn btn-secondary" onclick="hideDownloaderModal()"><i class="fas fa-times"></i> 取消</button>
            </div>
        </form>
    </div>
</div>

<!-- 站点模态框 -->
<div id="siteModal" class="modal">
    <div class="modal-content">
        <h2 id="siteModalTitle">添加站点</h2>
        <form id="siteForm" action="/services/settings.php" method="POST">
            <input type="hidden" name="action" value="save_site">
            <input type="hidden" name="id" id="siteId">
            <input type="hidden" name="protocol" id="siteProtocol">
            
            <div class="form-group">
                <label class="required">名称</label>
                <input type="text" name="name" id="siteName" required placeholder="输入站点名称">
            </div>
            
            <div class="form-group">
                <label class="required">域名</label>
                <input type="text" name="domain" id="siteDomain" required placeholder="例如: example.com">
            </div>
            
            <div class="form-group">
                <label class="required">RSS地址</label>
                <input type="text" name="rss_url" id="siteRssUrl" required placeholder="输入RSS订阅地址">
            </div>
            
            <div class="form-group">
                <label>Cookie</label>
                <textarea name="cookie" id="siteCookie" placeholder="输入站点Cookie，可选"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
                <button type="button" class="btn btn-secondary" onclick="hideSiteModal()"><i class="fas fa-times"></i> 取消</button>
            </div>
        </form>
    </div>
</div>

<style>
.dashboard {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 20px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #eee;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.card-header h2 {
    margin: 0;
    color: #333;
    font-size: 1.5rem;
    font-weight: 600;
}

.instance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.instance-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #eee;
    position: relative;
    overflow: hidden;
}

.instance-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.instance-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    position: relative;
}

.instance-icon {
    width: 32px;
    height: 32px;
    object-fit: contain;
    border-radius: 6px;
}

.instance-header h3 {
    margin: 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
    flex: 1;
}

.instance-type {
    background: #f0f2f5;
    color: #666;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.instance-info {
    margin: 15px 0;
}

.instance-info p {
    margin: 8px 0;
    color: #666;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.instance-info p strong {
    color: #333;
    min-width: 70px;
    font-weight: 500;
}

.instance-info p span {
    word-break: break-all;
    flex: 1;
}

.truncate-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

.card-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    justify-content: flex-end;
}

.card-actions .btn {
    display: flex;
    align-items: center;
    gap: 5px;
}

.card-actions .btn i {
    font-size: 14px;
}

/* 模态框样式修复 */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.show {
    opacity: 1;
}

.modal-content {
    position: relative;
    background: white;
    margin: 50px auto;
    width: 90%;
    max-width: 600px; /* 增加模态框宽度 */
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    transform: translateY(-20px);
    opacity: 0;
    transition: all 0.3s ease;
}

.modal.show .modal-content {
    transform: translateY(0);
    opacity: 1;
}

.modal-content h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    font-size: 20px;
    color: #333;
    font-weight: 600;
}

.modal-content h2::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 20px;
    background: #007bff;
    border-radius: 2px;
}

.modal-content form {
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.form-group label.required::after {
    content: '*';
    display: inline-block;
    color: #dc3545;
    margin-left: 4px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    color: #333;
    transition: all 0.3s ease;
    background: #f8f9fa;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
    background: white;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
}

.btn i {
    font-size: 14px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-1px);
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-size: 14px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* 添加动画效果 */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

.instance-card {
    animation: fadeIn 0.3s ease-out, slideUp 0.3s ease-out;
}

/* 选项卡样式 */
.settings-tabs {
    display: flex;
    gap: 2px;
    background: #f0f2f5;
    padding: 2px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.tab-button {
    flex: 1;
    padding: 12px 20px;
    border: none;
    background: none;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 6px;
}

.tab-button:hover {
    color: #333;
    background: rgba(255,255,255,0.5);
}

.tab-button.active {
    background: white;
    color: #007bff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.tab-content {
    display: none;
    animation: fadeIn 0.3s ease-out;
}

.tab-content.active {
    display: block;
}

.tab-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.tab-header h2 {
    margin: 0;
    color: #333;
    font-size: 1.5rem;
    font-weight: 600;
}

.tab-body {
    padding: 20px 0;
}

/* 空状态样式 */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state .empty-icon {
    width: 120px;
    height: 120px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 0 0 10px;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}

.empty-state p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

/* 修改现有样式以适应新布局 */
.dashboard {
    padding: 20px;
}

.card {
    padding: 20px;
    margin-bottom: 0;
}

.instance-grid {
    margin-top: 20px;
}

.settings-form {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #333;
    font-weight: 500;
}

.form-group input[type="number"],
.form-group input[type="text"] {
    width: 200px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.help-text {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.form-actions {
    margin-top: 30px;
}

.btn-primary {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-primary:hover {
    background: #0056b3;
}
</style>

<script>
// 默认User Agent
const defaultUserAgent = '<?= $default_user_agent ?>';

function showDownloaderModal() {
    // 切换到下载器标签页
    document.querySelector('[data-tab="downloaders"]').click();
    
    const modal = document.getElementById('downloaderModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('show'), 10);
    
    document.getElementById('downloaderModalTitle').textContent = '添加下载器';
    document.getElementById('downloaderForm').reset();
    document.getElementById('downloaderId').value = '';
}

function hideDownloaderModal() {
    const modal = document.getElementById('downloaderModal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
}

function editDownloader(downloader) {
    // 切换到下载器标签页
    document.querySelector('[data-tab="downloaders"]').click();
    
    const modal = document.getElementById('downloaderModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('show'), 10);
    
    document.getElementById('downloaderModalTitle').textContent = '编辑下载器';
    document.getElementById('downloaderId').value = downloader.id;
    document.getElementById('downloaderName').value = downloader.name;
    document.getElementById('downloaderType').value = downloader.type;
    document.getElementById('downloaderDomain').value = downloader.domain;
    document.getElementById('downloaderUsername').value = downloader.username;
    document.getElementById('downloaderPassword').value = '';
}

function deleteDownloader(id, name) {
    if (confirm(`确定要删除下载器"${name}"吗？`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/services/settings.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_downloader';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function showSiteModal() {
    // 切换到站点标签页
    document.querySelector('[data-tab="sites"]').click();
    
    const modal = document.getElementById('siteModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('show'), 10);
    
    document.getElementById('siteModalTitle').textContent = '添加站点';
    document.getElementById('siteForm').reset();
    document.getElementById('siteId').value = '';
    document.getElementById('siteProtocol').value = defaultUserAgent;
}

function hideSiteModal() {
    const modal = document.getElementById('siteModal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
}

function editSite(site) {
    // 切换到站点标签页
    document.querySelector('[data-tab="sites"]').click();
    
    const modal = document.getElementById('siteModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('show'), 10);
    
    document.getElementById('siteModalTitle').textContent = '编辑站点';
    document.getElementById('siteId').value = site.id;
    document.getElementById('siteName').value = site.name;
    document.getElementById('siteDomain').value = site.domain;
    document.getElementById('siteProtocol').value = site.protocol || defaultUserAgent;
    document.getElementById('siteRssUrl').value = site.rss_url;
    document.getElementById('siteCookie').value = site.cookie || '';
}

function deleteSite(id, name) {
    if (confirm(`确定要删除站点"${name}"吗？`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/services/settings.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_site';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// 防止点击模态框外部关闭
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            if (modal.id === 'downloaderModal') {
                hideDownloaderModal();
            } else if (modal.id === 'siteModal') {
                hideSiteModal();
            }
        }
    });
});

// 表单验证
document.getElementById('downloaderForm').addEventListener('submit', function(e) {
    const password = document.getElementById('downloaderPassword');
    const id = document.getElementById('downloaderId').value;
    
    if (!id && !password.value) {
        e.preventDefault();
        alert('新增下载器时密码不能为空');
        password.focus();
    }
});

document.getElementById('siteForm').addEventListener('submit', function(e) {
    if (!document.getElementById('siteProtocol').value) {
        document.getElementById('siteProtocol').value = defaultUserAgent;
    }
});

// 初始化：获取站点图标
document.addEventListener('DOMContentLoaded', () => {
    // 获取站点图标
    fetch('/services/fetch_favicon.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 刷新所有站点图标
                document.querySelectorAll('.instance-icon').forEach(img => {
                    if (img.src.includes('sites/')) {
                        const currentSrc = img.src;
                        img.src = currentSrc + '?t=' + new Date().getTime();
                    }
                });
            }
        })
        .catch(error => console.error('获取站点图标失败:', error));
});

// 选项卡切换功能
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // 移除所有活动状态
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // 添加新的活动状态
            button.classList.add('active');
            const tabId = button.dataset.tab + '-tab';
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // 修复模态框显示问题
    fixModalDisplay();
});

// 修复模态框显示问题
function fixModalDisplay() {
    // 确保编辑按钮正确显示模态框
    const editButtons = document.querySelectorAll('.btn-secondary[onclick^="edit"]');
    editButtons.forEach(button => {
        const originalOnclick = button.getAttribute('onclick');
        button.setAttribute('onclick', '');
        button.addEventListener('click', function() {
            // 执行原始的onclick函数
            eval(originalOnclick);
            
            // 确保模态框显示
            setTimeout(() => {
                const modalId = originalOnclick.includes('editDownloader') ? 'downloaderModal' : 'siteModal';
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'block';
                    modal.classList.add('show');
                }
            }, 50);
        });
    });
}

function showPluginModal() {
    alert('插件管理功能即将推出，敬请期待！');
}

document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const settings = {};
    
    formData.forEach((value, key) => {
        if (key === 'vampire_enabled') {
            settings[key] = value === 'on' ? '1' : '0';
        } else {
            settings[key] = value;
        }
    });
    
    try {
        for (const [name, value] of Object.entries(settings)) {
            const response = await fetch(`/services/vampire.php?action=update_setting&name=${encodeURIComponent(name)}&value=${encodeURIComponent(value)}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || '保存设置失败');
            }
        }
        
        alert('设置已保存');
    } catch (error) {
        console.error('保存设置失败:', error);
        alert('保存设置失败: ' + error.message);
    }
});
</script>

<?php
require_once 'layouts/footer.php';
?>
