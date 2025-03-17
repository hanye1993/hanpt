<?php
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    
    // 检查表是否存在
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Existing tables:\n";
    print_r($tables);
    
    if (in_array('downloaders', $tables)) {
        echo "\nDownloaders table structure:\n";
        $columns = $db->query("SHOW COLUMNS FROM downloaders")->fetchAll();
        print_r($columns);
        
        echo "\nActive downloaders:\n";
        $downloaders = $db->query("SELECT * FROM downloaders WHERE status = 1")->fetchAll();
        print_r($downloaders);
    } else {
        echo "\nDownloaders table does not exist!\n";
        
        // 创建表
        $db->query("
            CREATE TABLE IF NOT EXISTS downloaders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                domain VARCHAR(255) NOT NULL,
                username VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "Created downloaders table.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 