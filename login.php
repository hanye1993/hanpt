<?php
session_start();

// 检查是否已安装
if (!file_exists(__DIR__ . '/config/installed.php')) {
    header('Location: install.php');
    exit;
}

// 如果已经登录，跳转到首页
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM users WHERE username = ?", [$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['avatar'] = $user['avatar'];
            
            // 记录登录日志
            $db->insert('logs', [
                'type' => 'operation',
                'message' => "用户 {$user['username']} 登录成功"
            ]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    } catch (PDOException $e) {
        $error = '登录失败：' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>用户登录</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="content-wrapper">
        <div class="card" style="max-width: 400px; margin: 100px auto;">
            <h1 class="card-title">用户登录</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-container">
                <div class="form-group">
                    <label class="form-label">用户名</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">密码</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">登录</button>
            </form>
        </div>
    </div>
</body>
</html>
