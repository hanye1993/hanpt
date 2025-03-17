<?php
/**
 * 主布局文件
 * 
 * 这个文件是应用程序的主布局模板，包含了页面的基本结构和公共组件。
 * 使用方法：在页面文件中设置必要的变量，然后包含此布局文件。
 * 
 * 必要变量:
 * - $page_title: 页面标题
 * - $current_page: 当前页面标识，用于导航高亮
 * - $content: 页面主要内容（可选，如果不提供则需要在包含此文件后直接输出内容）
 */

// 启动会话（如果尚未启动）
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查是否登录（可以根据需要移除或修改此检查）
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: /login.php');
    exit;
}

// 获取用户信息
$username = $_SESSION['username'] ?? '';
$avatar = $_SESSION['avatar'] ?? '';

// 当前主题模式（默认为light）
$theme_mode = $_COOKIE['theme_mode'] ?? 'light';

// 设置默认页面标题（如果未提供）
if (!isset($page_title)) {
    $page_title = 'ThinkPHP应用';
}

// 设置默认当前页面（如果未提供）
if (!isset($current_page)) {
    $current_page = '';
}

// 包含头部
include_once __DIR__ . '/../components/header.php';

// 包含侧边栏
include_once __DIR__ . '/../components/sidebar.php';

// 如果提供了内容变量，则输出它
if (isset($content)) {
    echo $content;
}

// 注意：如果没有提供$content变量，则应该在包含此文件后直接输出页面内容

// 包含底部
include_once __DIR__ . '/../components/footer.php';
?>

<!DOCTYPE html>
<html lang="zh-CN" data-theme="<?php echo isset($theme_mode) ? $theme_mode : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ThinkPHP应用' : 'ThinkPHP应用'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($extra_css) && !empty($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php 
    // 包含头部组件
    include_once __DIR__ . '/../components/header.php'; 
    ?>

    <div class="main-container">
        <?php 
        // 包含侧边栏组件
        include_once __DIR__ . '/../components/sidebar.php'; 
        ?>

        <main class="content">
            <?php 
            // 显示闪存消息
            if (isset($_SESSION['flash_message']) && !empty($_SESSION['flash_message'])): 
                $message = $_SESSION['flash_message']['message'];
                $type = $_SESSION['flash_message']['type'];
                // 显示后清除闪存消息
                unset($_SESSION['flash_message']);
            ?>
                <div class="alert alert-<?php echo $type; ?>">
                    <?php echo $message; ?>
                    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none';">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php 
            // 输出页面内容
            echo $content; 
            ?>
        </main>
    </div>

    <?php 
    // 包含底部组件
    include_once __DIR__ . '/../components/footer.php'; 
    ?>

    <script>
        // 通用JavaScript功能
        document.addEventListener('DOMContentLoaded', function() {
            // 自动隐藏闪存消息
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>

    <?php if (isset($extra_js) && !empty($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html> 