<!-- 侧边栏 -->
<div class="sidebar">
    <div class="sidebar-header">
        <a href="/index.php" class="app-title">ThinkPHP应用</a>
    </div>
    <div class="sidebar-menu">
        <a href="/index.php" class="menu-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-tachometer-alt"></i></span>
            <span>首页</span>
        </a>
        <a href="/downloader.php" class="menu-item <?php echo $current_page === 'downloader' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-download"></i></span>
            <span>下载器</span>
        </a>
        <a href="/vampire.php" class="menu-item <?php echo $current_page === 'vampire' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-ban"></i></span>
            <span>吸血ban</span>
        </a>
        <a href="/sites.php" class="menu-item <?php echo $current_page === 'sites' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-globe"></i></span>
            <span>站点</span>
        </a>
        <a href="/plugins.php" class="menu-item <?php echo $current_page === 'plugins' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-puzzle-piece"></i></span>
            <span>插件</span>
        </a>
        <a href="/settings.php" class="menu-item <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
            <span class="menu-icon"><i class="fas fa-cog"></i></span>
            <span>设置</span>
        </a>
    </div>
</div>

<style>
/* 侧边栏样式 */
.sidebar {
    position: fixed;
    width: var(--sidebar-width);
    height: 100%;
    left: 0;
    top: 0;
    background-color: var(--dark-color);
    color: white;
    z-index: 1001;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 0 20px;
    height: var(--header-height);
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.app-title {
    font-size: 22px;
    font-weight: bold;
    color: white;
    text-decoration: none;
}

.sidebar-menu {
    padding: 20px 0;
}

.menu-item {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
}

.menu-item:hover, 
.menu-item.active {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    padding-left: 25px;
}

.menu-icon {
    margin-right: 12px;
    font-size: 18px;
    width: 25px;
    text-align: center;
}

.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: var(--text-color);
    font-size: 24px;
    cursor: pointer;
}
</style> 