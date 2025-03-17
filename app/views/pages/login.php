<?php
// 登录页面

// 如果用户已登录，重定向到首页
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

// 设置页面标题
$page_title = $page_title ?? '登录';
$current_page = '';

// 获取闪存消息
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// 开始输出缓冲
ob_start();
?>

<!DOCTYPE html>
<html lang="zh-CN" data-theme="<?php echo $theme_mode ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - ThinkPHP应用</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --text-color: #333;
            --text-muted: #777;
            --border-color: #ddd;
            --card-bg: #fff;
            --body-bg: #f5f8fa;
            --box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        [data-theme="dark"] {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --text-color: #f5f5f5;
            --text-muted: #aaa;
            --border-color: #444;
            --card-bg: #333;
            --body-bg: #222;
            --box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--body-bg);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .auth-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .auth-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 400px;
            padding: 30px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .auth-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .auth-subtitle {
            color: var(--text-muted);
            font-size: 16px;
        }
        
        .auth-form {
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--card-bg);
            color: var(--text-color);
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .form-check-input {
            margin-right: 10px;
        }
        
        .form-check-label {
            color: var(--text-muted);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            color: var(--text-muted);
        }
        
        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.2);
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            color: var(--primary-color);
            transform: rotate(30deg);
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="theme-toggle">
        <i class="fas <?php echo ($theme_mode ?? 'light') === 'dark' ? 'fa-sun' : 'fa-moon'; ?>"></i>
    </button>
    
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">ThinkPHP应用</div>
                <h1 class="auth-title">欢迎回来</h1>
                <p class="auth-subtitle">请登录您的账号</p>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <form class="auth-form" action="/user/login" method="post">
                <div class="form-group">
                    <label for="username" class="form-label">用户名或邮箱</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="请输入用户名或邮箱" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">密码</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="请输入密码" required>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember" class="form-check-input">
                    <label for="remember" class="form-check-label">记住我</label>
                </div>
                
                <button type="submit" class="btn">登录</button>
            </form>
            
            <div class="auth-footer">
                <p>还没有账号？ <a href="/user/register">立即注册</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // 主题切换
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            this.innerHTML = newTheme === 'dark' 
                ? '<i class="fas fa-sun"></i>' 
                : '<i class="fas fa-moon"></i>';
            
            // 保存主题设置到cookie
            document.cookie = `theme_mode=${newTheme}; path=/; max-age=${60*60*24*365}`;
        });
    </script>
</body>
</html>

<?php
// 获取输出缓冲内容，但不使用主布局
$content = ob_get_clean();

// 直接输出内容，不包含主布局
echo $content;
?> 