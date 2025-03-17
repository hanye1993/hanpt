<?php
if (!file_exists(__DIR__ . '/config/installed.php')) {
    header('Location: install.php');
    exit;
}

require_once 'layouts/header.php';
require_once 'includes/db.php';

$db = Database::getInstance();
$downloaders = $db->query("SELECT * FROM downloaders ORDER BY name")->fetchAll();
?>

<div class="dashboard">
    <div class="card">
        <div class="downloader-tabs">
            <button class="tab-button active" data-tab="all">全部下载器</button>
            <button class="tab-button" data-tab="qb">QB下载器</button>
            <button class="tab-button" data-tab="tr">TR下载器</button>
        </div>
        
        <!-- 全部下载器标签页 -->
        <div class="tab-content active" id="all-tab">
            <div class="downloader-grid">
                <?php foreach($downloaders as $downloader): ?>
                <div class="downloader-card" id="downloader-<?= $downloader['id'] ?>" data-id="<?= $downloader['id'] ?>" data-type="<?= htmlspecialchars($downloader['type']) ?>">
                    <div class="downloader-header">
                        <img src="assets/<?= strtolower($downloader['type']) ?>.png" alt="<?= htmlspecialchars($downloader['name']) ?>" class="downloader-icon">
                        <h3><?= htmlspecialchars($downloader['name']) ?></h3>
                    </div>
                    <div class="downloader-info">
                        <div class="stats-grid">
                            <div class="stat" title="上传速度">
                                <i class="fas fa-upload">上传</i>
                                <span class="up-speed">-</span>
                            </div>
                            <div class="stat" title="下载速度">
                                <i class="fas fa-download">下载</i>
                                <span class="dl-speed">-</span>
                            </div>
                            <div class="stat" title="剩余空间">
                                <i class="fas fa-hdd">空间</i>
                                <span class="free-space">-</span>
                            </div>
                            <div class="stat" title="总体积">
                                <i class="fas fa-database">体积</i>
                                <span class="total-size">-</span>
                            </div>
                            <div class="stat" title="种子数量">
                                <i class="fas fa-magnet">数量</i>
                                <span class="torrent-count">-</span>
                            </div>
                            <div class="stat" title="版本">
                                <i class="fas fa-code-branch">版本</i>
                                <span class="version-info">-</span>
                            </div>
                            <div class="stat" title="连接状态" style="display: none;">
                                <i class="fas fa-plug">连接</i>
                                <span class="connection-status">-</span>
                                <span class="connection-indicator"></span>
                            </div>
                        </div>
                        <div class="downloader-actions">
                            <button class="btn btn-primary btn-sm show-torrents" onclick="showTorrents(<?= $downloader['id'] ?>, '<?= $downloader['type'] ?>')">
                                查看种子列表
                            </button>
                            <button class="btn btn-success btn-sm add-torrents" onclick="showAddTorrentsModal(<?= $downloader['id'] ?>, '<?= $downloader['type'] ?>')">
                                增加下载
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- QB下载器标签页 -->
        <div class="tab-content" id="qb-tab">
            <div class="downloader-grid">
                <?php foreach($downloaders as $downloader): ?>
                <?php if(strtolower($downloader['type']) === 'qbittorrent'): ?>
                <div class="downloader-card" id="downloader-qb-<?= $downloader['id'] ?>" data-id="<?= $downloader['id'] ?>" data-type="<?= htmlspecialchars($downloader['type']) ?>">
                    <div class="downloader-header">
                        <img src="assets/<?= strtolower($downloader['type']) ?>.png" alt="<?= htmlspecialchars($downloader['name']) ?>" class="downloader-icon">
                        <h3><?= htmlspecialchars($downloader['name']) ?></h3>
                    </div>
                    <div class="downloader-info">
                        <div class="stats-grid">
                            <div class="stat" title="上传速度">
                                <i class="fas fa-upload">上传</i>
                                <span class="up-speed">-</span>
                            </div>
                            <div class="stat" title="下载速度">
                                <i class="fas fa-download">下载</i>
                                <span class="dl-speed">-</span>
                            </div>
                            <div class="stat" title="剩余空间">
                                <i class="fas fa-hdd">空间</i>
                                <span class="free-space">-</span>
                            </div>
                            <div class="stat" title="总体积">
                                <i class="fas fa-database">体积</i>
                                <span class="total-size">-</span>
                            </div>
                            <div class="stat" title="种子数量">
                                <i class="fas fa-magnet">数量</i>
                                <span class="torrent-count">-</span>
                            </div>
                            <div class="stat" title="版本">
                                <i class="fas fa-code-branch">版本</i>
                                <span class="version-info">-</span>
                            </div>
                            <div class="stat" title="连接状态" style="display: none;">
                                <i class="fas fa-plug">连接</i>
                                <span class="connection-status">-</span>
                                <span class="connection-indicator"></span>
                            </div>
                        </div>
                        <div class="downloader-actions">
                            <button class="btn btn-primary btn-sm show-torrents" onclick="showTorrents(<?= $downloader['id'] ?>, '<?= $downloader['type'] ?>')">
                                查看种子列表
                            </button>
                            <button class="btn btn-success btn-sm add-torrents" onclick="showAddTorrentsModal(<?= $downloader['id'] ?>, '<?= $downloader['type'] ?>')">
                                增加下载
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- TR下载器标签页 -->
        <div class="tab-content" id="tr-tab">
            <div class="downloader-grid">
                <?php foreach($downloaders as $downloader): ?>
                <?php if(strtolower($downloader['type']) === 'transmission'): ?>
                <div class="downloader-card" id="downloader-tr-<?= $downloader['id'] ?>" data-id="<?= $downloader['id'] ?>" data-type="<?= htmlspecialchars($downloader['type']) ?>">
                    <div class="downloader-header">
                        <img src="assets/<?= strtolower($downloader['type']) ?>.png" alt="<?= htmlspecialchars($downloader['name']) ?>" class="downloader-icon">
                        <h3><?= htmlspecialchars($downloader['name']) ?></h3>
                    </div>
                    <div class="downloader-info">
                        <div class="stats-grid">
                            <div class="stat" title="上传速度">
                                <i class="fas fa-upload">上传</i>
                                <span class="up-speed">-</span>
                            </div>
                            <div class="stat" title="下载速度">
                                <i class="fas fa-download">下载</i>
                                <span class="dl-speed">-</span>
                            </div>
                            <div class="stat" title="剩余空间">
                                <i class="fas fa-hdd">空间</i>
                                <span class="free-space">-</span>
                            </div>
                            <div class="stat" title="总体积">
                                <i class="fas fa-database">体积</i>
                                <span class="total-size">-</span>
                            </div>
                            <div class="stat" title="种子数量">
                                <i class="fas fa-magnet">数量</i>
                                <span class="torrent-count">-</span>
                            </div>
                            <div class="stat" title="版本">
                                <i class="fas fa-code-branch">版本</i>
                                <span class="version-info">-</span>
                            </div>
                            <div class="stat" title="连接状态" style="display: none;">
                                <i class="fas fa-plug">连接</i>
                                <span class="connection-status">-</span>
                                <span class="connection-indicator"></span>
                            </div>
                        </div>
                        <div class="downloader-actions">
                            <button class="btn btn-primary btn-sm show-torrents" onclick="showTorrents(<?= $downloader['id'] ?>, '<?= $downloader['type'] ?>')">
                                查看种子列表
                            </button>
                            <button class="btn btn-success btn-sm add-torrents" onclick="showAddTorrentsModal(<?= $downloader['id'] ?>, '<?= $downloader['type'] ?>')">
                                增加下载
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- 种子列表模态框 -->
<div id="torrentsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="torrents-title">种子列表</h2>
            <button class="btn-close" onclick="hideTorrentsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- 加载中提示 -->
            <div id="torrents-loading" style="display: none; justify-content: center; align-items: center; padding: 20px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">加载中...</span>
                </div>
                <span style="margin-left: 10px;">加载中...</span>
            </div>
            
            <!-- 种子列表容器 -->
            <div id="torrents-container">
                <div class="torrents-toolbar">
                    <button class="btn btn-danger btn-sm" onclick="deleteTorrents()">删除选中</button>
                    <button class="btn btn-warning btn-sm" onclick="recheckTorrents()">校验选中</button>
                    <button class="btn btn-info btn-sm" onclick="reAnnounceTorrents()">重新汇报</button>
                    <div class="form-check">
                        <input type="checkbox" id="deleteFiles" class="form-check-input">
                        <label for="deleteFiles" class="form-check-label">同时删除文件</label>
                    </div>
                </div>
                <div class="pagination-controls">
                    <div class="items-per-page">
                        <label for="itemsPerPage">每页显示：</label>
                        <select id="itemsPerPage" onchange="changeItemsPerPage()">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="0">全部</option>
                        </select>
                    </div>
                    <div class="pagination-buttons">
                        <button class="btn btn-sm btn-secondary" onclick="goToPage(currentPage - 1)" id="prevPageBtn" disabled>上一页</button>
                        <span id="pageInfo">第 <span id="currentPageNum">1</span> 页，共 <span id="totalPages">1</span> 页</span>
                        <button class="btn btn-sm btn-secondary" onclick="goToPage(currentPage + 1)" id="nextPageBtn" disabled>下一页</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th>种子名称</th>
                                <th width="150">进度</th>
                                <th width="100">大小</th>
                                <th width="100">已上传</th>
                                <th width="80">分享率</th>
                                <th width="80">状态</th>
                                <th width="100">操作</th>
                            </tr>
                        </thead>
                        <tbody id="torrentsList">
                            <!-- 种子列表将通过JavaScript动态填充 -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 增加下载模态框 -->
<div id="addTorrentsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>增加下载</h2>
            <button class="btn-close" onclick="hideAddTorrentsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="torrentUrls">种子链接（每行一个）：</label>
                <textarea id="torrentUrls" class="form-control" rows="10" placeholder="输入磁力链接或种子URL，每行一个"></textarea>
            </div>
            <div class="form-group">
                <label for="savePath">保存路径（可选）：</label>
                <input type="text" id="savePath" class="form-control" placeholder="留空使用默认路径">
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" onclick="addTorrents()">添加</button>
                <button class="btn btn-secondary" onclick="hideAddTorrentsModal()">取消</button>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard {
    padding: 20px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #eee;
}

/* 选项卡样式 */
.downloader-tabs {
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

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.downloader-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.downloader-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #eee;
}

.downloader-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.downloader-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.downloader-icon {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.downloader-card h3 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}

.downloader-info {
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.stat {
    display: flex;
    flex-direction: column;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: background-color 0.2s ease;
}

.stat:hover {
    background: #e9ecef;
}

.stat i {
    margin-bottom: 5px;
    color: #6c757d;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat span {
    font-weight: 500;
    color: #333;
}

/* 连接状态样式 */
.stat[title="连接状态"] {
    position: relative;
}

.connection-status {
    display: inline-block;
    margin-right: 5px;
}

.connection-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #6c757d; /* 默认灰色 */
    position: absolute;
    right: 12px;
    top: 12px;
}

.connection-success .connection-indicator {
    background-color: #28a745; /* 绿色 */
}

.connection-error .connection-indicator {
    background-color: #dc3545; /* 红色 */
}

.downloader-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.show-torrents {
    background: #007bff;
    color: white;
    border: none;
}

.show-torrents:hover {
    background: #0056b3;
}

.show-torrents:active {
    transform: translateY(0);
}

/* 添加一些动画效果 */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.downloader-card {
    animation: fadeIn 0.3s ease-out;
}

/* 为不同状态的数值添加颜色 */
.up-speed, .dl-speed {
    color: #2196f3;
}

.free-space {
    color: #4caf50;
}

.total-size {
    color: #ff9800;
}

.torrent-count {
    color: #9c27b0;
}

/* 连接状态样式 */
.connection-status {
    display: flex;
    align-items: center;
}

.connection-status::before {
    content: "";
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
}

.connection-status.normal::before {
    background-color: #4caf50; /* 绿色 */
}

.connection-status.error::before {
    background-color: #f44336; /* 红色 */
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
    animation: fadeIn 0.2s ease-out;
}

.modal-content {
    position: relative;
    background: white;
    margin: 30px auto;
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    animation: slideDown 0.3s ease-out;
    overflow: hidden;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #333;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.modal-header h2::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 20px;
    background: #007bff;
    border-radius: 2px;
    margin-right: 10px;
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    color: #666;
    transition: color 0.2s ease;
}

.btn-close:hover {
    color: #333;
}

.modal-body {
    padding: 25px;
    overflow-y: auto;
}

.torrents-toolbar {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.table th {
    background: #f8f9fa;
    font-weight: 500;
    white-space: nowrap;
}

.torrent-title {
    max-width: 500px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.progress {
    height: 6px;
    background: #eee;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 5px;
}

.progress-bar {
    height: 100%;
    background: var(--primary);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 12px;
    color: #666;
}

.form-check {
    margin-left: auto;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
}

.status-downloading {
    background: #e3f2fd;
    color: #1976d2;
}

.status-seeding {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-paused {
    background: #f5f5f5;
    color: #616161;
}

.status-error {
    background: #fbe9e7;
    color: #d84315;
}

.ratio-value {
    font-weight: 500;
}

.ratio-good {
    color: #2e7d32;
}

.ratio-warning {
    color: #f57c00;
}

.ratio-danger {
    color: #d32f2f;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.action-buttons .btn {
    padding: 4px 8px;
    font-size: 12px;
}

.pagination-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.items-per-page {
    display: flex;
    align-items: center;
    gap: 8px;
}

.items-per-page select {
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: white;
}

.pagination-buttons {
    display: flex;
    align-items: center;
    gap: 10px;
}

#pageInfo {
    font-size: 14px;
    color: #666;
}

.downloader-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.downloader-icon {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.downloader-header h3 {
    margin: 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}

.downloader-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.downloader-actions button {
    flex: 1;
    padding: 8px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.downloader-actions button:hover {
    transform: translateY(-1px);
}

.downloader-actions button:active {
    transform: translateY(0);
}

/* 增加下载按钮样式 */
.add-torrents {
    margin-left: 10px;
    background: #28a745;
    color: white;
    border: none;
    transition: all 0.2s ease;
}

.add-torrents:hover {
    background: #218838;
    transform: translateY(-1px);
}

.add-torrents:active {
    transform: translateY(0);
}

/* 表单样式 */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-sizing: border-box;
    font-size: 14px;
    transition: border-color 0.2s ease;
    background: #f8f9fa;
}

.form-control:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    background: white;
}

textarea.form-control {
    resize: vertical;
    min-height: 150px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.btn-primary {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-left: 0;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.btn-secondary:active {
    transform: translateY(0);
}

.refresh-stats {
    background-color: #17a2b8;
    color: white;
    border: none;
    margin-left: 5px;
}

.refresh-stats:hover {
    background-color: #138496;
}
</style>

<script>
let currentDownloaderId = null;
let currentDownloaderType = null;
let selectedTorrents = new Set();
let allTorrents = []; // 存储所有种子数据
let currentPage = 1;
let itemsPerPage = 10;
let totalItems = 0;

// 格式化大小
function formatSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// 格式化速度
function formatSpeed(bytesPerSecond) {
    return formatSize(bytesPerSecond) + '/s';
}

// 统一处理种子数据格式
function normalizeTorrentData(torrent, downloaderType) {
    if (!torrent) {
        console.error('无效的种子数据:', torrent);
        return {
            name: '未知',
            hash: '',
            size: 0,
            progress: 0,
            uploaded: 0,
            ratio: 0,
            state: 'unknown',
            dlspeed: 0,
            upspeed: 0
        };
    }
    
    const downloaderTypeLower = downloaderType ? downloaderType.toLowerCase() : '';
    
    if (downloaderTypeLower === 'transmission') {
        console.log('处理 Transmission 种子数据');
        // 适配不同版本可能的字段名
        return {
            name: torrent.name || '',
            hash: torrent.hashString || torrent.hash || torrent.id || '',
            size: torrent.totalSize || torrent.size || torrent.sizeWhenDone || 0,
            progress: torrent.percentDone || torrent.percent_done || torrent.progress || 0,
            uploaded: torrent.uploadedEver || torrent.uploaded || torrent.upload_ever || 0,
            ratio: torrent.uploadRatio || torrent.ratio || torrent.upload_ratio || 0,
            state: getTransmissionState(torrent.status || torrent.state),
            dlspeed: torrent.rateDownload || torrent.rate_download || torrent.downloadSpeed || 0,
            upspeed: torrent.rateUpload || torrent.rate_upload || torrent.uploadSpeed || 0
        };
    }
    
    // 对于其他下载器（如 qBittorrent）或未知类型，返回原始数据
    return torrent;
}

// 转换Transmission状态为通用状态
function getTransmissionState(status) {
    console.log('Transmission status:', status); // 添加调试日志
    
    // 如果状态为空或未定义，返回未知
    if (status === undefined || status === null) {
        return 'unknown';
    }
    
    // 支持数字状态码和字符串状态
    if (typeof status === 'number') {
        const statusMap = {
            0: 'paused',        // TR_STATUS_STOPPED
            1: 'checkingFiles', // TR_STATUS_CHECK_WAIT
            2: 'checking',      // TR_STATUS_CHECK
            3: 'downloading',   // TR_STATUS_DOWNLOAD_WAIT
            4: 'downloading',   // TR_STATUS_DOWNLOAD
            5: 'seeding',       // TR_STATUS_SEED_WAIT
            6: 'seeding'        // TR_STATUS_SEED
        };
        return statusMap[status] || 'unknown';
    } else if (typeof status === 'string') {
        // 处理字符串状态
        const lowerStatus = status.toLowerCase();
        const stringStatusMap = {
            'stopped': 'paused',
            'check pending': 'checkingFiles',
            'checking': 'checking',
            'download pending': 'downloading',
            'downloading': 'downloading',
            'seed pending': 'seeding',
            'seeding': 'seeding',
            // 添加更多可能的状态映射
            'idle': 'paused',
            'finished': 'seeding',
            'complete': 'seeding',
            'active': 'downloading',
            'queued': 'downloading',
            'verifying': 'checking',
            'waiting': 'downloading'
        };
        return stringStatusMap[lowerStatus] || lowerStatus;
    }
    
    // 如果是其他类型，转换为字符串并返回
    return String(status);
}

// 获取状态文本
function getStatusText(state) {
    const statusMap = {
        // 下载相关状态
        'downloading': '下载中',
        'stalledDL': '等待下载',
        'stalleddl': '等待下载',
        'stalled_dl': '等待下载',
        'stalleddownload': '等待下载',
        'stalled_download': '等待下载',
        'pausedDL': '已暂停下载',
        'pauseddl': '已暂停下载',
        'paused_dl': '已暂停下载',
        'queuedDL': '排队下载',
        'queueddl': '排队下载',
        'queued_dl': '排队下载',
        'checkingDL': '校验中',
        'checkingdl': '校验中',
        'checking_dl': '校验中',
        'forcedDL': '强制下载',
        'forceddl': '强制下载',
        'forced_dl': '强制下载',
        'downloadingMetadata': '获取元数据',
        
        // 上传相关状态
        'uploading': '做种中',
        'stalledUP': '等待做种',
        'stalledup': '等待做种',
        'stalled_up': '等待做种',
        'stalledupload': '等待做种',
        'stalled_upload': '等待做种',
        'pausedUP': '已暂停',
        'pausedup': '已暂停',
        'paused_up': '已暂停',
        'pausedupload': '已暂停',
        'paused_upload': '已暂停',
        'queuedUP': '排队做种',
        'queuedup': '排队做种',
        'queued_up': '排队做种',
        'checkingUP': '校验中',
        'checkingup': '校验中',
        'checking_up': '校验中',
        'forcedUP': '强制做种',
        'forcedup': '强制做种',
        'forced_up': '强制做种',
        'seeding': '做种中',
        
        // 校验相关状态
        'checking': '校验中',
        'checkingResumeData': '校验数据',
        'checkingresumedata': '校验数据',
        'checking_resume_data': '校验数据',
        'checkingFiles': '校验文件',
        'checkingfiles': '校验文件',
        'checking_files': '校验文件',
        
        // 元数据相关状态
        'metaDL': '获取元数据',
        'metadl': '获取元数据',
        'meta_dl': '获取元数据',
        'stalledMetadata': '等待元数据',
        'stalledmetadata': '等待元数据',
        'stalled_metadata': '等待元数据',
        'forcedMetadata': '强制获取元数据',
        'forcedmetadata': '强制获取元数据',
        'forced_metadata': '强制获取元数据',
        
        // 其他状态
        'moving': '移动中',
        'unknown': '未知',
        'missingFiles': '文件丢失',
        'missingfiles': '文件丢失',
        'missing_files': '文件丢失',
        'error': '错误',
        'paused': '已暂停',
        'queued': '排队中',
        'stalled': '等待中',
        'forced': '强制进行',
        'allocating': '分配空间',
        'allocatingDisk': '分配磁盘空间',
        'allocatingdisk': '分配磁盘空间',
        'allocating_disk': '分配磁盘空间',
        'resuming': '恢复中'
    };
    
    const lowerState = String(state).toLowerCase();
    return statusMap[lowerState] || state;
}

// 获取API端点
function getApiEndpoint(downloaderType, action, id = null) {
    let endpoint = '';
    
    // 确定基础URL
    if (downloaderType && downloaderType.toLowerCase() === 'transmission') {
        endpoint = '/services/transmission.php';
    } else if (downloaderType && downloaderType.toLowerCase() === 'qbittorrent') {
        endpoint = '/services/qbittorrent.php';
    } else {
        console.error("未知的下载器类型: " + downloaderType);
        return '';
    }
    
    // 添加操作参数
    endpoint += `?action=${action}`;
    
    // 如果提供了ID，添加ID参数
    if (id !== null) {
        endpoint += `&id=${id}`;
    } else if (currentDownloaderId) {
        endpoint += `&id=${currentDownloaderId}`;
    }
    
    console.log(`API端点: ${endpoint} (${downloaderType})`);
    return endpoint;
}

// 更新下载器统计信息
function updateDownloaderStats(downloaderId, downloaderType, forceConnectionCheck = true) {
    if (!downloaderId || !downloaderType) {
        console.error('更新下载器统计信息失败: 缺少下载器ID或类型');
        return;
    }
    
    const apiEndpoint = getApiEndpoint(downloaderType, 'stats', downloaderId);
    if (!apiEndpoint) {
        console.error('获取API端点失败');
        return;
    }
    
    console.log(`正在获取下载器统计信息: ID=${downloaderId}, 类型=${downloaderType}, 端点=${apiEndpoint}`);
    
    return fetch(apiEndpoint)
        .then(response => {
            console.log(`API响应状态码: ${response.status}`);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('获取到下载器统计信息:', data);
            
            if (data && data.success && data.stats) {
                // 更新所有相关的下载器卡片（主标签页和类型标签页）
                const selectors = [
                    `#downloader-${downloaderId}`,
                    `#downloader-${downloaderType.toLowerCase().substring(0, 2)}-${downloaderId}`
                ];
                
                console.log(`将更新以下选择器的卡片:`, selectors);
                
                selectors.forEach(selector => {
                    const card = document.querySelector(selector);
                    if (card) {
                        try {
                            // 获取统计数据，确保有默认值
                            const stats = data.stats;
                            const upSpeed = stats.up_speed || stats.up_info_speed || 0;
                            const dlSpeed = stats.dl_speed || stats.dl_info_speed || 0;
                            const freeSpace = stats.free_space || 0;
                            const totalSize = stats.total_size || 0;
                            const torrentCount = stats.torrent_count || 0;
                            const version = stats.version || '未知';
                            
                            console.log(`更新卡片 ${selector} 的统计信息:`, {
                                upSpeed, dlSpeed, freeSpace, totalSize, torrentCount, version
                            });
                            
                            // 更新统计信息
                            const upSpeedEl = card.querySelector('.up-speed');
                            if (upSpeedEl) upSpeedEl.textContent = formatSpeed(upSpeed);
                            
                            const dlSpeedEl = card.querySelector('.dl-speed');
                            if (dlSpeedEl) dlSpeedEl.textContent = formatSpeed(dlSpeed);
                            
                            const freeSpaceEl = card.querySelector('.free-space');
                            if (freeSpaceEl) freeSpaceEl.textContent = formatSize(freeSpace);

                            const totalSizeEl = card.querySelector('.total-size');
                            if (totalSizeEl) totalSizeEl.textContent = formatSize(totalSize);

                            const torrentCountEl = card.querySelector('.torrent-count');
                            if (torrentCountEl) torrentCountEl.textContent = torrentCount;
                            
                            const versionInfoEl = card.querySelector('.version-info');
                            if (versionInfoEl) versionInfoEl.textContent = version;
                            
                            // 更新连接状态
                            if (forceConnectionCheck) {
                                card.classList.remove('connection-error');
                                card.classList.add('connection-success');
                            }
                            
                            console.log(`卡片 ${selector} 更新完成`);
                        } catch (error) {
                            console.error(`更新卡片 ${selector} 时出错:`, error);
                        }
                    }
                });
                
                return true; // 表示更新成功
            } else {
                throw new Error('无效的响应数据');
            }
        })
        .catch(error => {
            console.error('获取下载器统计信息失败:', error);
            
            if (forceConnectionCheck) {
                // 更新所有相关的下载器卡片的连接状态
                const selectors = [
                    `#downloader-${downloaderId}`,
                    `#downloader-${downloaderType.toLowerCase().substring(0, 2)}-${downloaderId}`
                ];
                
                selectors.forEach(selector => {
                    const card = document.querySelector(selector);
                    if (card) {
                        card.classList.remove('connection-success');
                        card.classList.add('connection-error');
                    }
                });
            }
            
            return false; // 表示更新失败
        });
}

// 自动刷新下载器统计信息
let statsRefreshIntervals = {};

function startAutoRefresh(downloaderId, downloaderType) {
    // 如果已经存在刷新间隔，先清除它
    if (statsRefreshIntervals[downloaderId]) {
        clearInterval(statsRefreshIntervals[downloaderId]);
    }
    
    // 立即更新一次
    updateDownloaderStats(downloaderId, downloaderType, true);
    
    // 设置定时刷新（每3秒更新一次）
    statsRefreshIntervals[downloaderId] = setInterval(() => {
        updateDownloaderStats(downloaderId, downloaderType, false);
    }, 3000);
    
    console.log(`已启动下载器 ${downloaderId} 的自动刷新`);
}

function stopAutoRefresh(downloaderId) {
    if (statsRefreshIntervals[downloaderId]) {
        clearInterval(statsRefreshIntervals[downloaderId]);
        delete statsRefreshIntervals[downloaderId];
        console.log(`已停止下载器 ${downloaderId} 的自动刷新`);
    }
}

// 在页面加载完成后启动所有下载器的自动刷新
document.addEventListener('DOMContentLoaded', function() {
    // 获取所有下载器卡片
    const downloaderCards = document.querySelectorAll('[id^="downloader-"]');
    downloaderCards.forEach(card => {
        const id = card.getAttribute('data-id');
        const type = card.getAttribute('data-type');
        if (id && type) {
            startAutoRefresh(id, type);
        }
    });
});

// 在页面关闭或切换时停止所有自动刷新
window.addEventListener('beforeunload', function() {
    Object.keys(statsRefreshIntervals).forEach(downloaderId => {
        stopAutoRefresh(downloaderId);
    });
});

// 选项卡切换功能
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('初始化选项卡功能...');
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        if (tabButtons.length === 0) {
            console.warn('未找到选项卡按钮');
        }
        
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                try {
                    // 移除所有活动状态
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // 添加新的活动状态
                    button.classList.add('active');
                    const tabId = button.dataset.tab + '-tab';
                    const tabContent = document.getElementById(tabId);
                    if (tabContent) {
                        tabContent.classList.add('active');
                    } else {
                        console.error(`未找到标签页内容: ${tabId}`);
                    }
                } catch (error) {
                    console.error('切换选项卡时出错:', error);
                }
            });
        });
        
        // 初始化下载器状态
        initializeDownloaders();
    } catch (error) {
        console.error('初始化选项卡功能时出错:', error);
    }
});

// 检查会话状态
function checkSessionStatus() {
    try {
        console.log('检查会话状态...');
        
        fetch('/services/check_session.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.logged_in) {
                    console.log('会话有效，用户已登录');
                } else {
                    console.warn('会话无效或用户未登录');
                    // 可以在这里添加重定向到登录页面的逻辑
                }
            })
            .catch(error => {
                console.error('检查会话状态时出错:', error);
            });
    } catch (error) {
        console.error('检查会话状态函数执行出错:', error);
    }
}

// 初始化所有下载器
function initializeDownloaders() {
    try {
        console.log('正在初始化下载器...');
        
        // 尝试检查会话状态
        try {
            checkSessionStatus();
        } catch (error) {
            console.error('检查会话状态时出错:', error);
            // 继续初始化，不要因为会话检查失败而中断
        }
        
        // 获取所有下载器卡片
        const downloaderCards = document.querySelectorAll('.downloader-card');
        console.log(`找到 ${downloaderCards.length} 个下载器卡片`);
        
        if (downloaderCards.length === 0) {
            console.warn('未找到下载器卡片');
            return;
        }
        
        // 清除所有可能存在的旧定时器
        if (window.statsTimers) {
            window.statsTimers.forEach(timer => clearInterval(timer));
        }
        window.statsTimers = [];
        
        // 初始化每个下载器 - 只进行一次性更新，不设置定时刷新
        downloaderCards.forEach(card => {
            try {
                const downloaderId = card.id.split('-').pop();
                const downloaderType = card.getAttribute('data-type');
                
                if (!downloaderId || !downloaderType) {
                    console.error('下载器卡片缺少ID或类型:', card);
                    return;
                }
                
                console.log(`初始化下载器: ID=${downloaderId}, 类型=${downloaderType}`);
                
                // 初始化时检查连接状态（只进行一次性更新）
                updateDownloaderStats(downloaderId, downloaderType, true);
            } catch (error) {
                console.error('初始化下载器时出错:', error);
            }
        });
        
        console.log('下载器初始化完成');
    } catch (error) {
        console.error('初始化下载器过程中出错:', error);
    }
}

// 显示种子列表
function showTorrents(downloaderId, downloaderType) {
    try {
        console.log(`显示种子列表: ID=${downloaderId}, 类型=${downloaderType}`);
        
        if (!downloaderId || !downloaderType) {
            console.error('显示种子列表失败: 缺少下载器ID或类型');
            alert('无法显示种子列表: 缺少下载器信息');
            return;
        }
        
        // 在查看种子列表时更新一次连接状态（强制检查）
        updateDownloaderStats(downloaderId, downloaderType, true);
        
        // 设置当前下载器
        currentDownloaderId = downloaderId;
        currentDownloaderType = downloaderType;
        
        // 重置选中的种子
        selectedTorrents.clear();
        
        // 获取API端点
        const apiEndpoint = getApiEndpoint(downloaderType, 'list', downloaderId);
        if (!apiEndpoint) {
            console.error('获取API端点失败');
            alert('无法获取API端点');
            return;
        }
        
        console.log(`获取种子列表: 端点=${apiEndpoint}`);
        
        // 显示模态框
        const torrentsModal = document.getElementById('torrentsModal');
        if (!torrentsModal) {
            console.error('未找到种子列表模态框元素');
            alert('无法显示种子列表: 界面元素缺失');
            return;
        }
        torrentsModal.style.display = 'block';
        
        // 检查并显示加载中
        const loadingElement = document.getElementById('torrents-loading');
        const containerElement = document.getElementById('torrents-container');
        
        if (loadingElement) {
            loadingElement.style.display = 'flex';
        } else {
            console.warn('未找到加载中元素');
        }
        
        if (containerElement) {
            containerElement.style.display = 'none';
        } else {
            console.warn('未找到容器元素');
        }
        
        // 获取种子列表
        fetch(apiEndpoint)
            .then(response => {
                console.log(`API响应状态码: ${response.status}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.text().then(text => {
                    console.log(`API响应文本: ${text.substring(0, 200)}...`);
                    try {
                        return text ? JSON.parse(text) : {};
                    } catch (e) {
                        console.error('解析JSON响应失败:', e);
                        throw new Error(`解析响应失败: ${e.message}`);
                    }
                });
            })
            .then(data => {
                console.log('获取到种子列表数据类型:', typeof data);
                console.log('获取到种子列表数据结构:', Object.keys(data));
                
                if (data && (data.success || data.arguments)) {
                    // 规范化种子数据
                    let rawTorrents = [];
                    
                    if (Array.isArray(data.torrents)) {
                        console.log('使用 data.torrents 数组');
                        rawTorrents = data.torrents;
                    } else if (data.arguments && Array.isArray(data.arguments.torrents)) {
                        console.log('使用 data.arguments.torrents 数组');
                        // 处理 Transmission 的响应格式
                        rawTorrents = data.arguments.torrents;
                    } else if (data.data && Array.isArray(data.data)) {
                        console.log('使用 data.data 数组');
                        rawTorrents = data.data;
                    } else {
                        console.warn('未找到有效的种子数组，使用空数组');
                        rawTorrents = [];
                    }
                    
                    console.log(`原始种子数量: ${rawTorrents.length}`);
                    if (rawTorrents.length > 0) {
                        console.log('第一个种子示例:', rawTorrents[0]);
                    }
                    
                    // 统一处理种子数据格式
                    allTorrents = rawTorrents.map(torrent => normalizeTorrentData(torrent, downloaderType));
                    console.log(`规范化后的种子数量: ${allTorrents.length}`);
                    if (allTorrents.length > 0) {
                        console.log('规范化后的第一个种子示例:', allTorrents[0]);
                    }
                    
                    // 渲染种子列表
                    currentPage = 1;
                    renderTorrents();
                    
                    // 显示种子列表
                    if (loadingElement) {
                        loadingElement.style.display = 'none';
                    }
                    if (containerElement) {
                        containerElement.style.display = 'block';
                    }
                    
                    // 更新下载器名称
                    const titleElement = document.getElementById('torrents-title');
                    if (titleElement) {
                        const downloaderElement = document.querySelector(`#downloader-${downloaderId} h3`) || 
                                                document.querySelector(`#downloader-${downloaderType.toLowerCase().substring(0, 2)}-${downloaderId} h3`);
                        const downloaderName = downloaderElement ? downloaderElement.textContent : '下载器';
                        titleElement.textContent = `${downloaderName} - 种子列表`;
                    }
                } else {
                    console.error('获取种子列表失败:', data ? data.error || data.message : '未知错误');
                    alert(`获取种子列表失败: ${data ? data.error || data.message : '未知错误'}`);
                    if (loadingElement) {
                        loadingElement.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('获取种子列表出错:', error);
                alert(`获取种子列表出错: ${error.message}`);
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
            });
    } catch (error) {
        console.error('显示种子列表过程中出错:', error);
        alert(`显示种子列表出错: ${error.message}`);
    }
}

// 渲染种子列表（带分页）
function renderTorrents() {
    try {
        const tbody = document.getElementById('torrentsList');
        if (!tbody) {
            console.error('未找到种子列表表格元素');
            return;
        }
        
        tbody.innerHTML = '';
        
        if (!allTorrents || allTorrents.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="8" style="text-align: center; padding: 20px;">没有种子</td>';
            tbody.appendChild(tr);
            
            // 更新页码信息
            updatePaginationInfo(0, 0);
            return;
        }
        
        // 计算总页数
        const totalItems = allTorrents.length;
        const totalPages = itemsPerPage === 0 ? 1 : Math.ceil(totalItems / itemsPerPage);
        
        // 更新页码信息
        updatePaginationInfo(totalItems, totalPages);
        
        // 确定要显示的种子范围
        let torrentsToShow;
        if (itemsPerPage === 0) {
            // 显示全部
            torrentsToShow = allTorrents;
        } else {
            // 显示当前页的种子
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
            torrentsToShow = allTorrents.slice(startIndex, endIndex);
        }
        
        // 渲染种子列表
        torrentsToShow.forEach(torrent => {
            try {
                if (!torrent) return;
                
                const tr = document.createElement('tr');
                const ratio = parseFloat(torrent.ratio) || 0;
                const ratioClass = ratio >= 1 ? 'ratio-good' : (ratio >= 0.5 ? 'ratio-warning' : 'ratio-danger');
                
                // 检查是否为暂停状态 - 包括所有可能的暂停状态变体
                const state = String(torrent.state || '').toLowerCase();
                const isPaused = state.includes('paused');
                
                // 获取种子的唯一标识符
                const torrentId = torrent.hashString || torrent.hash || torrent.id || '';
                
                // 创建复选框
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'torrent-check';
                checkbox.value = torrentId;
                checkbox.checked = selectedTorrents.has(torrentId);
                
                // 创建第一个单元格并添加复选框
                const checkboxCell = document.createElement('td');
                checkboxCell.appendChild(checkbox);
                
                // 为复选框添加事件监听器
                checkbox.addEventListener('change', function() {
                    const id = this.value;
                    console.log('Checkbox changed:', id, this.checked);
                    if (this.checked) {
                        selectedTorrents.add(id);
                    } else {
                        selectedTorrents.delete(id);
                    }
                    console.log('Selected torrents:', Array.from(selectedTorrents));
                    updateSelectAllCheckbox();
                });
                
                tr.appendChild(checkboxCell);
                
                // 添加其他单元格
                tr.innerHTML += `
                    <td>
                        <div class="torrent-title" title="${torrent.name || ''}">
                            ${torrent.name || '未知'}
                        </div>
                    </td>
                    <td>
                        <div class="progress-text">${((torrent.progress || 0) * 100).toFixed(1)}%</div>
                        <div class="progress">
                            <div class="progress-bar" style="width: ${(torrent.progress || 0) * 100}%"></div>
                        </div>
                    </td>
                    <td>${formatSize(torrent.size || 0)}</td>
                    <td>${formatSize(torrent.uploaded || 0)}</td>
                    <td>
                        <span class="ratio-value ${ratioClass}">
                            ${ratio.toFixed(2)}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-${state}">
                            ${getStatusText(torrent.state || 'unknown')}
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn ${isPaused ? 'btn-success' : 'btn-warning'} btn-sm" onclick="pauseResumeTorrent('${torrentId}')">
                                ${isPaused ? '继续' : '暂停'}
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteSingleTorrent('${torrentId}')">
                                删除
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(tr);
            } catch (error) {
                console.error('渲染单个种子时出错:', error);
            }
        });
        
        // 更新全选框状态
        updateSelectAllCheckbox();
    } catch (error) {
        console.error('渲染种子列表时出错:', error);
    }
}

// 更新分页信息
function updatePaginationInfo(totalItems, totalPages) {
    try {
        const currentPageNum = document.getElementById('currentPageNum');
        const totalPagesEl = document.getElementById('totalPages');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');
        
        if (currentPageNum) currentPageNum.textContent = currentPage;
        if (totalPagesEl) totalPagesEl.textContent = totalPages;
        
        // 启用/禁用分页按钮
        if (prevPageBtn) prevPageBtn.disabled = currentPage <= 1;
        if (nextPageBtn) nextPageBtn.disabled = currentPage >= totalPages || itemsPerPage === 0;
    } catch (error) {
        console.error('更新分页信息时出错:', error);
    }
}

// 更新全选框状态
function updateSelectAllCheckbox() {
    try {
        const selectAllCheckbox = document.getElementById('selectAll');
        if (!selectAllCheckbox) {
            console.warn('未找到全选复选框');
            return;
        }
        
        const checkboxes = document.querySelectorAll('.torrent-check');
        const totalCheckboxes = checkboxes.length;
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        
        console.log('Updating select all checkbox:', {
            totalCheckboxes,
            checkedCount,
            selectedTorrents: Array.from(selectedTorrents)
        });
        
        // 更新全选框状态
        selectAllCheckbox.checked = totalCheckboxes > 0 && checkedCount === totalCheckboxes;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < totalCheckboxes;
    } catch (error) {
        console.error('更新全选框状态时出错:', error);
    }
}

// 切换到指定页
function goToPage(page) {
    if (page < 1 || (itemsPerPage > 0 && page > Math.ceil(allTorrents.length / itemsPerPage))) {
        return; // 页码超出范围
    }
    
    currentPage = page;
    renderTorrents();
}

// 更改每页显示数量
function changeItemsPerPage() {
    const newItemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
    itemsPerPage = newItemsPerPage;
    currentPage = 1; // 重置为第一页
    renderTorrents();
}

// 全选/取消全选
function toggleSelectAll() {
    try {
        const selectAllCheckbox = document.getElementById('selectAll');
        if (!selectAllCheckbox) {
            console.warn('未找到全选复选框');
            return;
        }
        
        const checked = selectAllCheckbox.checked;
        console.log('Toggle all:', checked);
        
        const checkboxes = document.querySelectorAll('.torrent-check');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
            const id = checkbox.value;
            if (checked) {
                selectedTorrents.add(id);
            } else {
                selectedTorrents.delete(id);
            }
        });
        
        console.log('Selected torrents after toggle:', Array.from(selectedTorrents));
        
        // 更新全选框状态
        updateSelectAllCheckbox();
    } catch (error) {
        console.error('切换全选状态时出错:', error);
    }
}

// 暂停/继续单个种子
async function pauseResumeTorrent(hash) {
    if (!currentDownloaderId) {
        alert('错误：无法获取下载器ID');
        return;
    }

    try {
        const apiEndpoint = getApiEndpoint(currentDownloaderType, 'pause_resume', currentDownloaderId);
        
        // 根据下载器类型构建不同的请求体
        let requestBody = {};
        if (currentDownloaderType === 'transmission') {
            // Transmission使用ids参数而不是hash
            requestBody = {
                ids: hash
            };
        } else {
            // qBittorrent使用hash参数
            requestBody = {
                hash: hash
            };
        }
        
        console.log('暂停/继续请求体:', requestBody);
        
        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });
        
        const data = await response.json();
        if (data.success) {
            showTorrents(currentDownloaderId, currentDownloaderType);
        } else {
            alert(data.error || data.message || '操作失败，请稍后重试');
        }
    } catch (error) {
        console.error('操作失败:', error);
        alert('操作失败，请稍后重试');
    }
}

// 删除单个种子
async function deleteSingleTorrent(hash) {
    if (!currentDownloaderId) {
        alert('错误：无法获取下载器ID');
        return;
    }

    if (!confirm('确定要删除这个种子吗？')) {
        return;
    }
    
    const deleteFiles = document.getElementById('deleteFiles').checked;
    
    try {
        const apiEndpoint = getApiEndpoint(currentDownloaderType, 'delete', currentDownloaderId);
        
        // 根据下载器类型构建不同的请求体
        let requestBody = {};
        if (currentDownloaderType === 'transmission') {
            // Transmission使用ids参数而不是hash
            requestBody = {
                ids: hash,
                'delete-local-data': deleteFiles
            };
        } else {
            // qBittorrent使用hash参数
            requestBody = {
                hash: hash,
                deleteFiles: deleteFiles
            };
        }
        
        console.log('删除单个种子请求体:', requestBody);
        
        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });
        
        const data = await response.json();
        if (data.success) {
            showResultAndRefresh('删除成功');
        } else {
            alert(data.error || data.message || '删除种子失败，请稍后重试');
        }
    } catch (error) {
        console.error('删除种子失败:', error);
        alert('删除种子失败，请稍后重试');
    }
}

// 隐藏种子列表
function hideTorrentsModal() {
    try {
        const modal = document.getElementById('torrentsModal');
        if (modal) {
            modal.style.display = 'none';
        } else {
            console.warn('未找到种子列表模态框元素');
        }
        
        // 重置选中的种子
        selectedTorrents.clear();
        
        // 重置当前下载器ID
        currentDownloaderId = null;
        currentDownloaderType = null;
    } catch (error) {
        console.error('隐藏种子列表模态框时出错:', error);
    }
}

// 删除种子
async function deleteTorrents() {
    if (!currentDownloaderId) {
        alert('错误：无法获取下载器ID');
        return;
    }

    console.log('Selected torrents for deletion:', Array.from(selectedTorrents));
    
    if (selectedTorrents.size === 0) {
        alert('请选择要删除的种子');
        return;
    }
    
    if (!confirm('确定要删除选中的种子吗？')) {
        return;
    }
    
    const deleteFiles = document.getElementById('deleteFiles').checked;
    
    try {
        const apiEndpoint = getApiEndpoint(currentDownloaderType, 'delete', currentDownloaderId);
        
        // 根据下载器类型构建不同的请求体
        let requestBody = {};
        if (currentDownloaderType.toLowerCase() === 'transmission') {
            // Transmission使用ids参数而不是hashes
            requestBody = {
                ids: Array.from(selectedTorrents),
                'delete-local-data': deleteFiles
            };
        } else {
            // qBittorrent使用hashes参数
            requestBody = {
                hashes: Array.from(selectedTorrents).join('|'),
                deleteFiles: deleteFiles
            };
        }
        
        console.log('删除多个种子请求体:', requestBody);
        
        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });
        
        const data = await response.json();
        if (data.success) {
            selectedTorrents.clear(); // 清空选中的种子
            showResultAndRefresh('删除成功');
        } else {
            alert(data.error || data.message || '删除种子失败，请稍后重试');
        }
    } catch (error) {
        console.error('删除种子失败:', error);
        alert('删除种子失败，请稍后重试');
    }
}

// 校验种子
async function recheckTorrents() {
    if (!currentDownloaderId) {
        alert('错误：无法获取下载器ID');
        return;
    }

    console.log('Selected torrents for recheck:', Array.from(selectedTorrents));
    
    if (selectedTorrents.size === 0) {
        alert('请选择要校验的种子');
        return;
    }
    
    try {
        const apiEndpoint = getApiEndpoint(currentDownloaderType, 'recheck', currentDownloaderId);
        
        // 根据下载器类型构建不同的请求体
        let requestBody = {};
        if (currentDownloaderType.toLowerCase() === 'transmission') {
            // Transmission使用ids参数而不是hashes
            requestBody = {
                ids: Array.from(selectedTorrents)
            };
        } else {
            // qBittorrent使用hashes参数
            requestBody = {
                hashes: Array.from(selectedTorrents).join('|')
            };
        }
        
        console.log('校验种子请求体:', requestBody);
        
        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });
        
        const data = await response.json();
        if (data.success) {
            selectedTorrents.clear(); // 清空选中的种子
            showResultAndRefresh('已开始校验选中的种子');
        } else {
            alert(data.error || data.message || '校验种子失败，请稍后重试');
        }
    } catch (error) {
        console.error('校验种子失败:', error);
        alert('校验种子失败，请稍后重试');
    }
}

// 重新汇报
async function reAnnounceTorrents() {
    if (!currentDownloaderId) {
        alert('错误：无法获取下载器ID');
        return;
    }

    console.log('Selected torrents for reannounce:', Array.from(selectedTorrents));
    
    if (selectedTorrents.size === 0) {
        alert('请选择要重新汇报的种子');
        return;
    }
    
    try {
        const apiEndpoint = getApiEndpoint(currentDownloaderType, 'reannounce', currentDownloaderId);
        
        // 根据下载器类型构建不同的请求体
        let requestBody = {};
        if (currentDownloaderType.toLowerCase() === 'transmission') {
            // Transmission使用ids参数而不是hashes
            requestBody = {
                ids: Array.from(selectedTorrents)
            };
        } else {
            // qBittorrent使用hashes参数
            requestBody = {
                hashes: Array.from(selectedTorrents).join('|')
            };
        }
        
        console.log('重新汇报请求体:', requestBody);
        
        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });
        
        const data = await response.json();
        if (data.success) {
            selectedTorrents.clear(); // 清空选中的种子
            showResultAndRefresh('已重新汇报选中的种子');
        } else {
            alert(data.error || data.message || '重新汇报失败，请稍后重试');
        }
    } catch (error) {
        console.error('重新汇报失败:', error);
        alert('重新汇报失败，请稍后重试');
    }
}

// 显示操作结果并刷新列表
function showResultAndRefresh(message) {
    alert(message);
    if (currentDownloaderId) {
        showTorrents(currentDownloaderId, currentDownloaderType);
        updateDownloaderStats(currentDownloaderId, currentDownloaderType);
    }
}

// 防止点击模态框外部关闭
document.getElementById('torrentsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        // 不做任何事情，保持模态框打开
    }
});

// 初始化：获取所有下载器的统计信息
document.addEventListener('DOMContentLoaded', function() {
    console.log('Document ready, initializing downloaders...');
    
    // 确保模态框关闭时重置当前下载器ID
    const closeButton = document.querySelector('.btn-close');
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            currentDownloaderId = null;
            currentDownloaderType = null;
        });
    }
    
    // 注意：下载器的初始化已经在 initializeDownloaders 函数中处理
});

// 显示增加下载模态框
function showAddTorrentsModal(downloaderId, downloaderType) {
    currentDownloaderId = downloaderId;
    currentDownloaderType = downloaderType;
    document.getElementById('addTorrentsModal').style.display = 'block';
    document.getElementById('torrentUrls').value = '';
    document.getElementById('savePath').value = '';
}

// 隐藏增加下载模态框
function hideAddTorrentsModal() {
    document.getElementById('addTorrentsModal').style.display = 'none';
}

// 添加种子
async function addTorrents() {
    if (!currentDownloaderId) {
        alert('错误：无法获取下载器ID');
        return;
    }

    const torrentUrls = document.getElementById('torrentUrls').value.trim();
    if (!torrentUrls) {
        alert('请输入至少一个种子链接');
        return;
    }

    const savePath = document.getElementById('savePath').value.trim();
    const urls = torrentUrls.split('\n').filter(url => url.trim() !== '');
    
    if (urls.length === 0) {
        alert('请输入至少一个有效的种子链接');
        return;
    }

    const addButton = document.querySelector('#addTorrentsModal .btn-primary');
    const originalText = addButton.innerHTML;
    const cancelButton = document.querySelector('#addTorrentsModal .btn-secondary');
    
    try {
        // 显示加载状态
        addButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 添加中...';
        addButton.disabled = true;
        cancelButton.disabled = true;

        const apiEndpoint = getApiEndpoint(currentDownloaderType, 'add', currentDownloaderId);
        const requestBody = currentDownloaderType.toLowerCase() === 'transmission' ? {
            filenames: urls,
            'download-dir': savePath || undefined
        } : {
            urls: urls,
            savepath: savePath || undefined
        };

        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(requestBody)
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || data.message || '未知错误');
        }

        // 显示成功提示
        const successCount = urls.length - (data.failedUrls?.length || 0);
        if (data.failedUrls?.length > 0) {
            alert(`成功添加 ${successCount} 个种子，失败 ${data.failedUrls.length} 个`);
        } else {
            alert(`成功添加 ${urls.length} 个种子`);
        }

        hideAddTorrentsModal();
        updateDownloaderStats(currentDownloaderId, currentDownloaderType);
        
        // 如果当前显示种子列表则刷新
        if (document.getElementById('torrentsModal').style.display === 'block') {
            showTorrents(currentDownloaderId, currentDownloaderType);
        }
    } catch (error) {
        console.error('添加失败:', error);
        alert(`添加种子失败: ${error.message}`);
    } finally {
        // 恢复按钮状态
        addButton.innerHTML = originalText;
        addButton.disabled = false;
        cancelButton.disabled = false;
    }
}
</script>

<?php
require_once 'layouts/footer.php';
?>

