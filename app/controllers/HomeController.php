<?php
namespace app\controllers;

use app\models\Downloader;
use app\models\Site;
use app\models\Torrent;
use app\models\VampireRule;

/**
 * 首页控制器
 * 
 * 处理与首页相关的请求。
 */
class HomeController extends Controller
{
    /**
     * 首页方法
     * 
     * @return void
     */
    public function index()
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        // 设置页面标题和当前页面
        $page_title = '仪表盘';
        $current_page = 'dashboard';
        
        // 获取统计数据
        $downloaderModel = new Downloader();
        $siteModel = new Site();
        $torrentModel = new Torrent();
        $vampireModel = new VampireRule();
        
        // 获取种子数量
        $torrentCount = $torrentModel->count();
        
        // 获取种子总体积
        $torrentSize = $torrentModel->getTotalSize();
        
        // 获取站点数量
        $siteCount = $siteModel->count();
        
        // 获取下载器数量
        $downloaderCount = $downloaderModel->count();
        
        // 获取下载器上传下载统计数据
        $transferStats = $downloaderModel->getTransferStats();
        
        // 获取PeerBan数据（按日期统计）
        $peerbanStats = $vampireModel->getStatsByDate(30);
        
        // 获取公告信息
        $announcements = $this->getAnnouncements();
        
        // 渲染视图
        $this->render('dashboard', [
            'page_title' => $page_title,
            'current_page' => $current_page,
            'torrentCount' => $torrentCount,
            'torrentSize' => $torrentSize,
            'siteCount' => $siteCount,
            'downloaderCount' => $downloaderCount,
            'transferStats' => $transferStats,
            'peerbanStats' => $peerbanStats,
            'announcements' => $announcements
        ]);
    }
    
    /**
     * 获取公告信息
     * 
     * @return array 公告信息
     */
    private function getAnnouncements()
    {
        $announcementFile = __DIR__ . '/../../storage/announcements.txt';
        $announcements = [];
        
        if (file_exists($announcementFile)) {
            $content = file_get_contents($announcementFile);
            $announcements = array_filter(explode("\n", $content));
        }
        
        return $announcements;
    }
    
    /**
     * 关于页面
     * 
     * @return void
     */
    public function about()
    {
        // 设置页面标题和当前页面
        $page_title = '关于我们';
        $current_page = 'about';
        
        // 渲染视图
        $this->render('about', [
            'page_title' => $page_title,
            'current_page' => $current_page,
        ]);
    }
} 