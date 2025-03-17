<?php
namespace App\Services;

require_once __DIR__ . '/../../includes/qbittorrent.php';
require_once __DIR__ . '/../../includes/transmission.php';
require_once __DIR__ . '/../../includes/db.php';

/**
 * 下载器服务类
 * 整合qBittorrent和Transmission的功能
 */
class DownloaderService {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance();
    }
    
    /**
     * 获取下载器信息
     * 
     * @param int $id 下载器ID
     * @return array 下载器信息
     * @throws \Exception 如果下载器不存在
     */
    public function getDownloader($id) {
        $downloader = $this->db->query("SELECT * FROM downloaders WHERE id = ?", [$id])->fetch();
        if (!$downloader) {
            throw new \Exception('下载器不存在');
        }
        return $downloader;
    }
    
    /**
     * 获取所有下载器
     * 
     * @param string $type 下载器类型，可选
     * @return array 下载器列表
     */
    public function getAllDownloaders($type = null) {
        $sql = "SELECT * FROM downloaders";
        $params = [];
        
        if ($type) {
            $sql .= " WHERE type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY id ASC";
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * 获取下载器统计信息
     * 
     * @param int $id 下载器ID
     * @return array 统计信息
     */
    public function getStats($id) {
        $downloader = $this->getDownloader($id);
        
        if ($downloader['type'] === 'qbittorrent') {
            return $this->getQBittorrentStats($downloader);
        } else if ($downloader['type'] === 'transmission') {
            return $this->getTransmissionStats($downloader);
        } else {
            throw new \Exception('不支持的下载器类型');
        }
    }
    
    /**
     * 获取qBittorrent统计信息
     * 
     * @param array $downloader 下载器信息
     * @return array 统计信息
     */
    private function getQBittorrentStats($downloader) {
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
    }
    
    /**
     * 获取Transmission统计信息
     * 
     * @param array $downloader 下载器信息
     * @return array 统计信息
     */
    private function getTransmissionStats($downloader) {
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
    }
}