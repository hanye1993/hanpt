<?php
require_once 'layouts/header.php';
require_once 'includes/db.php';

$db = Database::getInstance();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'] ?? '';
    
    // 验证当前密码
    $user = $db->query("SELECT * FROM users WHERE id = ?", [$user_id])->fetch();
    if (!$user || !password_verify($current_password, $user['password'])) {
        $error = '当前密码错误';
    } else {
        $updates = [];
        $params = [];
        
        // 更新用户名
        if (!empty($_POST['username']) && $_POST['username'] !== $user['username']) {
            // 检查用户名是否已存在
            $exists = $db->query("SELECT id FROM users WHERE username = ? AND id != ?", 
                [$_POST['username'], $user_id])->fetch();
            if ($exists) {
                $error = '用户名已被使用';
            } else {
                $updates[] = "username = ?";
                $params[] = $_POST['username'];
                $_SESSION['username'] = $_POST['username'];
            }
        }
        
        // 更新密码
        if (!empty($_POST['new_password'])) {
            if (strlen($_POST['new_password']) < 6) {
                $error = '新密码长度不能小于6位';
            } else {
                $updates[] = "password = ?";
                $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            }
        }
        
        // 处理头像上传
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
                $error = '只支持 JPG、PNG 和 GIF 格式的图片';
            } elseif ($_FILES['avatar']['size'] > $max_size) {
                $error = '图片大小不能超过5MB';
            } else {
                $upload_dir = 'assets/images/avatars/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'OIP-C.jpg';
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filepath)) {
                    $updates[] = "avatar = ?";
                    $params[] = '/' . $filepath;
                    $_SESSION['avatar'] = '/' . $filepath;
                } else {
                    $error = '头像上传失败';
                }
            }
        }
        
        // 执行更新
        if (!$error && !empty($updates)) {
            $params[] = $user_id;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            try {
                $db->query($sql, $params);
                $success = '个人信息更新成功';
                
                // 记录操作日志
                $db->insert('logs', [
                    'type' => 'operation',
                    'message' => "用户 {$_SESSION['username']} 更新了个人信息"
                ]);
            } catch (PDOException $e) {
                $error = '更新失败：' . $e->getMessage();
            }
        }
    }
}

// 获取当前用户信息
$user = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
?>

<div class="dashboard">
    <div class="card">
        <h1 class="card-title">个人信息</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="form-container" enctype="multipart/form-data">
            <div class="avatar-upload">
                <div class="current-avatar">
                    <img src="<?= $user['avatar'] ?? '/assets/images/default-avatar.png' ?>" 
                         alt="当前头像" id="currentAvatar">
                </div>
                <div class="avatar-input">
                    <label class="btn btn-primary" for="avatarInput">
                        更换头像
                        <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display: none;">
                    </label>
                    <p class="help-text">支持 JPG、PNG 和 GIF 格式，大小不超过5MB</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">用户名</label>
                <input type="text" name="username" class="form-input" 
                       value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">当前密码</label>
                <input type="password" name="current_password" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">新密码（不修改请留空）</label>
                <input type="password" name="new_password" class="form-input" minlength="6">
            </div>
            
            <button type="submit" class="btn btn-primary">保存修改</button>
        </form>
    </div>
</div>

<style>
.avatar-upload {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}

.current-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid var(--border);
}

.current-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-input {
    flex: 1;
}

.help-text {
    margin-top: 8px;
    font-size: 12px;
    color: #666;
}

.alert {
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-success {
    background: rgba(76, 175, 80, 0.1);
    color: var(--success);
    border: 1px solid var(--success);
}

.alert-error {
    background: rgba(255, 68, 68, 0.1);
    color: var(--error);
    border: 1px solid var(--error);
}
</style>

<script>
document.getElementById('avatarInput').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('currentAvatar').src = e.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>

<?php
require_once 'layouts/footer.php';
?>
