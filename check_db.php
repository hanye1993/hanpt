<?php
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    $downloaders = $db->query("SELECT * FROM downloaders WHERE status = 1")->fetchAll();
    echo "Active downloaders:\n";
    print_r($downloaders);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 