<?php
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        require_once __DIR__ . '/../config/database.php';
        
        try {
            $this->pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // 记录错误日志
            error_log("SQL错误: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            '`' . implode('`, `', $fields) . '`',
            implode(', ', $placeholders)
        );
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("插入错误: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "`$key` = ?";
            $values[] = $value;
        }
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $fields),
            $where
        );
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge($values, $whereParams));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("更新错误: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("删除错误: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollBack() {
        return $this->pdo->rollBack();
    }
    
    /**
     * 获取最后插入行的ID
     * @return string 最后插入的ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}
