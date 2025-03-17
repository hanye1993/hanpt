<?php
namespace app\core;

use PDO;
use Exception;

/**
 * 基础模型类
 */
class Model
{
    /**
     * 数据库连接
     */
    protected $db;
    
    /**
     * 表名
     */
    protected $table;
    
    /**
     * 查询条件
     */
    protected $where = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * 设置查询条件
     */
    public function where($conditions)
    {
        $this->where = array_merge($this->where, $conditions);
        return $this;
    }
    
    /**
     * 获取所有记录
     */
    public function get()
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($this->where)) {
            $whereClauses = [];
            foreach ($this->where as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取单条记录
     */
    public function first()
    {
        $result = $this->get();
        return $result ? $result[0] : null;
    }
    
    /**
     * 查找记录
     */
    public function find($id)
    {
        return $this->where(['id' => $id])->first();
    }
    
    /**
     * 创建记录
     */
    public function create($data)
    {
        $fields = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    /**
     * 更新记录
     */
    public function update($id, $data)
    {
        $setClauses = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $setClauses[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(',', $setClauses) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * 删除记录
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
} 