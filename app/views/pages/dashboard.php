<?php
// 首页视图

// 检查是否登录
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 当前页面标识
$current_page = 'dashboard';
$page_title = '仪表盘';

// 开始输出缓冲
ob_start();

// 格式化文件大小
function formatSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<div class="dashboard-container">
    <!-- 统计卡片 -->
    <div class="stats-cards">
        <div class="card stats-card teal-card">
            <div class="card-icon">
                <i class="fas fa-yen-sign"></i>
            </div>
            <div class="card-content">
                <div class="card-title">全部种子数量</div>
                <div class="card-value"><?php echo number_format($torrentCount); ?></div>
            </div>
        </div>
        
        <div class="card stats-card coral-card">
            <div class="card-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-content">
                <div class="card-title">全部站点数量</div>
                <div class="card-value"><?php echo number_format($siteCount); ?></div>
            </div>
        </div>
        
        <div class="card stats-card green-card">
            <div class="card-icon">
                <i class="fas fa-download"></i>
            </div>
            <div class="card-content">
                <div class="card-title">全部种子体积</div>
                <div class="card-value"><?php echo formatSize($torrentSize); ?></div>
            </div>
        </div>
        
        <div class="card stats-card purple-card">
            <div class="card-icon">
                <i class="fas fa-comment"></i>
            </div>
            <div class="card-content">
                <div class="card-title">新增公告</div>
                <div class="card-value"><?php echo count($announcements); ?> 条</div>
            </div>
        </div>
    </div>
    
    <!-- 公告区域 -->
    <div class="card announcement-card">
        <div class="card-header">
            <h3><i class="fas fa-bullhorn"></i> 开源信息</h3>
        </div>
        <div class="card-body">
            <?php if (empty($announcements)): ?>
                <p class="text-muted">暂无公告</p>
            <?php else: ?>
                <ul class="announcement-list">
                    <?php foreach ($announcements as $announcement): ?>
                        <li><?php echo htmlspecialchars($announcement); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 图表区域 -->
    <div class="charts-container">
        <!-- 每周用户 -->
        <div class="card chart-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> 每周用户</h3>
            </div>
            <div class="card-body">
                <canvas id="weeklyUsersChart"></canvas>
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
    color: white;
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
    opacity: 0.8;
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
    opacity: 0.8;
    margin-bottom: 5px;
}

.card-value {
    font-size: 1.8rem;
    font-weight: bold;
}

.announcement-card, .chart-card {
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
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 每周用户图表
    const weeklyUsersCtx = document.getElementById('weeklyUsersChart').getContext('2d');
    const weeklyUsersChart = new Chart(weeklyUsersCtx, {
        type: 'bar',
        data: {
            labels: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
            datasets: [
                {
                    label: '注册用户',
                    data: [2500, 1500, 1200, 3150, 4700, 3500, 1500],
                    backgroundColor: '#4fd1c5',
                    borderColor: '#4fd1c5',
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
// 获取输出缓冲内容
$content = ob_get_clean();

// 包含主布局文件
include_once __DIR__ . '/../layouts/main.php';
?> 