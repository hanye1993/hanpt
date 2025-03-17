<?php
define('APP_ROOT', __DIR__);

// 检查是否已安装
if (file_exists(APP_ROOT . '/config/database.php')) {
    header('Location: /');
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // 检查目录权限
        $writableDirs = [
            '/config',
            '/storage',
            '/storage/uploads',
            '/storage/uploads/avatars',
            '/storage/logs'
        ];
        
        $allWritable = true;
        foreach ($writableDirs as $dir) {
            $path = APP_ROOT . $dir;
            if (!is_dir($path)) {
                @mkdir($path, 0777, true);
            }
            if (!is_writable($path)) {
                $allWritable = false;
                $error = "目录 {$dir} 不可写,请设置权限为777";
                break;
            }
        }
        
        if ($allWritable) {
            header('Location: /install.php?step=2');
            exit;
        }
    } elseif ($step === 2) {
        // 验证数据库连接
        $host = $_POST['db_host'] ?? '';
        $port = $_POST['db_port'] ?? '3306';
        $database = $_POST['db_name'] ?? '';
        $username = $_POST['db_user'] ?? '';
        $password = $_POST['db_pass'] ?? '';
        $charset = $_POST['db_charset'] ?? 'utf8mb4';
        
        try {
            $dsn = "mysql:host={$host};port={$port};charset={$charset}";
            $db = new PDO($dsn, $username, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 创建数据库
            $db->exec("CREATE DATABASE IF NOT EXISTS `{$database}` DEFAULT CHARACTER SET {$charset}");
            $db->exec("USE `{$database}`");
            
            // 导入SQL文件
            $sql = file_get_contents(APP_ROOT . '/install/install.sql');
            $db->exec($sql);
            
            // 生成配置文件
            $config = <<<EOT
<?php
return [
    'host' => '{$host}',
    'port' => '{$port}',
    'database' => '{$database}',
    'username' => '{$username}',
    'password' => '{$password}',
    'charset' => '{$charset}'
];
EOT;
            
            file_put_contents(APP_ROOT . '/config/database.php', $config);
            
            $success = '安装成功!';
            header('refresh:3;url=/');
        } catch (PDOException $e) {
            $error = '数据库连接失败: ' . $e->getMessage();
        }
    }
}

// 显示安装页面
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>安装向导</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.2.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">安装向导</h3>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($step === 1): ?>
                <form method="post">
                    <h5 class="mb-3">步骤1: 环境检查</h5>
                    <div class="mb-3">
                        <p>正在检查目录权限...</p>
                        <button type="submit" class="btn btn-primary">下一步</button>
                    </div>
                </form>
                <?php elseif ($step === 2): ?>
                <form method="post">
                    <h5 class="mb-3">步骤2: 数据库配置</h5>
                    <div class="mb-3">
                        <label class="form-label">数据库主机</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">端口</label>
                        <input type="text" name="db_port" class="form-control" value="3306" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">数据库名</label>
                        <input type="text" name="db_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">用户名</label>
                        <input type="text" name="db_user" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">密码</label>
                        <input type="password" name="db_pass" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">字符集</label>
                        <input type="text" name="db_charset" class="form-control" value="utf8mb4" required>
                    </div>
                    <button type="submit" class="btn btn-primary">开始安装</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
