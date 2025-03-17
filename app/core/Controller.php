<?php
namespace app\core;

/**
 * 基础控制器类
 */
abstract class Controller
{
    public function __construct()
    {
        $this->checkInstall();
    }

    /**
     * 检查是否已安装
     */
    protected function checkInstall()
    {
        // 如果访问的是安装页面,则不检查
        if (strpos($_SERVER['REQUEST_URI'], 'install.php') !== false) {
            return;
        }

        // 检查配置文件是否存在
        if (!file_exists(APP_ROOT . '/config/database.php')) {
            header('Location: /install.php');
            exit;
        }
    }

    /**
     * 渲染视图
     */
    protected function render($view, $data = [])
    {
        extract($data);
        $viewFile = APP_ROOT . "/app/views/{$view}.php";
        if (file_exists($viewFile)) {
            require $viewFile;
        }
    }

    /**
     * 重定向
     */
    protected function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * 设置闪存消息
     */
    protected function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * 检查是否已登录
     */
    protected function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * 获取当前用户
     */
    protected function getUser()
    {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'avatar' => $_SESSION['avatar'] ?? ''
            ];
        }
        return null;
    }

    /**
     * 要求登录
     */
    protected function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            $this->setFlash('error', '请先登录');
            $this->redirect('/user/login');
        }
    }

    /**
     * 返回JSON响应
     */
    protected function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
} 