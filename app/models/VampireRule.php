<?php
namespace app\models;

/**
 * 吸血规则模型
 * 
 * 处理与吸血规则相关的数据操作。
 */
class VampireRule extends Model
{
    /**
     * 表名
     * 
     * @var string
     */
    protected $table = 'vampire_rules';
    
    /**
     * 获取所有规则
     * 
     * @param string $status 规则状态，可选
     * @return array 规则列表
     */
    public function getAll($status = null)
    {
        if ($status) {
            return $this->where(['status' => $status]);
        }
        
        return $this->all();
    }
    
    /**
     * 获取活跃规则
     * 
     * @return array 活跃规则列表
     */
    public function getActive()
    {
        return $this->where(['status' => 'active']);
    }
    
    /**
     * 获取规则统计信息
     * 
     * @return array 统计信息
     */
    public function getStats()
    {
        // 获取规则统计
        $total = $this->count();
        $active = $this->count(['status' => 'active']);
        $inactive = $this->count(['status' => 'inactive']);
        
        // 获取规则动作统计
        $block = $this->count(['action' => 'block']);
        $warn = $this->count(['action' => 'warn']);
        $log = $this->count(['action' => 'log']);
        
        // 获取最近的PeerBan日志
        $logs = $this->getRecentLogs();
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'block' => $block,
            'warn' => $warn,
            'log' => $log,
            'logs' => $logs
        ];
    }
    
    /**
     * 获取最近的PeerBan日志
     * 
     * @param int $limit 限制数量
     * @return array 日志列表
     */
    public function getRecentLogs($limit = 10)
    {
        $sql = "SELECT * FROM vampire_logs ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * 获取PeerBan统计数据（按日期分组）
     * 
     * @param int $days 天数
     * @return array 统计数据
     */
    public function getStatsByDate($days = 30)
    {
        $sql = "SELECT 
                    DATE(created_at) as date, 
                    COUNT(*) as count,
                    SUM(CASE WHEN action = 'block' THEN 1 ELSE 0 END) as block_count,
                    SUM(CASE WHEN action = 'warn' THEN 1 ELSE 0 END) as warn_count,
                    SUM(CASE WHEN action = 'log' THEN 1 ELSE 0 END) as log_count
                FROM 
                    vampire_logs 
                WHERE 
                    created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY 
                    DATE(created_at)
                ORDER BY 
                    date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        
        $result = $stmt->fetchAll();
        
        // 格式化数据为图表所需格式
        $stats = [
            'labels' => [],
            'block' => [],
            'warn' => [],
            'log' => [],
            'total' => []
        ];
        
        foreach ($result as $row) {
            $stats['labels'][] = $row['date'];
            $stats['block'][] = (int)$row['block_count'];
            $stats['warn'][] = (int)$row['warn_count'];
            $stats['log'][] = (int)$row['log_count'];
            $stats['total'][] = (int)$row['count'];
        }
        
        return $stats;
    }
} 