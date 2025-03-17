<?php
namespace app\models;

/**
 * 站点模型
 * 
 * 处理与站点相关的数据操作。
 */
class Site extends Model
{
    /**
     * 表名
     * 
     * @var string
     */
    protected $table = 'sites';
    
    /**
     * 获取所有站点
     * 
     * @param string $status 站点状态，可选
     * @return array 站点列表
     */
    public function getAll($status = null)
    {
        if ($status) {
            return $this->where(['status' => $status]);
        }
        
        return $this->all();
    }
    
    /**
     * 获取活跃站点
     * 
     * @return array 活跃站点列表
     */
    public function getActive()
    {
        return $this->where(['status' => 'active']);
    }
    
    /**
     * 获取站点统计信息
     * 
     * @return array 统计信息
     */
    public function getStats()
    {
        $total = $this->count();
        $active = $this->count(['status' => 'active']);
        $inactive = $this->count(['status' => 'inactive']);
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive
        ];
    }
} 