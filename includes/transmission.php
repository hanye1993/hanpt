<?php
/**
 * Transmission API 客户端
 */
class Transmission {
    private $url;
    private $username;
    private $password;
    private $sessionId = '';
    
    /**
     * 构造函数
     * 
     * @param string $url Transmission RPC URL
     * @param string $username 用户名
     * @param string $password 密码
     */
    public function __construct($url, $username = '', $password = '') {
        // 确保URL以/transmission/rpc结尾
        if (!preg_match('/\/transmission\/rpc$/', $url)) {
            $url = rtrim($url, '/') . '/transmission/rpc';
        }
        
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
    }
    
    /**
     * 发送请求到Transmission RPC
     * 
     * @param string $method RPC方法名
     * @param array $arguments 参数
     * @return array 响应数据
     */
    public function sendRequest($method, $arguments = []) {
        $data = [
            'method' => $method,
            'arguments' => $arguments
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Transmission-Session-Id: ' . $this->sessionId
        ]);
        
        // 如果设置了用户名和密码，添加基本认证
        if (!empty($this->username) && !empty($this->password)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // 处理CSRF保护，如果收到409错误，获取新的会话ID并重试
        if ($httpCode === 409) {
            $headers = [];
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) {
                    return $len;
                }
                $headers[strtolower(trim($header[0]))][] = trim($header[1]);
                return $len;
            });
            
            // 重新发送请求以获取会话ID
            curl_exec($ch);
            
            if (isset($headers['x-transmission-session-id'][0])) {
                $this->sessionId = $headers['x-transmission-session-id'][0];
                curl_close($ch);
                return $this->sendRequest($method, $arguments);
            }
        }
        
        curl_close($ch);
        
        if ($response === false) {
            return ['success' => false, 'error' => 'CURL错误: ' . curl_error($ch)];
        }
        
        $data = json_decode($response, true);
        if ($data === null) {
            return ['success' => false, 'error' => 'JSON解析错误: ' . $response];
        }
        
        if ($httpCode !== 200) {
            return ['success' => false, 'error' => '服务器错误: ' . $httpCode];
        }
        
        if (isset($data['result']) && $data['result'] === 'success') {
            return ['success' => true, 'data' => $data['arguments'] ?? []];
        }
        
        return ['success' => false, 'error' => $data['result'] ?? '未知错误'];
    }
    
    /**
     * 获取所有种子列表
     * 
     * @return array 种子列表
     */
    public function getTorrents() {
        $fields = [
            'id', 'name', 'hashString', 'status', 'totalSize', 'percentDone', 
            'downloadedEver', 'uploadedEver', 'rateDownload', 'rateUpload', 
            'eta', 'uploadRatio', 'error', 'errorString', 'peersConnected',
            'peersSendingToUs', 'peersGettingFromUs', 'addedDate', 'doneDate',
            'leftUntilDone', 'downloadDir'
        ];
        
        $response = $this->sendRequest('torrent-get', ['fields' => $fields]);
        
        if (!$response['success']) {
            error_log('Transmission getTorrents error: ' . ($response['error'] ?? 'Unknown error'));
            return $response;
        }
        
        if (!isset($response['data']['torrents']) || !is_array($response['data']['torrents'])) {
            error_log('Transmission getTorrents error: Invalid response format');
            return ['success' => false, 'error' => '无效的响应格式'];
        }
        
        $torrents = [];
        foreach ($response['data']['torrents'] as $torrent) {
            try {
                // 转换状态码为qBittorrent兼容的状态
                $status = $this->convertStatus($torrent['status'] ?? 0);
                
                $torrents[] = [
                    'hash' => $torrent['hashString'] ?? '',
                    'name' => $torrent['name'] ?? '',
                    'state' => $status,
                    'progress' => $torrent['percentDone'] ?? 0,
                    'size' => $torrent['totalSize'] ?? 0,
                    'downloaded' => $torrent['downloadedEver'] ?? 0,
                    'uploaded' => $torrent['uploadedEver'] ?? 0,
                    'dlspeed' => $torrent['rateDownload'] ?? 0,
                    'upspeed' => $torrent['rateUpload'] ?? 0,
                    'eta' => $torrent['eta'] ?? -1,
                    'ratio' => $torrent['uploadRatio'] ?? 0,
                    'num_seeds' => $torrent['peersSendingToUs'] ?? 0,
                    'num_leechs' => $torrent['peersGettingFromUs'] ?? 0,
                    'added_on' => $torrent['addedDate'] ?? 0,
                    'completion_on' => $torrent['doneDate'] ?? 0,
                    'save_path' => $torrent['downloadDir'] ?? ''
                ];
            } catch (Exception $e) {
                error_log('Transmission getTorrents error processing torrent: ' . $e->getMessage());
                continue;
            }
        }
        
        $result = ['success' => true, 'torrents' => $torrents];
        error_log('Transmission getTorrents result: ' . json_encode($result));
        return $result;
    }
    
    /**
     * 获取单个种子信息
     * 
     * @param string $hash 种子哈希
     * @return array 种子信息
     */
    public function getTorrent($hash) {
        $fields = [
            'id', 'name', 'hashString', 'status', 'totalSize', 'percentDone', 
            'downloadedEver', 'uploadedEver', 'rateDownload', 'rateUpload', 
            'eta', 'uploadRatio', 'error', 'errorString', 'peersConnected',
            'peersSendingToUs', 'peersGettingFromUs', 'addedDate', 'doneDate',
            'leftUntilDone', 'downloadDir', 'files', 'fileStats', 'peers'
        ];
        
        $response = $this->sendRequest('torrent-get', [
            'fields' => $fields,
            'ids' => [$hash]
        ]);
        
        if (!$response['success'] || empty($response['data']['torrents'])) {
            return ['success' => false, 'error' => '种子不存在'];
        }
        
        $torrent = $response['data']['torrents'][0];
        $status = $this->convertStatus($torrent['status']);
        
        // 处理文件列表
        $files = [];
        if (isset($torrent['files']) && isset($torrent['fileStats'])) {
            foreach ($torrent['files'] as $index => $file) {
                $files[] = [
                    'name' => $file['name'],
                    'size' => $file['length'],
                    'progress' => $file['bytesCompleted'] / $file['length'],
                    'priority' => $torrent['fileStats'][$index]['priority'],
                    'is_seed' => $file['bytesCompleted'] == $file['length']
                ];
            }
        }
        
        // 处理Peers列表
        $peers = [];
        if (isset($torrent['peers'])) {
            foreach ($torrent['peers'] as $peer) {
                $peers[] = [
                    'ip' => $peer['address'] . ':' . $peer['port'],
                    'client' => $peer['clientName'],
                    'progress' => $peer['progress'],
                    'dl_speed' => $peer['rateToClient'],
                    'up_speed' => $peer['rateToPeer'],
                    'flags' => $peer['flagStr']
                ];
            }
        }
        
        return [
            'success' => true,
            'torrent' => [
                'hash' => $torrent['hashString'],
                'name' => $torrent['name'],
                'state' => $status,
                'progress' => $torrent['percentDone'],
                'size' => $torrent['totalSize'],
                'downloaded' => $torrent['downloadedEver'],
                'uploaded' => $torrent['uploadedEver'],
                'dlspeed' => $torrent['rateDownload'],
                'upspeed' => $torrent['rateUpload'],
                'eta' => $torrent['eta'],
                'ratio' => $torrent['uploadRatio'],
                'num_seeds' => $torrent['peersSendingToUs'],
                'num_leechs' => $torrent['peersGettingFromUs'],
                'added_on' => $torrent['addedDate'],
                'completion_on' => $torrent['doneDate'],
                'save_path' => $torrent['downloadDir'],
                'files' => $files,
                'peers' => $peers
            ]
        ];
    }
    
    /**
     * 添加种子
     * 
     * @param string $torrent 种子文件路径或磁力链接
     * @param string $savePath 保存路径
     * @return array 添加结果
     */
    public function addTorrent($torrent, $savePath = '') {
        $arguments = [];
        
        // 检查是否为磁力链接
        if (preg_match('/^magnet:/', $torrent)) {
            $arguments['filename'] = $torrent;
        } else {
            // 读取种子文件内容并进行Base64编码
            $torrentContent = file_get_contents($torrent);
            if ($torrentContent === false) {
                return ['success' => false, 'error' => '无法读取种子文件'];
            }
            $arguments['metainfo'] = base64_encode($torrentContent);
        }
        
        // 设置保存路径
        if (!empty($savePath)) {
            $arguments['download-dir'] = $savePath;
        }
        
        $response = $this->sendRequest('torrent-add', $arguments);
        
        if (!$response['success']) {
            return $response;
        }
        
        if (isset($response['data']['torrent-added'])) {
            return [
                'success' => true, 
                'hash' => $response['data']['torrent-added']['hashString']
            ];
        } elseif (isset($response['data']['torrent-duplicate'])) {
            return [
                'success' => true, 
                'hash' => $response['data']['torrent-duplicate']['hashString'],
                'warning' => '种子已存在'
            ];
        } else {
            return ['success' => false, 'error' => '添加种子失败'];
        }
    }
    
    /**
     * 获取种子的ID
     * 
     * @param string $hash 种子哈希
     * @return string|null 种子哈希，如果未找到则返回null
     */
    private function getTorrentId($hash) {
        $fields = ['hashString'];
        $response = $this->sendRequest('torrent-get', [
            'fields' => $fields,
            'ids' => [$hash]
        ]);
        
        if (!$response['success'] || empty($response['data']['torrents'])) {
            return null;
        }
        
        return $hash;
    }
    
    /**
     * 删除种子
     * 
     * @param string $hash 种子哈希
     * @param bool $deleteFiles 是否同时删除文件
     * @return array 删除结果
     */
    public function deleteTorrent($hash, $deleteFiles = false) {
        $arguments = [
            'ids' => [$hash],
            'delete-local-data' => $deleteFiles
        ];
        
        $response = $this->sendRequest('torrent-remove', $arguments);
        error_log('Transmission deleteTorrent response: ' . json_encode($response));
        return $response;
    }
    
    /**
     * 删除种子（removeTorrent 是 deleteTorrent 的别名）
     * 
     * @param string $hash 种子哈希
     * @param bool $deleteFiles 是否同时删除文件
     * @return array 操作结果
     */
    public function removeTorrent($hash, $deleteFiles = false) {
        return $this->deleteTorrent($hash, $deleteFiles);
    }
    
    /**
     * 暂停种子
     * 
     * @param string $hash 种子哈希
     * @return array 操作结果
     */
    public function pauseTorrent($hash) {
        $arguments = [
            'ids' => [$hash]
        ];
        
        $response = $this->sendRequest('torrent-stop', $arguments);
        error_log('Transmission pauseTorrent response: ' . json_encode($response));
        return $response;
    }
    
    /**
     * 恢复种子
     * 
     * @param string $hash 种子哈希
     * @return array 操作结果
     */
    public function resumeTorrent($hash) {
        $arguments = [
            'ids' => [$hash]
        ];
        
        $response = $this->sendRequest('torrent-start', $arguments);
        error_log('Transmission resumeTorrent response: ' . json_encode($response));
        return $response;
    }
    
    /**
     * 立即恢复种子（忽略队列位置）
     * 
     * @param string $hash 种子哈希
     * @return array 操作结果
     */
    public function resumeTorrentNow($hash) {
        $arguments = [
            'ids' => [$hash]
        ];
        
        $response = $this->sendRequest('torrent-start-now', $arguments);
        
        return $response;
    }
    
    /**
     * 校验种子
     * 
     * @param string $hash 种子哈希
     * @return array 操作结果
     */
    public function recheckTorrent($hash) {
        $arguments = [
            'ids' => [$hash]
        ];
        
        $response = $this->sendRequest('torrent-verify', $arguments);
        error_log('Transmission recheckTorrent response: ' . json_encode($response));
        return $response;
    }
    
    /**
     * 重新汇报种子
     * 
     * @param string $hash 种子哈希
     * @return array 操作结果
     */
    public function reannounceTorrent($hash) {
        $arguments = [
            'ids' => [$hash]
        ];
        
        $response = $this->sendRequest('torrent-reannounce', $arguments);
        error_log('Transmission reannounceTorrent response: ' . json_encode($response));
        return $response;
    }
    
    /**
     * 获取统计信息
     * 
     * @return array 统计信息
     */
    public function getStats() {
        error_log('Transmission getStats: 开始获取统计信息');
        
        try {
            // 获取会话统计信息
            $statsResponse = $this->sendRequest('session-stats');
            if (!$statsResponse['success']) {
                error_log('Transmission getStats error: ' . ($statsResponse['error'] ?? 'Unknown error'));
                return $statsResponse;
            }
            
            $stats = $statsResponse['data'];
            error_log('Transmission session-stats raw data: ' . json_encode($stats));
            
            // 获取所有种子以计算总大小
            $torrentsResponse = $this->getTorrents();
            $totalSize = 0;
            if ($torrentsResponse['success'] && isset($torrentsResponse['torrents'])) {
                foreach ($torrentsResponse['torrents'] as $torrent) {
                    $totalSize += $torrent['size'] ?? 0;
                }
            }
            
            // 获取可用空间
            $freeSpaceResponse = $this->sendRequest('free-space', ['path' => '/']);
            $freeSpace = $freeSpaceResponse['success'] ? ($freeSpaceResponse['data']['size-bytes'] ?? 0) : 0;
            
            // 获取速度限制信息
            $sessionResponse = $this->sendRequest('session-get');
            $downloadSpeedLimit = -1;
            $uploadSpeedLimit = -1;
            if ($sessionResponse['success']) {
                if (isset($sessionResponse['data']['speed-limit-down-enabled']) && 
                    $sessionResponse['data']['speed-limit-down-enabled']) {
                    $downloadSpeedLimit = $sessionResponse['data']['speed-limit-down'] * 1024;
                }
                if (isset($sessionResponse['data']['speed-limit-up-enabled']) && 
                    $sessionResponse['data']['speed-limit-up-enabled']) {
                    $uploadSpeedLimit = $sessionResponse['data']['speed-limit-up'] * 1024;
                }
            }
            
            // 直接从 session-stats 获取当前速度
            $downloadSpeed = 0;
            $uploadSpeed = 0;
            
            // 检查不同的可能的字段路径
            if (isset($stats['arguments']['downloadSpeed'])) {
                $downloadSpeed = $stats['arguments']['downloadSpeed'];
            } elseif (isset($stats['downloadSpeed'])) {
                $downloadSpeed = $stats['downloadSpeed'];
            }
            
            if (isset($stats['arguments']['uploadSpeed'])) {
                $uploadSpeed = $stats['arguments']['uploadSpeed'];
            } elseif (isset($stats['uploadSpeed'])) {
                $uploadSpeed = $stats['uploadSpeed'];
            }
            
            // 记录详细的速度信息
            error_log('Transmission speeds from session-stats - Upload: ' . $uploadSpeed . ' B/s, Download: ' . $downloadSpeed . ' B/s');
            
            // 使用 cumulative-stats 获取总上传下载量
            $downloadedBytes = 0;
            $uploadedBytes = 0;
            
            if (isset($stats['arguments']['cumulative-stats'])) {
                $cumulativeStats = $stats['arguments']['cumulative-stats'];
                $downloadedBytes = $cumulativeStats['downloadedBytes'] ?? 0;
                $uploadedBytes = $cumulativeStats['uploadedBytes'] ?? 0;
            } elseif (isset($stats['cumulative-stats'])) {
                $cumulativeStats = $stats['cumulative-stats'];
                $downloadedBytes = $cumulativeStats['downloadedBytes'] ?? 0;
                $uploadedBytes = $cumulativeStats['uploadedBytes'] ?? 0;
            }
            
            // 如果无法从 session-stats 获取速度，尝试从活动种子计算
            if ($downloadSpeed == 0 && $uploadSpeed == 0) {
                error_log('Transmission speeds are zero, trying to calculate from active torrents');
                if ($torrentsResponse['success'] && isset($torrentsResponse['torrents'])) {
                    foreach ($torrentsResponse['torrents'] as $torrent) {
                        $downloadSpeed += $torrent['dlspeed'] ?? 0;
                        $uploadSpeed += $torrent['upspeed'] ?? 0;
                    }
                    error_log('Calculated speeds from torrents - Upload: ' . $uploadSpeed . ' B/s, Download: ' . $downloadSpeed . ' B/s');
                }
            }
            
            // 获取种子数量
            $torrentCount = 0;
            if (isset($stats['arguments']['torrentCount'])) {
                $torrentCount = $stats['arguments']['torrentCount'];
            } elseif (isset($stats['torrentCount'])) {
                $torrentCount = $stats['torrentCount'];
            } elseif ($torrentsResponse['success'] && isset($torrentsResponse['torrents'])) {
                $torrentCount = count($torrentsResponse['torrents']);
            }
            
            $result = [
                'success' => true,
                'stats' => [
                    'dl_info_speed' => $downloadSpeed,
                    'dl_info_data' => $downloadedBytes,
                    'up_info_speed' => $uploadSpeed,
                    'up_info_data' => $uploadedBytes,
                    'dl_rate_limit' => $downloadSpeedLimit,
                    'up_rate_limit' => $uploadSpeedLimit,
                    'dht_nodes' => 0,
                    'connection_status' => 'connected',
                    'free_space' => $freeSpace,
                    'total_size' => $totalSize,
                    'torrent_count' => $torrentCount,
                    'dl_speed' => $downloadSpeed,
                    'up_speed' => $uploadSpeed
                ]
            ];
            
            error_log('Transmission getStats final result: ' . json_encode($result));
            return $result;
        } catch (Exception $e) {
            error_log('Transmission getStats exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取种子的Peers
     * 
     * @param string $hash 种子哈希
     * @return array Peers列表
     */
    public function getTorrentPeers($hash) {
        $response = $this->sendRequest('torrent-get', [
            'ids' => [$hash],
            'fields' => ['peers', 'trackerStats']
        ]);
        
        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $response['error'] ?? '未知错误'
            ];
        }
        
        if (empty($response['data']['torrents'])) {
            return [
                'success' => false,
                'error' => '种子不存在'
            ];
        }
        
        $torrent = $response['data']['torrents'][0];
        $peers = [];
        $trackerPeers = [];
        
        // 处理已连接的peers
        if (isset($torrent['peers']) && is_array($torrent['peers'])) {
            foreach ($torrent['peers'] as $peer) {
                $peers[] = [
                    'ip' => $peer['address'] . ':' . $peer['port'],
                    'client' => $peer['clientName'] ?? 'Unknown',
                    'up_speed' => $peer['rateToClient'] ?? 0,
                    'dl_speed' => $peer['rateToPeer'] ?? 0,
                    'progress' => $peer['progress'] ?? 0,
                    'flags' => []
                ];
            }
        }
        
        // 处理tracker统计信息
        if (isset($torrent['trackerStats']) && is_array($torrent['trackerStats'])) {
            foreach ($torrent['trackerStats'] as $tracker) {
                if (isset($tracker['lastAnnounceSucceeded']) && $tracker['lastAnnounceSucceeded']) {
                    $numPeers = ($tracker['seederCount'] ?? 0) + ($tracker['leecherCount'] ?? 0);
                    // 减去已连接的peers数量，避免重复
                    $numPeers = max(0, $numPeers - count($peers));
                    
                    // 为每个潜在的peer创建一个条目
                    for ($i = 0; $i < $numPeers; $i++) {
                        $trackerPeers[] = [
                            'ip' => 'Unknown',
                            'status' => 'not_connected',
                            'flags' => ['from_tracker']
                        ];
                    }
                }
            }
        }
        
        return [
            'success' => true,
            'peers' => $peers,
            'trackerPeers' => $trackerPeers
        ];
    }
    
    /**
     * 获取可用空间
     * 
     * @param string $path 路径
     * @return array 可用空间信息
     */
    public function getFreeSpace($path) {
        $response = $this->sendRequest('free-space', ['path' => $path]);
        
        if (!$response['success']) {
            return $response;
        }
        
        return [
            'success' => true,
            'free_space' => $response['data']['size-bytes'] ?? 0
        ];
    }
    
    /**
     * 转换Transmission状态码为qBittorrent兼容的状态
     * 
     * @param int $status Transmission状态码
     * @return string qBittorrent兼容的状态
     */
    private function convertStatus($status) {
        // Transmission状态码
        // 0: 已停止
        // 1: 排队等待校验
        // 2: 校验中
        // 3: 排队等待下载
        // 4: 下载中
        // 5: 排队等待做种
        // 6: 做种中
        
        try {
            $status = intval($status);
            
            switch ($status) {
                case 0:
                    return 'pausedUP';
                case 1:
                    return 'checkingUP';
                case 2:
                    return 'checkingUP';
                case 3:
                    return 'queuedDL';
                case 4:
                    return 'downloading';
                case 5:
                    return 'queuedUP';
                case 6:
                    return 'uploading';
                default:
                    error_log('Transmission convertStatus unknown status: ' . $status);
                    return 'unknown';
            }
        } catch (Exception $e) {
            error_log('Transmission convertStatus error: ' . $e->getMessage());
            return 'unknown';
        }
    }
    
    /**
     * 暂停/恢复种子
     * 
     * @param string $hash 种子哈希
     * @return array 操作结果
     */
    public function pauseResumeTorrent($hash) {
        // 先获取种子状态
        $torrent = $this->getTorrent($hash);
        if (!$torrent['success']) {
            return $torrent;
        }
        
        // 根据当前状态决定暂停还是恢复
        if (strpos($torrent['torrent']['state'], 'paused') !== false) {
            return $this->resumeTorrent($hash);
        } else {
            return $this->pauseTorrent($hash);
        }
    }
} 