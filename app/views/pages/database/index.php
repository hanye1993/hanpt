<?php
// 数据库管理页面

// 检查是否登录
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 获取闪存消息
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// 开始输出缓冲
ob_start();
?>

<div class="page-header">
    <h1>数据库管理</h1>
    <p>管理数据库备份和恢复操作</p>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?php echo $flash['type']; ?>">
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<div class="page-card">
    <div class="card-header">
        <h3>数据库操作</h3>
    </div>
    <div class="card-body">
        <div class="action-buttons">
            <form action="/database/backup" method="post" class="inline-form">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-download"></i> 创建备份
                </button>
            </form>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="card-header">
        <h3>备份列表</h3>
    </div>
    <div class="card-body">
        <?php if (empty($backupFiles)): ?>
            <div class="empty-state">
                <i class="fas fa-database"></i>
                <p>暂无备份文件</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>文件名</th>
                            <th>大小</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backupFiles as $file): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($file['name']); ?></td>
                                <td><?php echo formatFileSize($file['size']); ?></td>
                                <td><?php echo htmlspecialchars($file['date']); ?></td>
                                <td class="actions">
                                    <div class="btn-group">
                                        <a href="/database/download/<?php echo urlencode($file['name']); ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-download"></i> 下载
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#restoreModal" data-filename="<?php echo htmlspecialchars($file['name']); ?>">
                                            <i class="fas fa-undo"></i> 恢复
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-filename="<?php echo htmlspecialchars($file['name']); ?>">
                                            <i class="fas fa-trash"></i> 删除
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 恢复确认模态框 -->
<div class="modal fade" id="restoreModal" tabindex="-1" role="dialog" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restoreModalLabel">确认恢复</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>您确定要恢复此备份吗？这将覆盖当前数据库中的所有数据。</p>
                <p class="text-danger">此操作不可逆，请确保您已经备份了当前数据。</p>
            </div>
            <div class="modal-footer">
                <form action="/database/restore" method="post">
                    <input type="hidden" name="filename" id="restoreFilename" value="">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">确认恢复</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 删除确认模态框 -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">确认删除</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>您确定要删除此备份吗？</p>
                <p class="text-danger">此操作不可逆，删除后将无法恢复。</p>
            </div>
            <div class="modal-footer">
                <form action="/database/delete" method="post">
                    <input type="hidden" name="filename" id="deleteFilename" value="">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-danger">确认删除</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.inline-form {
    display: inline-block;
}

.action-buttons {
    margin-bottom: 20px;
}

.empty-state {
    text-align: center;
    padding: 50px 0;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 20px;
}

.actions .btn-group {
    display: flex;
    gap: 5px;
}

.modal-content {
    background-color: var(--card-bg);
    color: var(--text-color);
    border-radius: 12px;
}

.modal-header {
    border-bottom-color: var(--border-color);
}

.modal-footer {
    border-top-color: var(--border-color);
}

.close {
    color: var(--text-color);
}

.text-danger {
    color: var(--danger-color) !important;
}
</style>

<script>
// 设置模态框中的文件名
document.addEventListener('DOMContentLoaded', function() {
    // 恢复模态框
    var restoreModal = document.getElementById('restoreModal');
    if (restoreModal) {
        restoreModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var filename = button.getAttribute('data-filename');
            var input = document.getElementById('restoreFilename');
            input.value = filename;
        });
    }
    
    // 删除模态框
    var deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var filename = button.getAttribute('data-filename');
            var input = document.getElementById('deleteFilename');
            input.value = filename;
        });
    }
});
</script>

<?php
/**
 * 格式化文件大小
 * 
 * @param int $bytes 字节数
 * @param int $precision 精度
 * @return string 格式化后的大小
 */
function formatFileSize($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// 获取输出缓冲内容
$content = ob_get_clean();

// 包含主布局文件
include_once __DIR__ . '/../../layouts/main.php';
?> 