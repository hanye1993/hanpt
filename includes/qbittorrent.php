<?php
/**
 * qBittorrent API 封装类
 */
class QBittorrent {
    private $domain;
    private $username;
    private $password;
    private $cookieFile;
    private $isLoggedIn = false;

    /**
     * 构造函数
     * @param string $domain qBittorrent WebUI域名
     * @param string $username 用户名
     * @param string $password 密码
     */
    public function __construct($domain, $username, $password) {
        $this->domain = rtrim($domain, '/');
        $this->username = $username;
        $this->password = $password;
        
        // 创建cookies目录（如果不存在）
        if (!is_dir(__DIR__ . '/../cookies')) {
            mkdir(__DIR__ . '/../cookies', 0755, true);
        }
        
        // 设置cookie文件路径
        $this->cookieFile = __DIR__ . '/../cookies/qb_' . md5($domain . $username) . '.cookie';
    }

    /**
     * 检查登录状态，如果未登录则进行登录
     */
    private function checkLogin() {
        if ($this->isLoggedIn) {
            return true;
        }

        try {
            // 尝试登录
            $ch = curl_init($this->domain . '/api/v2/auth/login');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'username' => $this->username,
                    'password' => $this->password
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_COOKIEJAR => $this->cookieFile,
                CURLOPT_COOKIEFILE => $this->cookieFile,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === 'Ok.' && $httpCode === 200) {
                $this->isLoggedIn = true;
                return true;
            }

            error_log('QBittorrent登录失败: ' . $response . ' HTTP: ' . $httpCode);
            return false;
        } catch (Exception $e) {
            error_log('QBittorrent登录异常: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送GET请求
     */
    private function get($path, $params = []) {
        try {
            $this->checkLogin();

            $url = $this->domain . $path;
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_COOKIEFILE => $this->cookieFile,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return json_decode($response, true);
            }

            error_log('QBittorrent API请求失败: HTTP ' . $httpCode . ' URL: ' . $url);
            return null;
        } catch (Exception $e) {
            error_log('QBittorrent API请求异常: ' . $e->getMessage() . ' URL: ' . $path);
            return null;
        }
    }

    /**
     * 发送POST请求到qBittorrent API
     * @param string $endpoint API端点
     * @param array $params 请求参数
     * @return mixed 请求结果
     */
    private function post($endpoint, $params = []) {
        $url = $this->domain . '/api/v2/' . ltrim($endpoint, '/');
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($result === false || $httpCode >= 400) {
            return null;
        }
        
        return $result;
    }

    /**
     * 获取所有种子信息
     * @return array 种子列表
     */
    public function getTorrents() {
        try {
            $response = $this->get("/api/v2/torrents/info");
            return $response !== null ? $response : [];
        } catch (Exception $e) {
            error_log('QBittorrent getTorrents error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取种子的peers信息
     * @param string $hash 种子的hash值
     * @return array peers信息数组
     */
    public function getTorrentPeers($hash) {
        $response = $this->get("/api/v2/sync/torrentPeers", [
            'hash' => $hash,
            'rid' => 0
        ]);
        
        if (!$response || !isset($response['peers'])) {
            return [];
        }
        
        return $response['peers'];
    }

    /**
     * 获取活动的种子列表
     */
    public function getActiveTorrents() {
        $response = $this->get("/api/v2/torrents/info", [
            'filter' => 'active'
        ]);
        
        return $response ?: [];
    }

    /**
     * 封禁Peer
     * @param string $ip 要封禁的IP
     * @return bool 是否成功
     */
    public function banPeer($ip) {
        if (!$this->checkLogin()) {
            return false;
        }

        try {
            $ch = curl_init($this->domain . '/api/v2/app/banPeers');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'peers' => $ip
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_COOKIEFILE => $this->cookieFile
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (Exception $e) {
            error_log('qBittorrent banPeer error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 解除IP封禁
     * @param string $ip 要解除封禁的IP地址
     * @return bool 是否成功
     */
    public function unbanPeer($ip) {
        return $this->post("/api/v2/transfer/unbanPeers", [
            'peers' => $ip
        ]);
    }

    /**
     * 获取种子的trackers信息
     * @param string $hash 种子的hash值
     * @return array tracker peers信息数组
     */
    public function getTorrentTrackers($hash) {
        $response = $this->get("/api/v2/torrents/trackers", [
            'hash' => $hash
        ]);
        
        if (!$response) {
            return [];
        }
        
        $trackerPeers = [];
        foreach ($response as $tracker) {
            if (isset($tracker['num_peers']) && $tracker['num_peers'] > 0) {
                // 从 tracker 获取的 peers 数量
                $numPeers = $tracker['num_peers'];
                
                // 为每个潜在的 peer 创建一个条目
                for ($i = 0; $i < $numPeers; $i++) {
                    $trackerPeers[] = [
                        'ip' => 'Unknown',  // Tracker 不提供具体 IP
                        'status' => 'not_connected',
                        'flags' => ['from_tracker']
                    ];
                }
            }
        }
        
        return $trackerPeers;
    }

    /**
     * 获取单个种子的详细信息
     * @param string $hash 种子的hash值
     * @return array 种子详细信息
     */
    public function getTorrent($hash) {
        $torrents = $this->get("/api/v2/torrents/info", [
            'hashes' => $hash
        ]);
        
        if (!$torrents || empty($torrents)) {
            return [];
        }
        
        $torrent = $torrents[0];
        
        // 获取文件列表
        $files = $this->get("/api/v2/torrents/files", [
            'hash' => $hash
        ]);
        
        // 获取tracker列表
        $trackers = $this->get("/api/v2/torrents/trackers", [
            'hash' => $hash
        ]);
        
        // 获取种子属性
        $properties = $this->get("/api/v2/torrents/properties", [
            'hash' => $hash
        ]);
        
        // 合并所有信息
        $torrent['files'] = $files ?? [];
        $torrent['trackers'] = $trackers ?? [];
        $torrent['properties'] = $properties ?? [];
        
        return $torrent;
    }

    /**
     * 获取同步主数据
     */
    public function getMainData() {
        try {
            $response = $this->get("/api/v2/sync/maindata");
            return $response !== null ? $response : [];
        } catch (Exception $e) {
            error_log('QBittorrent getMainData error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取qBittorrent版本号
     */
    public function getVersion() {
        try {
            $response = $this->get("/api/v2/app/version");
            return $response !== null ? $response : '';
        } catch (Exception $e) {
            error_log('QBittorrent getVersion error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * 删除种子
     * @param string $hash 种子的哈希值
     * @param bool $deleteFiles 是否同时删除文件，默认为false
     * @return bool 是否成功
     */
    public function deleteTorrent($hash, $deleteFiles = false) {
        if (!$this->checkLogin()) {
            return false;
        }

        try {
            $ch = curl_init($this->domain . '/api/v2/torrents/delete');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'hashes' => $hash,
                    'deleteFiles' => $deleteFiles ? 'true' : 'false'
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_COOKIEFILE => $this->cookieFile
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (Exception $e) {
            error_log('qBittorrent deleteTorrent error: ' . $e->getMessage());
            return false;
        }
    }
}