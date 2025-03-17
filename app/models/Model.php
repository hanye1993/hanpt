<?php
namespace app\models;

/**
 * 模型基类
 * 所有模型都应继承此类
 */
class Model
{
    /**
     * 数据库连接
     * @var \PDO
     */
    protected $db;
    
    /**
     * 表名
     * @var string
     */
    protected $table;
    
    /**
     * 主键
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->connect();
    }
    
    /**
     * 连接数据库
     * 
     * @return void
     */
    protected function connect()
    {
        // 加载数据库配置
        $config = require __DIR__ . '/../config/database.php';
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            $this->db = new \PDO($dsn, $config['username'], $config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (\PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    
    /**
     * 查询所有记录
     * 
     * @param array $columns 要查询的列
     * @return array
     */
    public function all($columns = ['*'])
    {
        $columns = $this->prepareColumns($columns);
        $sql = "SELECT {$columns} FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * 根据ID查询记录
     * 
     * @param int $id 记录ID
     * @param array $columns 要查询的列
     * @return array|null
     */
    public function find($id, $columns = ['*'])
    {
        $columns = $this->prepareColumns($columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }
    
    /**
     * 根据条件查询记录
     * 
     * @param array $conditions 查询条件
     * @param array $columns 要查询的列
     * @return array
     */
    public function where($conditions, $columns = ['*'])
    {
        $columns = $this->prepareColumns($columns);
        
        $whereClause = [];
        $params = [];
        
        foreach ($conditions as $column => $value) {
            $whereClause[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }
        
        $whereClause = implode(' AND ', $whereClause);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * 根据条件查询单条记录
     * 
     * @param array $conditions 查询条件
     * @param array $columns 要查询的列
     * @return array|null
     */
    public function findOne($conditions, $columns = ['*'])
    {
        $columns = $this->prepareColumns($columns);
        
        $whereClause = [];
        $params = [];
        
        foreach ($conditions as $column => $value) {
            $whereClause[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }
        
        $whereClause = implode(' AND ', $whereClause);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$whereClause} LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }
    
    /**
     * 插入记录
     * 
     * @param array $data 要插入的数据
     * @return int 插入的记录ID
     */
    public function insert($data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->db->prepare($sql);
        foreach ($data as $column => $value) {
            $stmt->bindValue(":{$column}", $value);
        }
        $stmt->execute();
        
        return $this->db->lastInsertId();
    }
    
    /**
     * 更新记录
     * 
     * @param int $id 记录ID
     * @param array $data 要更新的数据
     * @return bool
     */
    public function update($id, $data)
    {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        foreach ($data as $column => $value) {
            $stmt->bindValue(":{$column}", $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * 根据条件更新记录
     * 
     * @param array $conditions 更新条件
     * @param array $data 要更新的数据
     * @return bool
     */
    public function updateWhere($conditions, $data)
    {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :set_{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $whereClause = [];
        foreach (array_keys($conditions) as $column) {
            $whereClause[] = "{$column} = :where_{$column}";
        }
        $whereClause = implode(' AND ', $whereClause);
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        foreach ($data as $column => $value) {
            $stmt->bindValue(":set_{$column}", $value);
        }
        foreach ($conditions as $column => $value) {
            $stmt->bindValue(":where_{$column}", $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * 删除记录
     * 
     * @param int $id 记录ID
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * 根据条件删除记录
     * 
     * @param array $conditions 删除条件
     * @return bool
     */
    public function deleteWhere($conditions)
    {
        $whereClause = [];
        $params = [];
        
        foreach ($conditions as $column => $value) {
            $whereClause[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }
        
        $whereClause = implode(' AND ', $whereClause);
        $sql = "DELETE FROM {$this->table} WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * 开始事务
     * 
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }
    
    /**
     * 提交事务
     * 
     * @return bool
     */
    public function commit()
    {
        return $this->db->commit();
    }
    
    /**
     * 回滚事务
     * 
     * @return bool
     */
    public function rollback()
    {
        return $this->db->rollBack();
    }
    
    /**
     * 执行原始SQL查询
     * 
     * @param string $sql SQL语句
     * @param array $params 绑定参数
     * @return \PDOStatement
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }
    
    /**
     * 准备列名
     * 
     * @param array $columns 列名数组
     * @return string
     */
    protected function prepareColumns($columns)
    {
        if (in_array('*', $columns)) {
            return '*';
        }
        
        return implode(', ', $columns);
    }
    
    /**
     * 获取表名
     * 
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
    
    /**
     * 设置表名
     * 
     * @param string $table 表名
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }
    
    /**
     * 计算表中的记录数量
     * 
     * @param array $conditions 可选的查询条件
     * @return int 记录数量
     */
    public function count($conditions = [])
    {
        if (empty($conditions)) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            $whereClause = [];
            $params = [];
            
            foreach ($conditions as $column => $value) {
                $whereClause[] = "{$column} = :{$column}";
                $params[":{$column}"] = $value;
            }
            
            $whereClause = implode(' AND ', $whereClause);
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$whereClause}";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
        }
        
        $result = $stmt->fetch();
        return (int) $result['count'];
    }
} 