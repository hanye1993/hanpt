<?php
try {
    // 创建PDO连接
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
    ]);

    // 读取SQL文件
    $sql = file_get_contents(__DIR__ . '/install.sql');

    // 分割SQL语句
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    // 逐条执行SQL语句
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "执行成功: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "执行失败: " . substr($statement, 0, 50) . "...\n";
                echo "错误信息: " . $e->getMessage() . "\n";
                // 继续执行下一条语句
                continue;
            }
        }
    }

    echo "\n数据库和表创建成功！\n";
} catch (PDOException $e) {
    echo "数据库连接错误：" . $e->getMessage() . "\n";
    exit(1);
}
?> 