<?php
// 检查是否已安装
if (!file_exists(__DIR__ . '/config/installed.php')) {
    header('Location: install.php');
    exit;
}

// 格式化文件大小
function formatSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

require_once 'layouts/header.php';
?>

<div class="dashboard-container">
    <!-- 统计卡片 -->
    <div class="stats-cards">
        <div class="card stats-card teal-card">
            <div class="card-icon">
                <i class="fas fa-download"></i>
            </div>
            <div class="card-content">
                <div class="card-title">全部种子数量</div>
                <div class="card-value" id="torrentCount">加载中...</div>
            </div>
        </div>
        
        <div class="card stats-card coral-card">
            <div class="card-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-content">
                <div class="card-title">全部站点数量</div>
                <div class="card-value" id="siteCount">加载中...</div>
            </div>
        </div>
        
        <div class="card stats-card green-card">
            <div class="card-icon">
                <i class="fas fa-database"></i>
            </div>
            <div class="card-content">
                <div class="card-title">全部种子体积</div>
                <div class="card-value" id="torrentSize">加载中...</div>
            </div>
        </div>
        
        <div class="card stats-card purple-card">
            <div class="card-icon">
                <i class="fas fa-hdd"></i>
            </div>
            <div class="card-content">
                <div class="card-title">下载器数量</div>
                <div class="card-value" id="downloaderCount">加载中...</div>
            </div>
        </div>
    </div>
    
    <!-- 公告区域 -->
    <div class="card announcement-card">
        <div class="card-header">
            <h3><i class="fas fa-bullhorn"></i> 开源信息</h3>
        </div>
        <div class="card-body">
            <ul class="announcement-list" id="announcements">
                <li>加载中...</li>
            </ul>
        </div>
    </div>
    
    <!-- 图表区域 -->
    <div class="charts-container">
        <!-- 上传下载总量 -->
        <div class="card chart-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> 上传下载总量</h3>
            </div>
            <div class="card-body">
                <canvas id="transferChart"></canvas>
            </div>
        </div>
        
        <!-- 交易历史记录 -->
        <div class="card chart-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-line"></i> 交易历史记录</h3>
            </div>
            <div class="card-body">
                <canvas id="transactionHistoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- 快速导航 -->
    <div class="card nav-card">
        <div class="card-header">
            <h3><i class="fas fa-compass"></i> 快速导航</h3>
        </div>
        <div class="card-body">
            <div class="quick-nav">
                <a href="/downloader.php" class="quick-nav-item">
                    <i class="fas fa-download"></i>
                    <span>下载器</span>
                </a>
                <a href="/vampire.php" class="quick-nav-item">
                    <i class="fas fa-tint"></i>
                    <span>吸血</span>
                </a>
                <a href="/sites.php" class="quick-nav-item">
                    <i class="fas fa-globe"></i>
                    <span>站点</span>
                </a>
                <a href="/settings.php" class="quick-nav-item">
                    <i class="fas fa-cog"></i>
                    <span>设置</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 15px;
    background-color: #f5f7fa;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.stats-card {
    display: flex;
    align-items: center;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    color: #ffffff;
    transition: transform 0.3s ease;
    border: none;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.teal-card {
    background-color: #26c6da;
}

.coral-card {
    background-color: #ff7675;
}

.green-card {
    background-color: #2ecc71;
}

.purple-card {
    background-color: #a29bfe;
}

.card-icon {
    font-size: 2rem;
    margin-right: 20px;
    opacity: 0.9;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-content {
    flex: 1;
    text-align: right;
}

.card-title {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 5px;
    color: rgba(255, 255, 255, 0.9);
}

.card-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #ffffff;
}

.announcement-card, .chart-card, .nav-card {
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    border: none;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.card-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}

.card-body {
    padding: 20px;
}

.announcement-list {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.announcement-list li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    color: #555;
}

.announcement-list li:last-child {
    border-bottom: none;
}

.charts-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.chart-card .card-body {
    height: 350px;
}

.quick-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
}

.quick-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 120px;
    height: 120px;
    background-color: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.quick-nav-item:hover {
    transform: translateY(-5px);
    background-color: #e9ecef;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.quick-nav-item i {
    font-size: 2.5rem;
    margin-bottom: 10px;
    color: #26c6da;
}

.quick-nav-item span {
    font-size: 1rem;
    font-weight: 500;
}

@media (max-width: 1200px) {
    .stats-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .charts-container {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-cards {
        grid-template-columns: 1fr;
    }
    
    .quick-nav-item {
        width: 100px;
        height: 100px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 格式化文件大小的 JavaScript 函数
function formatSize(bytes, precision = 2) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    bytes = Math.max(bytes, 0);
    const pow = Math.floor((bytes ? Math.log(bytes) : 0) / Math.log(1024));
    const pow_clamped = Math.min(pow, units.length - 1);
    
    bytes /= Math.pow(1024, pow_clamped);
    
    return `${bytes.toFixed(precision)} ${units[pow_clamped]}`;
}

// 设置加载状态
function setLoadingState(isLoading) {
    const loadingText = isLoading ? '加载中...' : '0';
    
    if (isLoading) {
        document.getElementById('torrentCount').textContent = '加载中...';
        document.getElementById('siteCount').textContent = '加载中...';
        document.getElementById('downloaderCount').textContent = '加载中...';
        document.getElementById('torrentSize').textContent = '加载中...';
        document.getElementById('announcements').innerHTML = '<li>加载中...</li>';
    }
}

// 获取下载器数据
async function fetchDownloaderData() {
    console.log('开始获取下载器数据');
    
    try {
        // 获取所有下载器
        const response = await fetch('/services/downloaders.php?action=list');
        if (!response.ok) {
            throw new Error(`获取下载器列表失败: ${response.status}`);
        }
        
        const data = await response.json();
        if (!data.success || !Array.isArray(data.downloaders)) {
            throw new Error('获取下载器列表失败: 无效的响应数据');
        }
        
        console.log(`找到 ${data.downloaders.length} 个下载器`);
        
        // 初始化统计数据
        let totalTorrents = 0;
        let totalSize = 0;
        const downloaderStats = [];
        
        // 获取每个下载器的统计信息
        for (const downloader of data.downloaders) {
            console.log(`获取下载器 ${downloader.id} (${downloader.name}) 的统计信息`);
            
            try {
                // 根据下载器类型选择不同的API端点
                const apiEndpoint = downloader.type.toLowerCase() === 'transmission' 
                    ? `/services/transmission.php?action=stats&id=${downloader.id}`
                    : `/services/qbittorrent.php?action=stats&id=${downloader.id}`;
                
                const statsResponse = await fetch(apiEndpoint);
                if (!statsResponse.ok) {
                    console.error(`获取下载器 ${downloader.id} 统计信息失败: ${statsResponse.status}`);
                    continue;
                }
                
                const statsData = await statsResponse.json();
                if (!statsData.success || !statsData.stats) {
                    console.error(`获取下载器 ${downloader.id} 统计信息失败: 无效的响应数据`);
                    continue;
                }
                
                // 累加统计数据
                const stats = statsData.stats;
                totalTorrents += stats.torrent_count || 0;
                totalSize += stats.total_size || 0;
                
                // 保存下载器的上传下载数据
                downloaderStats.push({
                    id: downloader.id,
                    name: downloader.name,
                    type: downloader.type,
                    uploadedData: stats.uploaded || stats.all_time_upload || 0, // 总上传量
                    downloadedData: stats.downloaded || stats.all_time_download || 0, // 总下载量
                    torrentCount: stats.torrent_count || 0,
                    totalSize: stats.total_size || 0
                });
                
                console.log(`下载器 ${downloader.id} (${downloader.name}): ${stats.torrent_count || 0} 个种子, ${formatSize(stats.total_size || 0)} 大小, 上传量: ${formatSize(stats.uploaded || stats.all_time_upload || 0)}, 下载量: ${formatSize(stats.downloaded || stats.all_time_download || 0)}`);
            } catch (error) {
                console.error(`处理下载器 ${downloader.id} 时出错:`, error);
                // 继续处理下一个下载器
            }
        }
        
        console.log(`汇总统计: ${totalTorrents} 个种子, ${formatSize(totalSize)} 大小`);
        
        // 返回汇总的统计数据
        return {
            torrents: totalTorrents,
            torrentSize: totalSize,
            downloaderStats: downloaderStats
        };
    } catch (error) {
        console.error('获取下载器数据失败:', error);
        throw error;
    }
}

// 更新上传下载图表
function updateTransferChart(downloaderStats) {
    const ctx = document.getElementById('transferChart').getContext('2d');
    
    // 准备图表数据
    const labels = downloaderStats.map(stat => stat.name);
    const uploadData = downloaderStats.map(stat => stat.uploadedData / (1024 * 1024 * 1024)); // 转换为GB
    const downloadData = downloaderStats.map(stat => stat.downloadedData / (1024 * 1024 * 1024)); // 转换为GB
    
    // 创建图表
    const transferChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '上传量 (GB)',
                    data: uploadData,
                    backgroundColor: '#4fd1c5',
                    borderColor: '#4fd1c5',
                    borderWidth: 0,
                    borderRadius: 4
                },
                {
                    label: '下载量 (GB)',
                    data: downloadData,
                    backgroundColor: '#ff7675',
                    borderColor: '#ff7675',
                    borderWidth: 0,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f0f0f0'
                    },
                    title: {
                        display: true,
                        text: '数据量 (GB)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: true,
                    text: '下载器上传下载总量'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += formatSize(context.parsed.y * 1024 * 1024 * 1024);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    return transferChart;
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('页面加载完成，开始获取数据...');
    
    // 显示加载状态
    setLoadingState(true);
    
    // 获取系统统计数据
    setTimeout(async () => {
        try {
            // 首先尝试使用 stats.php 获取基本数据
            const statsResponse = await fetch('/services/stats.php');
            if (!statsResponse.ok) {
                throw new Error(`获取基本统计数据失败: ${statsResponse.status}`);
            }
            
            const statsData = await statsResponse.json();
            console.log('从 stats.php 获取到的基本数据:', statsData);
            
            // 更新下载器数量和站点数量
            document.getElementById('downloaderCount').textContent = statsData.downloaders || 0;
            document.getElementById('siteCount').textContent = statsData.sites || 0;
            
            // 加载公告
            if (statsData.announcements && statsData.announcements.length > 0) {
                const announcementsEl = document.getElementById('announcements');
                announcementsEl.innerHTML = '';
                statsData.announcements.forEach(announcement => {
                    const li = document.createElement('li');
                    li.textContent = announcement;
                    announcementsEl.appendChild(li);
                });
            } else {
                document.getElementById('announcements').innerHTML = '<li>暂无公告</li>';
            }
            
            // 尝试获取更准确的下载器数据
            try {
                const downloaderData = await fetchDownloaderData();
                console.log('获取到更准确的下载器数据:', downloaderData);
                
                // 更新种子数量和体积
                document.getElementById('torrentCount').textContent = downloaderData.torrents || 0;
                document.getElementById('torrentSize').textContent = formatSize(downloaderData.torrentSize || 0);
                
                // 更新上传下载图表
                if (downloaderData.downloaderStats && downloaderData.downloaderStats.length > 0) {
                    updateTransferChart(downloaderData.downloaderStats);
                }
            } catch (downloaderError) {
                console.error('获取下载器数据失败，使用 stats.php 的数据:', downloaderError);
                
                // 使用 stats.php 的数据作为备用
                document.getElementById('torrentCount').textContent = statsData.torrents || 0;
                document.getElementById('torrentSize').textContent = formatSize(statsData.torrentSize || 0);
            }
        } catch (error) {
            console.error('获取统计数据失败:', error);
            
            // 设置默认值
            document.getElementById('torrentCount').textContent = '0';
            document.getElementById('siteCount').textContent = '0';
            document.getElementById('downloaderCount').textContent = '0';
            document.getElementById('torrentSize').textContent = '0 B';
            document.getElementById('announcements').innerHTML = '<li>获取数据失败: ' + error.message + '</li>';
        }
    }, 500); // 延迟500毫秒，确保"加载中"状态能够显示
    
    // 交易历史记录图表
    const transactionHistoryCtx = document.getElementById('transactionHistoryChart').getContext('2d');
    const transactionHistoryChart = new Chart(transactionHistoryCtx, {
        type: 'line',
        data: {
            labels: ['2003', '2004', '2005', '2006', '2007', '2008', '2009', '2010', '2011', '2012', '2013', '2014'],
            datasets: [
                {
                    label: '交易数量',
                    data: [20, 25, 40, 30, 45, 40, 55, 40, 48, 40, 42, 50],
                    backgroundColor: 'rgba(116, 185, 255, 0.1)',
                    borderColor: '#74b9ff',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#74b9ff'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f0f0f0'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
});
</script>

<?php
require_once 'layouts/footer.php';
?>
