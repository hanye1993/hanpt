<?php
require_once 'layouts/header.php';
require_once 'includes/db.php';

$db = Database::getInstance();
$type = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;

// 构建查询条件
$where = '';
$params = [];
if ($type !== 'all') {
    $where = 'WHERE type = ?';
    $params[] = $type;
}

// 获取总记录数
$total = $db->query("SELECT COUNT(*) as count FROM logs " . $where, $params)->fetch()['count'];
$total_pages = max(1, ceil($total / $per_page));
$page = min($page, $total_pages);

// 获取日志记录
$offset = max(0, ($page - 1) * $per_page);
$sql = "SELECT * FROM logs {$where} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}";
$logs = $db->query($sql, $params)->fetchAll();

// 获取日志类型统计
$type_stats = $db->query("
    SELECT type, COUNT(*) as count 
    FROM logs 
    GROUP BY type
")->fetchAll();

$type_counts = [];
foreach ($type_stats as $stat) {
    $type_counts[$stat['type']] = $stat['count'];
}
?>

<div class="dashboard">
    <div class="card">
        <h1 class="card-title">系统日志</h1>
        
        <div class="log-stats">
            <a href="?type=all" class="stat-card <?= $type === 'all' ? 'active' : '' ?>">
                <h3>全部日志</h3>
                <p class="stat-value"><?= array_sum($type_counts) ?></p>
            </a>
            <a href="?type=operation" class="stat-card <?= $type === 'operation' ? 'active' : '' ?>">
                <h3>操作日志</h3>
                <p class="stat-value"><?= $type_counts['operation'] ?? 0 ?></p>
            </a>
            <a href="?type=error" class="stat-card <?= $type === 'error' ? 'active' : '' ?>">
                <h3>错误日志</h3>
                <p class="stat-value"><?= $type_counts['error'] ?? 0 ?></p>
            </a>
            <a href="?type=site" class="stat-card <?= $type === 'site' ? 'active' : '' ?>">
                <h3>站点日志</h3>
                <p class="stat-value"><?= $type_counts['site'] ?? 0 ?></p>
            </a>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>时间</th>
                        <th>类型</th>
                        <th>消息</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $log): ?>
                    <tr>
                        <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                        <td>
                            <span class="badge badge-<?= $log['type'] ?>">
                                <?= [
                                    'operation' => '操作',
                                    'error' => '错误',
                                    'site' => '站点'
                                ][$log['type']] ?? $log['type'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($log['message']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?type=<?= $type ?>&page=<?= $page - 1 ?>" class="btn btn-primary">&laquo; 上一页</a>
            <?php endif; ?>
            
            <span class="page-info">第 <?= $page ?> 页，共 <?= $total_pages ?> 页</span>
            
            <?php if ($page < $total_pages): ?>
            <a href="?type=<?= $type ?>&page=<?= $page + 1 ?>" class="btn btn-primary">下一页 &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.log-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s;
    border: 1px solid var(--border);
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card.active {
    border-color: var(--primary);
    background: rgba(33, 150, 243, 0.1);
}

.stat-card h3 {
    font-size: 1em;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 1.5em;
    font-weight: bold;
    color: var(--primary);
}

.badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.badge-operation {
    background: rgba(33, 150, 243, 0.1);
    color: var(--primary);
}

.badge-error {
    background: rgba(255, 68, 68, 0.1);
    color: var(--error);
}

.badge-site {
    background: rgba(76, 175, 80, 0.1);
    color: var(--success);
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 20px;
}

.page-info {
    color: #666;
}
</style>

<?php
require_once 'layouts/footer.php';
?>
