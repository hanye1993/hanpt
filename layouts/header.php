<?php
session_start();

// 检查是否已安装
if (!file_exists(__DIR__ . '/../config/installed.php')) {
    header('Location: /install.php');
    exit;
}

// 检查是否已登录
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php' && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    header('Location: /login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>下载管理系统</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/main.js" defer></script>
</head>
<body>
    <header class="main-header">
        <nav class="nav-container">
            <div class="nav-left">
                <a href="/" class="nav-logo">下载管理系统</a>
                <ul class="nav-menu">
                    <li><a href="/index.php">首页</a></li>
                    <li><a href="/downloader.php">下载器</a></li>
                    <li><a href="/vampire.php">PeerBan管理</a></li>
                    <li><a href="/plugins.php">插件</a></li>
                    <li><a href="/sites.php">站点</a></li>
                    <li><a href="/settings.php">设置</a></li>
                </ul>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="nav-right">
                <div class="user-profile" id="userProfile">
                    <img src="<?= $_SESSION['avatar'] ?? '/assets/images/OIP-C.jpg' ?>" alt="用户头像" class="avatar">
                    <span class="username"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <div class="dropdown-menu">
                        <a href="/profile.php">个人信息</a>
                        <a href="/logs.php">系统日志</a>
                        <a href="/logout.php">退出登录</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </nav>
    </header>
    <main class="content-wrapper">
