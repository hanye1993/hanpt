<?php
/**
 * 数据库配置文件
 * 
 * 包含数据库连接信息和相关设置
 */

return [
    // 数据库连接信息
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'thinkphp_app',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    
    // 数据库备份设置
    'backup' => [
        'path' => __DIR__ . '/../../storage/backups',
        'filename_format' => 'Y-m-d_H-i-s',
    ],
]; 