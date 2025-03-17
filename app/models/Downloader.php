<?php
namespace app\models;

/**
 * 下载器模型
 * 
 * 处理与下载器相关的数据操作。
 */
class Downloader extends Model
{
    /**
     * 表名
     * 
     * @var string
     */
    protected $table = 'downloaders';
    
    /**
     * 获取所有下载器
     * 
     * @param string $type 下载器类型，可选
     * @return array 下载器列表
     */
    public function getAll($type = null)
    {
        if ($type) {
            return $this->where(['type' => $type]);
        }
        
        return $this->all();
    }
    
    /**
     * 获取下载器统计信息
     * @return array
     */
    public function getStats()
    {
        try {
            // 获取活跃的下载器
            $downloader = self::where('id', $this->id)->where('status', 1)->first();
            if (!$downloader) {
                return [
                    'error' => '下载器不存在或已禁用'
                ];
            }

            // 根据下载器类型获取统计信息
            if ($downloader->type === 'qbittorrent') {
                return $this->getQBittorrentStats($downloader->toArray());
            } else {
                return $this->getTransmissionStats($downloader->toArray());
            }
        } catch (Exception $e) {
            return [
                'error' => '获取统计信息失败: ' . $e->getMessage()
            ];
        }
    }
        $downloader = $this->find($id);
        
        if (!$downloader) {
            return [];
        }
        
        if ($downloader['type'] === 'qbittorrent') {
            return $this->getQBittorrentStats($downloader);
        } elseif ($downloader['type'] === 'transmission') {
            return $this->getTransmissionStats($downloader);
        }
        
        return [];
    }
    
    /**
     * 获取所有下载器的传输统计数据
     * 
     * @return array 传输统计数据
     */
    public function getTransferStats()
    {
        $downloaders = $this->all();
        $stats = [
            'labels' => [],
            'upload' => [],
            'download' => []
        ];
        
        foreach ($downloaders as $downloader) {
            $downloaderStats = $this->getStats($downloader['id']);
            
            if (!empty($downloaderStats)) {
                $stats['labels'][] = $downloader['name'];
                $stats['upload'][] = $downloaderStats['up_speed'] ?? 0;
                $stats['download'][] = $downloaderStats['dl_speed'] ?? 0;
            }
        }
        
        return $stats;
    }
    
    /**
     * 获取qBittorrent统计信息
     * 
     * @param array $downloader 下载器信息
     * @return array 统计信息
     */
    private function getQBittorrentStats($downloader)
    {
        try {
            require_once __DIR__ . '/../../includes/qbittorrent.php';
            
            $qb = new \QBittorrent($downloader['domain'], $downloader['username'], $downloader['password']);
            $maindata = $qb->getMainData();
            $torrents = $qb->getTorrents();
            
            $total_size = 0;
            foreach ($torrents as $torrent) {
                $total_size += $torrent['size'] ?? 0;
            }
            
            return [
                'up_speed' => $maindata['server_state']['up_info_speed'] ?? 0,
                'dl_speed' => $maindata['server_state']['dl_info_speed'] ?? 0,
                'free_space' => $maindata['server_state']['free_space_on_disk'] ?? 0,
                'total_size' => $total_size,
                'torrent_count' => count($torrents),
                'version' => $qb->getVersion(),
                'connected_peers' => $maindata['server_state']['dl_info_data'] ?? 0
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 获取Transmission统计信息
     * 
     * @param array $downloader 下载器信息
     * @return array 统计信息
     */
    private function getTransmissionStats($downloader)
    {
        try {
            require_once __DIR__ . '/../../includes/transmission.php';
            
            $transmission = new \Transmission($downloader['domain'], $downloader['username'], $downloader['password']);
            $stats = $transmission->getStats();
            $torrents = $transmission->getTorrents();
            
            $total_size = 0;
            $total_up_speed = 0;
            $total_dl_speed = 0;
            $connected_peers = 0;
            
            if (isset($torrents['torrents'])) {
                foreach ($torrents['torrents'] as $torrent) {
                    $total_size += $torrent['totalSize'] ?? 0;
                    $total_up_speed += $torrent['upspeed'] ?? 0;
                    $total_dl_speed += $torrent['dlspeed'] ?? 0;
                    $connected_peers += $torrent['peersConnected'] ?? 0;
                }
            }
            
            return [
                'up_speed' => $total_up_speed,
                'dl_speed' => $total_dl_speed,
                'free_space' => $stats['free_space'] ?? 0,
                'total_size' => $total_size,
                'torrent_count' => count($torrents['torrents'] ?? []),
                'version' => $stats['version'] ?? 'Unknown',
                'connected_peers' => $connected_peers
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
} 