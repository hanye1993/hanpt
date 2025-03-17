<?php
require_once 'includes/db.php';

// 显示错误信息
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html lang='zh-CN'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>更新吸血鬼功能数据库</title>
    <link rel='stylesheet' href='assets/css/bootstrap.min.css'>
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .log { margin-bottom: 10px; padding: 10px; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #e2e3e5; color: #383d41; }
        .warning { background-color: #fff3cd; color: #856404; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        .debug-info { margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>更新吸血鬼功能数据库</h1>";

try {
    // 获取数据库连接
    $db = Database::getInstance();
    
    // 检查数据库连接
    echo "<div class='log info'>检查数据库连接...</div>";
    try {
        $db->query("SELECT 1");
        echo "<div class='log success'>数据库连接成功</div>";
    } catch (Exception $e) {
        echo "<div class='log error'>数据库连接失败: " . $e->getMessage() . "</div>";
        throw $e;
    }
    
    // 读取SQL文件内容
    echo "<div class='log info'>读取SQL文件...</div>";
    $sqlFile = 'database/update_vampire.sql';
    if (!file_exists($sqlFile)) {
        echo "<div class='log error'>SQL文件不存在: " . $sqlFile . "</div>";
        throw new Exception("SQL文件不存在: " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    if (empty($sql)) {
        echo "<div class='log error'>SQL文件为空</div>";
        throw new Exception("SQL文件为空");
    }
    
    echo "<div class='log success'>SQL文件读取成功，大小: " . strlen($sql) . " 字节</div>";
    
    // 使用正则表达式分割SQL语句，保留分号
    $pattern = '/;\s*$/m';
    $queries = preg_split($pattern, $sql, -1, PREG_SPLIT_NO_EMPTY);
    
    echo "<div class='log info'>共找到 " . count($queries) . " 条SQL语句</div>";
    
    // 执行每个SQL语句
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($queries as $index => $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $db->query($query);
                echo "<div class='log success'>执行成功 (" . ($index + 1) . "/" . count($queries) . "): <pre>" . htmlspecialchars(substr($query, 0, 100)) . (strlen($query) > 100 ? "..." : "") . "</pre></div>";
                $successCount++;
            } catch (Exception $e) {
                echo "<div class='log error'>执行失败 (" . ($index + 1) . "/" . count($queries) . "): <pre>" . htmlspecialchars($query) . "</pre>错误信息: " . $e->getMessage() . "</div>";
                $errorCount++;
                
                // 尝试检查错误原因
                if (strpos($e->getMessage(), "Column 'unban_time' cannot be null") !== false) {
                    echo "<div class='log warning'>提示: 'unban_time' 列不能为 NULL，尝试修改默认值...</div>";
                    try {
                        $db->query("ALTER TABLE peer_bans MODIFY COLUMN unban_time TIMESTAMP NULL DEFAULT NULL");
                        echo "<div class='log success'>修改 'unban_time' 列默认值成功</div>";
                    } catch (Exception $e2) {
                        echo "<div class='log error'>修改 'unban_time' 列默认值失败: " . $e2->getMessage() . "</div>";
                    }
                }
                
                if (strpos($e->getMessage(), "Unknown column 'ban_time'") !== false) {
                    echo "<div class='log warning'>提示: 'ban_time' 列不存在，尝试添加该列...</div>";
                    try {
                        $db->query("ALTER TABLE peer_bans ADD COLUMN ban_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '封禁时间' AFTER ip");
                        echo "<div class='log success'>添加 'ban_time' 列成功</div>";
                    } catch (Exception $e2) {
                        echo "<div class='log error'>添加 'ban_time' 列失败: " . $e2->getMessage() . "</div>";
                    }
                }
                
                if (strpos($e->getMessage(), "Unknown column 'unban_time'") !== false) {
                    echo "<div class='log warning'>提示: 'unban_time' 列不存在，尝试添加该列...</div>";
                    try {
                        $db->query("ALTER TABLE peer_bans ADD COLUMN unban_time TIMESTAMP NULL DEFAULT NULL COMMENT '解封时间' AFTER ban_time");
                        echo "<div class='log success'>添加 'unban_time' 列成功</div>";
                    } catch (Exception $e2) {
                        echo "<div class='log error'>添加 'unban_time' 列失败: " . $e2->getMessage() . "</div>";
                    }
                }
                
                if (strpos($e->getMessage(), "Unknown column 'auto_unban_time'") !== false) {
                    echo "<div class='log warning'>提示: 'auto_unban_time' 列不存在，尝试添加该列...</div>";
                    try {
                        $db->query("ALTER TABLE peer_bans ADD COLUMN auto_unban_time TIMESTAMP NULL DEFAULT NULL COMMENT '自动解封时间' AFTER unban_time");
                        echo "<div class='log success'>添加 'auto_unban_time' 列成功</div>";
                    } catch (Exception $e2) {
                        echo "<div class='log error'>添加 'auto_unban_time' 列失败: " . $e2->getMessage() . "</div>";
                    }
                }
            }
        }
    }
    
    echo "<div class='log info'>SQL执行统计: 成功 " . $successCount . " 条，失败 " . $errorCount . " 条</div>";
    
    // 检查表结构
    echo "<div class='log info'>检查表结构...</div>";
    
    // 检查 peer_checks 表
    try {
        $result = $db->query("SHOW TABLES LIKE 'peer_checks'")->rowCount();
        if ($result > 0) {
            echo "<div class='log success'>peer_checks 表存在</div>";
        } else {
            echo "<div class='log error'>peer_checks 表不存在</div>";
        }
    } catch (Exception $e) {
        echo "<div class='log error'>检查 peer_checks 表失败: " . $e->getMessage() . "</div>";
    }
    
    // 检查 peer_bans 表
    try {
        $result = $db->query("SHOW TABLES LIKE 'peer_bans'")->rowCount();
        if ($result > 0) {
            echo "<div class='log success'>peer_bans 表存在</div>";
            
            // 检查 ban_time 列
            try {
                $result = $db->query("SHOW COLUMNS FROM peer_bans LIKE 'ban_time'")->rowCount();
                if ($result > 0) {
                    echo "<div class='log success'>peer_bans 表中的 ban_time 列存在</div>";
                } else {
                    echo "<div class='log error'>peer_bans 表中的 ban_time 列不存在</div>";
                    
                    // 尝试添加 ban_time 列
                    try {
                        $db->query("ALTER TABLE peer_bans ADD COLUMN ban_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '封禁时间' AFTER ip");
                        echo "<div class='log success'>添加 ban_time 列成功</div>";
                    } catch (Exception $e2) {
                        echo "<div class='log error'>添加 ban_time 列失败: " . $e2->getMessage() . "</div>";
                    }
                }
            } catch (Exception $e) {
                echo "<div class='log error'>检查 ban_time 列失败: " . $e->getMessage() . "</div>";
            }
            
            // 检查 unban_time 列
            try {
                $result = $db->query("SHOW COLUMNS FROM peer_bans LIKE 'unban_time'")->rowCount();
                if ($result > 0) {
                    echo "<div class='log success'>peer_bans 表中的 unban_time 列存在</div>";
                } else {
                    echo "<div class='log error'>peer_bans 表中的 unban_time 列不存在</div>";
                    
                    // 尝试添加 unban_time 列
                    try {
                        $db->query("ALTER TABLE peer_bans ADD COLUMN unban_time TIMESTAMP NULL DEFAULT NULL COMMENT '解封时间' AFTER ban_time");
                        echo "<div class='log success'>添加 unban_time 列成功</div>";
                    } catch (Exception $e2) {
                        echo "<div class='log error'>添加 unban_time 列失败: " . $e2->getMessage() . "</div>";
                    }
                }
            } catch (Exception $e) {
                echo "<div class='log error'>检查 unban_time 列失败: " . $e->getMessage() . "</div>";
            }
            
            // 检查 auto_unban_time 列
            try {
                $result = $db->query("SHOW COLUMNS FROM peer_bans LIKE 'auto_unban_time'")->rowCount();
                if ($result > 0) {
                    echo "<div class='log success'>peer_bans 表中的 auto_unban_time 列存在</div>";
                } else {
                    echo "<div class='log error'>peer_bans 表中的 auto_unban_time 列不存在</div>";
                    
                    // 尝试添加 auto_unban_time 列
                    try {
                        $db->query("ALTER TABLE peer_bans ADD COLUMN auto_unban_time TIMESTAMP NULL DEFAULT NULL COMMENT '自动解封时间' AFTER unban_time");
                        echo "<div class='log success'>添加 auto_unban_time 列成功</div>";
                    } catch (Exception $e2) {
                        echo "<div class='log error'>添加 auto_unban_time 列失败: " . $e2->getMessage() . "</div>";
                    }
                }
            } catch (Exception $e) {
                echo "<div class='log error'>检查 auto_unban_time 列失败: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='log error'>peer_bans 表不存在</div>";
            
            // 尝试创建 peer_bans 表
            try {
                $db->query("
                    CREATE TABLE IF NOT EXISTS `peer_bans` (
                      `id` INT PRIMARY KEY AUTO_INCREMENT,
                      `ip` VARCHAR(45) NOT NULL COMMENT 'Peer IP',
                      `ban_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '封禁时间',
                      `unban_time` TIMESTAMP NULL DEFAULT NULL COMMENT '解封时间',
                      `auto_unban_time` TIMESTAMP NULL DEFAULT NULL COMMENT '自动解封时间',
                      `reason` VARCHAR(255) DEFAULT '手动封禁' COMMENT '封禁原因',
                      `downloader_id` INT NULL COMMENT '下载器ID',
                      KEY `ip_index` (`ip`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
                echo "<div class='log success'>创建 peer_bans 表成功</div>";
            } catch (Exception $e2) {
                echo "<div class='log error'>创建 peer_bans 表失败: " . $e2->getMessage() . "</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='log error'>检查 peer_bans 表失败: " . $e->getMessage() . "</div>";
    }
    
    // 检查 torrents 表
    try {
        $result = $db->query("SHOW TABLES LIKE 'torrents'")->rowCount();
        if ($result > 0) {
            echo "<div class='log success'>torrents 表存在</div>";
        } else {
            echo "<div class='log error'>torrents 表不存在</div>";
        }
    } catch (Exception $e) {
        echo "<div class='log error'>检查 torrents 表失败: " . $e->getMessage() . "</div>";
    }
    
    if ($errorCount > 0) {
        echo "<div class='log warning'><strong>数据库更新完成，但有 " . $errorCount . " 条SQL语句执行失败。</strong></div>";
    } else {
        echo "<div class='log success'><strong>数据库更新成功！</strong></div>";
    }
    
    echo "<p><a href='vampire.php' class='btn btn-primary'>返回吸血鬼管理页面</a></p>";
} catch (Exception $e) {
    echo "<div class='log error'><strong>数据库更新失败:</strong> " . $e->getMessage() . "</div>";
    echo "<p><a href='index.php' class='btn btn-secondary'>返回首页</a></p>";
}

// 显示调试信息
echo "<div class='debug-info'>
    <h3>调试信息</h3>
    <p>PHP版本: " . phpversion() . "</p>
    <p>当前时间: " . date('Y-m-d H:i:s') . "</p>
    <p>服务器信息: " . $_SERVER['SERVER_SOFTWARE'] . "</p>
</div>";

echo "</div></body></html>"; 