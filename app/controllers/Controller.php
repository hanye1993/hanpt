<?php
namespace app\controllers;

/**
 * 控制器基类
 * 所有控制器都应继承此类
 */
class Controller
{
    /**
     * 渲染视图
     * 
     * @param string $view 视图文件路径（相对于views/pages目录）
     * @param array $data 传递给视图的数据
     * @return void
     */
    protected function render($view, $data = [])
    {
        // 开始输出缓冲
        ob_start();
        
        // 提取数据变量，使其在视图中可用
        extract($data);
        
        // 包含视图文件
        $viewPath = __DIR__ . '/../views/pages/' . $view . '.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            // 视图不存在，显示错误
            echo "视图文件不存在: {$viewPath}";
        }
        
        // 获取输出缓冲内容
        $content = ob_get_clean();
        
        // 包含布局文件
        include_once __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * 重定向到指定URL
     * 
     * @param string $url 重定向URL
     * @return void
     */
    protected function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * 设置闪存消息
     * 
     * @param string $message 消息内容
     * @param string $type 消息类型（success, error, warning, info）
     * @return void
     */
    protected function setFlashMessage($message, $type = 'info')
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    /**
     * 设置闪存消息（旧方法，为兼容性保留）
     * 
     * @param string $type 消息类型（success, error, warning, info）
     * @param string $message 消息内容
     * @return void
     */
    protected function setFlash($type, $message)
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * 检查用户是否已登录
     * 
     * @return bool
     */
    protected function isLoggedIn()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * 要求用户登录，如果未登录则重定向到登录页面
     * 
     * @return void
     */
    protected function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            $this->setFlash('warning', '请先登录后再访问该页面');
            $this->redirect('/login.php');
        }
    }
    
    /**
     * 获取当前登录用户ID
     * 
     * @return int|null
     */
    protected function getUserId()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    /**
     * 获取当前登录用户名
     * 
     * @return string|null
     */
    protected function getUsername()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        return isset($_SESSION['username']) ? $_SESSION['username'] : null;
    }
    
    /**
     * 获取POST请求数据
     * 
     * @param string $key 数据键名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getPostData($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    
    /**
     * 获取GET请求数据
     * 
     * @param string $key 数据键名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getQueryData($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
    
    /**
     * 验证CSRF令牌
     * 
     * @return bool
     */
    protected function validateCsrfToken()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return $_POST['csrf_token'] === $_SESSION['csrf_token'];
    }
    
    /**
     * 生成CSRF令牌
     * 
     * @return string
     */
    protected function generateCsrfToken()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
} 