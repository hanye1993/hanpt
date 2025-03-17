<?php
if (!isset($current_page)) {
    $current_page = '';
}
if (!isset($theme_mode)) {
    $theme_mode = $_COOKIE['theme_mode'] ?? 'light';
}
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="<?php echo htmlspecialchars($theme_mode); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>ThinkPHP应用</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        /* 基础变量 */
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --dark-color: #34495e;
            --light-color: #f5f5f5;
            --border-color: #ddd;
            --text-color: #333;
            --text-muted: #777;
            --box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            --sidebar-width: 240px;
            --header-height: 60px;
            --body-bg: #f8f9fa;
            --card-bg: #fff;
        }
        
        [data-theme="dark"] {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #2c3e50;
            --border-color: #3d4852;
            --text-color: #f5f5f5;
            --text-muted: #b2bec3;
            --box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            --body-bg: #1a202c;
            --card-bg: #2d3748;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--body-bg);
            min-height: 100vh;
        }
        
        /* 顶部栏样式优化 */
        .header {
            background-color: var(--card-bg);
            box-shadow: var(--box-shadow);
            height: var(--header-height);
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 30px;
            transition: all 0.3s ease;
        }
        
        .header-content {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            width: 100%;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: auto;
        }
        
        .theme-btn {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 20px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .theme-btn:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }
        
        .username {
            font-weight: 500;
            font-size: 15px;
            color: var(--text-color);
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .avatar:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .avatar img {
            width: 100%;
            height: 100%;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            width: 200px;
            z-index: 1100;
            display: none;
            margin-top: 10px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        
        .user-dropdown.active {
            display: block;
            animation: dropdownFade 0.2s ease;
        }
        
        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .dropdown-item i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        
        /* 主内容区域 */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: calc(var(--header-height) + 20px) 30px 30px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        /* 响应式样式 */
        @media (max-width: 992px) {
            .sidebar {
                left: -var(--sidebar-width);
            }
            
            .header {
                left: 0;
            }
            
            .main-content,
            .footer {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            body.sidebar-open .sidebar {
                left: 0;
            }
            
            body.sidebar-open .header {
                left: var(--sidebar-width);
            }
            
            body.sidebar-open .main-content,
            body.sidebar-open .footer {
                margin-left: var(--sidebar-width);
            }
        }
    </style>
</head>
<body>
    <!-- 顶部栏 -->
    <header class="header">
        <div class="header-content">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="header-actions">
                <span class="username"><?php echo htmlspecialchars($username ?? ''); ?></span>
                
                <button class="theme-btn" id="themeToggleBtn">
                    <i class="fas <?php echo $theme_mode === 'dark' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                </button>
                
                <div class="user-info">
                    <div class="avatar" id="avatarToggle">
                        <?php if (!empty($avatar)): ?>
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="头像">
                        <?php else: ?>
                            <?php echo htmlspecialchars(mb_substr($username ?? 'U', 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-dropdown" id="userDropdown">
                        <a href="/profile.php" class="dropdown-item">
                            <i class="fas fa-user-circle"></i>
                            <span>个人信息</span>
                        </a>
                        <a href="/logs.php" class="dropdown-item">
                            <i class="fas fa-clipboard-list"></i>
                            <span>查看日志</span>
                        </a>
                        <a href="/logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>退出登录</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 主内容区域开始 -->
    <div class="main-content">

    <script>
        // 用户下拉菜单
        document.getElementById('avatarToggle').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('userDropdown').classList.toggle('active');
        });
        
        // 点击外部关闭下拉菜单
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const avatar = document.getElementById('avatarToggle');
            
            if (!avatar.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
        
        // 移动端侧边栏切换
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-open');
        });
        
        // 主题切换
        document.getElementById('themeToggleBtn').addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            
            const expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + 30);
            document.cookie = `theme_mode=${newTheme};expires=${expiryDate.toUTCString()};path=/`;
            
            const iconElement = this.querySelector('i');
            if (newTheme === 'dark') {
                iconElement.classList.replace('fa-moon', 'fa-sun');
            } else {
                iconElement.classList.replace('fa-sun', 'fa-moon');
            }
        });
    </script>
</body>
</html> 