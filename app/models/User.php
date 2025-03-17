<?php
namespace app\models;

/**
 * 用户模型
 * 
 * 处理与用户相关的数据操作。
 */
class User extends Model
{
    /**
     * 表名
     * 
     * @var string
     */
    protected $table = 'users';
    
    /**
     * 通过用户名查找用户
     * 
     * @param string $username 用户名
     * @return array|null
     */
    public function findByUsername($username)
    {
        return $this->findOne(['username' => $username]);
    }
    
    /**
     * 通过邮箱查找用户
     * 
     * @param string $email 邮箱
     * @return array|null
     */
    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }
    
    /**
     * 创建新用户
     * 
     * @param string $username 用户名
     * @param string $email 邮箱
     * @param string $password 密码
     * @return int 用户ID
     */
    public function create($username, $email, $password)
    {
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'avatar' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->insert($data);
    }
    
    /**
     * 更新用户信息
     * 
     * @param int $id 用户ID
     * @param array $data 更新数据
     * @return int 影响行数
     */
    public function updateUser($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }
    
    /**
     * 更新用户密码
     * 
     * @param int $id 用户ID
     * @param string $password 新密码
     * @return int 影响行数
     */
    public function updatePassword($id, $password)
    {
        $data = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($id, $data);
    }
    
    /**
     * 更新用户头像
     * 
     * @param int $id 用户ID
     * @param string $avatar 头像路径
     * @return int 影响行数
     */
    public function updateAvatar($id, $avatar)
    {
        $data = [
            'avatar' => $avatar,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($id, $data);
    }
    
    /**
     * 验证用户密码
     * 
     * @param string $password 明文密码
     * @param string $hash 密码哈希
     * @return bool
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    /**
     * 用户登录
     * 
     * @param string $username 用户名或邮箱
     * @param string $password 密码
     * @return array|null 用户信息
     */
    public function login($username, $password)
    {
        // 尝试通过用户名查找
        $user = $this->findByUsername($username);
        
        // 如果未找到，尝试通过邮箱查找
        if (!$user) {
            $user = $this->findByEmail($username);
        }
        
        // 如果找到用户并且密码正确
        if ($user && $this->verifyPassword($password, $user['password'])) {
            return $user;
        }
        
        return null;
    }
    
    /**
     * 记录用户操作日志
     * 
     * @param int $userId 用户ID
     * @param string $action 操作
     * @param string $details 详情
     * @return int 日志ID
     */
    public function logAction($userId, $action, $details = '')
    {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO user_logs (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['action'],
            $data['details'],
            $data['ip_address'],
            $data['user_agent'],
            $data['created_at']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * 获取用户操作日志
     * 
     * @param int $userId 用户ID
     * @param int $limit 限制
     * @param int $offset 偏移
     * @return array
     */
    public function getUserLogs($userId, $limit = 10, $offset = 0)
    {
        $sql = "SELECT * FROM user_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 计数记录
     * 
     * @param array $conditions 查询条件
     * @return int 记录数
     */
    public function count($conditions = [])
    {
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $column => $value) {
                $whereParts[] = "{$column} = ?";
                $params[] = $value;
            }
            $whereClause = ' WHERE ' . implode(' AND ', $whereParts);
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table}{$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }
} 