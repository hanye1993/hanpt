<?php
require_once 'layouts/header.php';
require_once 'includes/db.php';
require_once 'includes/qbittorrent.php';

$db = Database::getInstance();

// 获取启用的下载器列表
$downloaders = $db->query("SELECT * FROM downloaders WHERE status = 1")->fetchAll();

// 获取统计数据
try {
    $stats = $db->query("
        SELECT 
            COUNT(*) as total_peers,
            (SELECT COUNT(*) FROM peer_bans WHERE unban_time IS NULL) as banned_peers,
            (SELECT COUNT(*) FROM peer_bans WHERE unban_time IS NOT NULL) as unbanned_peers,
            (SELECT COUNT(*) FROM peer_bans WHERE unban_time > NOW()) as active_bans,
            (SELECT COUNT(*) FROM peer_checks) as total_checks
        FROM peer_bans
    ")->fetch();
} catch (PDOException $e) {
    // 如果表不存在，使用默认值
    $stats = [
        'total_peers' => 0,
        'banned_peers' => 0,
        'unbanned_peers' => 0,
        'active_bans' => 0,
        'total_checks' => 0
    ];
}
?>

<div class="dashboard">
    <div class="card">
        <div class="card-header">
            <h2>PeerBan管理</h2>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card clickable" onclick="showBannedPeersFromChecks()">
                <h3>共检查</h3>
                <div class="stat-value"><?= number_format($stats['total_checks']) ?><span>次</span></div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h3>已连接的下载器</h3>
            </div>
            
            <div class="downloader-grid">
                <?php foreach($downloaders as $downloader): ?>
                <div class="downloader-card" id="downloader-<?= $downloader['id'] ?>">
                    <div class="downloader-header">
                        <img src="assets/<?= strtolower($downloader['type']) ?>.png" alt="<?= htmlspecialchars($downloader['name']) ?>" class="downloader-icon">
                        <h4><?= htmlspecialchars($downloader['name']) ?></h4>
                    </div>
                    <div class="downloader-info">
                        <div class="info-row">
                            <span class="label">类型</span>
                            <span class="value"><?= $downloader['type'] === 'transmission' ? 'Transmission' : 'qBittorrent' ?> <i class="fas fa-info-circle" title="<?= $downloader['type'] === 'transmission' ? 'Transmission RPC' : 'qBittorrent WebUI' ?>"></i></span>
                        </div>
                        <div class="info-row">
                            <span class="label">状态</span>
                            <span class="value status-ok"><i class="fas fa-check-circle"></i> 正常</span>
                        </div>
                        <div class="info-row">
                            <span class="label">活动种子数</span>
                            <span class="value active-torrents">0</span>
                        </div>
                        <div class="info-row">
                            <span class="label">已连接的Peers</span>
                            <span class="value connected-peers">0</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h3>活动种子</h3>
                <div class="tab-group">
                    <?php foreach($downloaders as $downloader): ?>
                    <button class="tab-btn" onclick="switchDownloader(<?= $downloader['id'] ?>)"><?= htmlspecialchars($downloader['name']) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="torrent-list">
                <div class="torrent-header">
                    <div class="col">名称</div>
                    <div class="col">速度</div>
                    <div class="col">大小</div>
                    <div class="col">Hash</div>
                    <div class="col">进度</div>
                    <div class="col">Peers</div>
                </div>
                <div id="torrent-rows">
                    <div class="no-data">
                        <i class="fas fa-robot"></i>
                        <p>暂无数据</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加Peers详情模态框 -->
<div class="modal" id="peersModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>种子Peers详情</h3>
            <button class="close-btn" onclick="closePeersModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="peers-list">
                <div class="peers-header">
                    <div class="peer-col">IP地址</div>
                    <div class="peer-col">客户端</div>
                    <div class="peer-col">上传速度</div>
                    <div class="peer-col">下载速度</div>
                    <div class="peer-col">进度</div>
                    <div class="peer-col">状态</div>
                    <div class="peer-col">操作</div>
                </div>
                <div id="peers-rows"></div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px;
}

.stat-card {
    background: #f5f5f5;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
    color: #666;
    font-size: 14px;
    margin: 0 0 10px 0;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.stat-value span {
    font-size: 14px;
    color: #666;
    margin-left: 5px;
}

.section {
    margin: 20px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.section-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #333;
}

.downloader-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    padding: 20px;
}

.downloader-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.downloader-card h4 {
    margin: 0 0 15px 0;
    color: #333;
}

.downloader-info {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-row .label {
    color: #666;
}

.info-row .value {
    color: #333;
}

.status-ok {
    color: #28a745 !important;
}

.tab-group {
    display: flex;
    gap: 10px;
}

.tab-btn {
    background: #e9ecef;
    border: none;
    color: #333;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.tab-btn:hover {
    background: #dee2e6;
}

.tab-btn.active {
    background: #28a745;
    color: #fff;
}

.torrent-list {
    padding: 20px;
}

.torrent-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr;
    gap: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 10px;
    color: #666;
    font-weight: bold;
}

.torrent-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr;
    gap: 10px;
    padding: 10px;
    border-bottom: 1px solid #eee;
    align-items: center;
}

.torrent-row:hover {
    background: #f8f9fa;
}

.torrent-row .col {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #333;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #666;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
}

.no-data i {
    font-size: 48px;
    margin-bottom: 10px;
    color: #adb5bd;
}

.btn {
    padding: 6px 12px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #007bff;
    color: #fff;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

/* 模态框样式 */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    position: relative;
    background: #fff;
    width: 90%;
    max-width: 1000px;
    margin: 50px auto;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close-btn:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.peers-list {
    border: 1px solid #eee;
    border-radius: 4px;
}

.peers-header {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr 1fr;
    gap: 10px;
    padding: 10px;
    background: #f8f9fa;
    font-weight: bold;
    color: #666;
}

.peer-row {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr 1fr;
    gap: 10px;
    padding: 10px;
    border-top: 1px solid #eee;
    align-items: center;
}

.peer-row:hover {
    background: #f8f9fa;
}

.peer-col {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ban-btn {
    background: #dc3545;
    color: #fff;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
}

.ban-btn:hover {
    background: #c82333;
}

.ban-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

/* 修改种子列表中的Peers列样式 */
.torrent-row .col:last-child {
    cursor: pointer;
    color: #007bff;
}

.torrent-row .col:last-child:hover {
    text-decoration: underline;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #666;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
}

.loading i {
    font-size: 48px;
    margin-bottom: 10px;
    color: #007bff;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.downloader-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.downloader-icon {
    width: 24px;
    height: 24px;
    object-fit: contain;
}

.downloader-header h4 {
    margin: 0;
    color: #333;
}

.peer-status {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-align: center;
}

.peer-status-connected {
    background: #28a745;
    color: white;
}

.peer-status-not-connected {
    background: #6c757d;
    color: white;
}

.peer-status-error {
    background: #dc3545;
    color: white;
}

.peer-status-connecting {
    background: #ffc107;
    color: black;
}
</style>

<script>
let currentDownloader = null;
let refreshInterval = null;

async function updateStats() {
    try {
        const response = await fetch('/services/vampire.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            // 更新下载器统计
            data.downloaders.forEach(stats => {
                const card = document.getElementById(`downloader-${stats.id}`);
                if (card) {
                    card.querySelector('.active-torrents').textContent = stats.activeTorrents;
                    card.querySelector('.connected-peers').textContent = stats.connectedPeers;
                }
            });
            
            // 更新共检查次数显示
            const totalChecksElement = document.querySelector('.stats-grid .stat-card .stat-value');
            if (totalChecksElement) {
                const currentChecks = parseInt(totalChecksElement.textContent.replace(/,/g, '')) || 0;
                totalChecksElement.innerHTML = `${currentChecks + 1}<span>次</span>`;
            }
        }
    } catch (error) {
        console.error('更新统计信息失败:', error);
    }
}

async function updateTorrents() {
    if (!currentDownloader) return;
    
    const container = document.getElementById('torrent-rows');
    
    // 显示加载状态
    container.innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>正在加载...</p>
        </div>
    `;
    
    try {
        const response = await fetch(`/services/vampire.php?action=list&id=${currentDownloader}`);
        const data = await response.json();
        
        if (!data.success || !data.torrents || data.torrents.length === 0) {
            container.innerHTML = `
                <div class="no-data">
                    <i class="fas fa-robot"></i>
                    <p>暂无数据</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        data.torrents.forEach(torrent => {
            html += `
                <div class="torrent-row">
                    <div class="col">${torrent.name}</div>
                    <div class="col">${torrent.speed}</div>
                    <div class="col">${torrent.size}</div>
                    <div class="col">${torrent.hash_short}</div>
                    <div class="col">${torrent.progress}%</div>
                    <div class="col" onclick="showPeersDetail('${torrent.hash}')">${torrent.peers} <i class="fas fa-external-link-alt"></i></div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    } catch (error) {
        console.error('获取种子列表失败:', error);
        container.innerHTML = `
            <div class="no-data">
                <i class="fas fa-exclamation-circle"></i>
                <p>加载失败，请稍后重试</p>
            </div>
        `;
    }
}

async function switchDownloader(downloaderId) {
    // 移除所有按钮的 active 类
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // 添加当前按钮的 active 类
    const currentBtn = document.querySelector(`.tab-btn[onclick="switchDownloader(${downloaderId})"]`);
    if (currentBtn) {
        currentBtn.classList.add('active');
    }
    
    // 更新当前下载器
    currentDownloader = downloaderId;
    
    // 更新种子列表（会显示加载状态）
    updateTorrents();
}

async function showPeersDetail(torrentHash) {
    // 显示模态框
    const peersModal = document.getElementById('peersModal');
    peersModal.style.display = 'block';
    peersModal.setAttribute('data-hash', torrentHash);
    
    // 修改模态框标题
    const modalHeader = peersModal.querySelector('.modal-header h3');
    if (modalHeader) {
        modalHeader.textContent = '种子Peers详情';
    }
    
    // 显示加载状态
    document.getElementById('peers-rows').innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>正在加载...</p>
        </div>
    `;
    
    try {
        // 使用vampire.php的peers接口获取peers信息
        const response = await fetch(`/services/vampire.php?action=peers&id=${currentDownloader}&hash=${torrentHash}`);
        const data = await response.json();
        
        const peersList = document.getElementById('peers-rows');
        
        if (!data.success || !data.peers || data.peers.length === 0) {
            peersList.innerHTML = `
                <div class="no-data">
                    <i class="fas fa-users-slash"></i>
                    <p>暂无Peers信息</p>
                </div>
            `;
            return;
        }
        
        // 修改peers-header以适应peers数据结构
        const peersHeader = document.querySelector('.peers-header');
        if (peersHeader) {
            peersHeader.innerHTML = `
                <div class="peer-col">IP地址</div>
                <div class="peer-col">客户端</div>
                <div class="peer-col">上传速度</div>
                <div class="peer-col">下载速度</div>
                <div class="peer-col">进度</div>
                <div class="peer-col">状态</div>
                <div class="peer-col">操作</div>
            `;
        }
        
        let html = '';
        data.peers.forEach(peer => {
            const statusClass = `peer-status-${peer.status}`;
            const statusText = getStatusText(peer.status);
            
            html += `
                <div class="peer-row">
                    <div class="peer-col">${peer.ip}</div>
                    <div class="peer-col">${peer.client}</div>
                    <div class="peer-col">${formatSpeed(peer.up_speed)}</div>
                    <div class="peer-col">${formatSpeed(peer.dl_speed)}</div>
                    <div class="peer-col">${peer.progress}%</div>
                    <div class="peer-col">
                        <span class="peer-status ${statusClass}">${statusText}</span>
                    </div>
                    <div class="peer-col">
                        <button class="ban-btn" onclick="banPeer('${peer.ip}')" ${peer.is_banned ? 'disabled' : ''}>
                            ${peer.is_banned ? '已封禁' : '封禁'}
                        </button>
                    </div>
                </div>
            `;
        });
        
        peersList.innerHTML = html;
    } catch (error) {
        console.error('获取Peers详情失败:', error);
        document.getElementById('peers-rows').innerHTML = `
            <div class="no-data">
                <i class="fas fa-exclamation-circle"></i>
                <p>加载失败，请稍后重试</p>
            </div>
        `;
    }
}

// 根据下载器ID获取下载器名称
async function getDownloaderName(downloaderId) {
    try {
        const response = await fetch(`/services/vampire.php?action=downloader_info&id=${downloaderId}`);
        const data = await response.json();
        
        if (data.success && data.downloader) {
            return data.downloader.name;
        }
        return null;
    } catch (error) {
        console.error('获取下载器信息失败:', error);
        return null;
    }
}

function banPeer(ip) {
    if (confirm('确定要封禁这个IP吗？')) {
        fetch('/services/vampire.php?action=ban&ip=' + encodeURIComponent(ip) + '&downloader_id=' + currentDownloader)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('IP封禁成功');
                    // 刷新 peers 列表
                    const peersModal = document.getElementById('peersModal');
                    if (peersModal.style.display === 'block') {
                        // 获取当前显示的种子 hash
                        const torrentHash = peersModal.getAttribute('data-hash');
                        if (torrentHash) {
                            showPeersDetail(torrentHash);
                        }
                    }
                    updateStats();
                } else {
                    alert('IP封禁失败: ' + (data.error || '未知错误'));
                }
            })
            .catch(error => {
                console.error('IP封禁请求失败:', error);
                alert('IP封禁请求失败，请查看控制台获取详细信息');
            });
    }
}

function closePeersModal() {
    document.getElementById('peersModal').style.display = 'none';
}

// 格式化速度
function formatSpeed(bytesPerSecond) {
    if (bytesPerSecond === 0) return '0 B/s';
    const k = 1024;
    const sizes = ['B/s', 'KB/s', 'MB/s', 'GB/s'];
    const i = Math.floor(Math.log(bytesPerSecond) / Math.log(k));
    return parseFloat((bytesPerSecond / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// 获取状态文本
function getStatusText(status) {
    switch (status) {
        case 'connected':
            return '已连接';
        case 'not_connected':
            return '未连接';
        case 'connecting':
            return '连接中';
        case 'error':
            return '错误';
        default:
            return '未知';
    }
}

// 显示peer_checks库中的封禁peer数据
async function showBannedPeersFromChecks() {
    // 显示模态框
    const peersModal = document.getElementById('peersModal');
    peersModal.style.display = 'block';
    
    // 修改模态框标题
    const modalHeader = peersModal.querySelector('.modal-header h3');
    if (modalHeader) {
        modalHeader.textContent = '检查记录';
    }
    
    // 显示加载状态
    document.getElementById('peers-rows').innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>正在加载...</p>
        </div>
    `;
    
    try {
        // 获取peer_checks库中的数据
        const response = await fetch('/services/vampire.php?action=banned_peers_from_checks');
        const data = await response.json();
        
        const peersList = document.getElementById('peers-rows');
        
        if (!data.success || !data.peers || data.peers.length === 0) {
            peersList.innerHTML = `
                <div class="no-data">
                    <i class="fas fa-users-slash"></i>
                    <p>暂无检查记录</p>
                </div>
            `;
            return;
        }
        
        // 修改peers-header以适应新的数据结构
        const peersHeader = document.querySelector('.peers-header');
        if (peersHeader) {
            peersHeader.innerHTML = `
                <div class="peer-col">IP地址</div>
                <div class="peer-col">种子Hash</div>
                <div class="peer-col">下载速度</div>
                <div class="peer-col">上传速度</div>
                <div class="peer-col">吸血比例</div>
                <div class="peer-col">检查时间</div>
                <div class="peer-col">状态</div>
            `;
        }
        
        let html = '';
        data.peers.forEach(peer => {
            // 格式化检查时间
            const checkTime = new Date(peer.check_time).toLocaleString();
            
            // 确定状态文本和样式
            let statusText = '正常';
            let statusClass = 'peer-status-connected';
            
            if (peer.is_banned == 1) {
                statusText = '已封禁';
                statusClass = 'peer-status-error';
            } else if (peer.vampire_ratio > 0) {
                statusText = '吸血';
                statusClass = 'peer-status-not-connected';
            }
            
            html += `
                <div class="peer-row">
                    <div class="peer-col">${peer.ip}</div>
                    <div class="peer-col">${peer.torrent_hash.substring(0, 8)}...</div>
                    <div class="peer-col">${formatSpeed(peer.download_speed || 0)}</div>
                    <div class="peer-col">${formatSpeed(peer.upload_speed || 0)}</div>
                    <div class="peer-col">${peer.vampire_ratio || 0}</div>
                    <div class="peer-col">${checkTime}</div>
                    <div class="peer-col">
                        <span class="peer-status ${statusClass}">${statusText}</span>
                    </div>
                </div>
            `;
        });
        
        peersList.innerHTML = html;
    } catch (error) {
        console.error('获取检查记录失败:', error);
        document.getElementById('peers-rows').innerHTML = `
            <div class="no-data">
                <i class="fas fa-exclamation-circle"></i>
                <p>加载失败，请稍后重试</p>
            </div>
        `;
    }
}

// 防止点击模态框外部关闭
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            // 不做任何事情，保持模态框打开
        }
    });
});

// 初始化
updateStats();

// 执行吸血检测
async function runVampireCheck() {
    try {
        console.log('执行吸血检测...');
        const response = await fetch('/services/vampire_check.php?api=1');
        const data = await response.json();
        
        // 无论成功与否，都更新统计和种子列表
        updateStats();
        updateTorrents();
        
        if (data.success) {
            console.log('吸血检测完成');
        } else {
            console.error('吸血检测失败');
        }
    } catch (error) {
        console.error('吸血检测请求失败');
        
        // 出错时仍然更新统计和种子列表
        updateStats();
        updateTorrents();
    }
}

// 添加自动刷新功能
document.addEventListener('DOMContentLoaded', function() {
    const autoRefreshToggle = document.getElementById('auto-refresh-toggle');
    
    if (autoRefreshToggle) {
        autoRefreshToggle.addEventListener('change', function() {
            if (this.checked) {
                // 获取刷新间隔（秒）
                const refreshIntervalText = document.querySelector('.refresh-interval').textContent;
                const refreshIntervalSeconds = parseInt(refreshIntervalText) || 300;
                
                // 设置自动刷新
                refreshInterval = setInterval(() => {
                    runVampireCheck();
                }, refreshIntervalSeconds * 1000);
                
                // 立即执行一次
                runVampireCheck();
            } else {
                // 清除自动刷新
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = null;
                }
            }
        });
        
        // 如果默认是选中状态，自动启动刷新
        if (autoRefreshToggle.checked) {
            autoRefreshToggle.dispatchEvent(new Event('change'));
        }
    }
});
</script>


