<?php
namespace app\models;

/**
 * 种子模型
 * 
 * 处理与种子相关的数据操作。
 */
class Torrent extends Model
{
    /**
     * 表名
     * 
     * @var string
     */
    protected $table = 'torrents';
    
    /**
     * 获取所有种子
     * 
     * @param string $status 种子状态，可选
     * @return array 种子列表
     */
    public function getAll($status = null)
    {
        if ($status) {
            return $this->where(['status' => $status]);
        }
        
        return $this->all();
    }
    
    /**
     * 获取种子总大小
     * 
     * @return int 总大小（字节）
     */
    public function getTotalSize()
    {
        $sql = "SELECT SUM(file_size) as total_size FROM {$this->table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total_size'] ?? 0;
    }
    
    /**
     * 获取种子统计信息
     * 
     * @return array 统计信息
     */
    public function getStats()
    {
        $total = $this->count();
        $downloading = $this->count(['status' => 'downloading']);
        $completed = $this->count(['status' => 'completed']);
        $failed = $this->count(['status' => 'failed']);
        $pending = $this->count(['status' => 'pending']);
        $paused = $this->count(['status' => 'paused']);
        
        return [
            'total' => $total,
            'downloading' => $downloading,
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $pending,
            'paused' => $paused
        ];
    }
    
    /**
     * 格式化文件大小
     * 
     * @param int $bytes 字节数
     * @param int $precision 精度
     * @return string 格式化后的大小
     */
    public function formatSize($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
} 