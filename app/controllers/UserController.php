<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Downloader;
use Exception;

/**
 * 用户控制器
 * 
 * 处理与用户相关的请求。
 */
class UserController extends Controller
{
    /**
     * 用户模型
     * 
     * @var User
     */
    protected $userModel;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->userModel = new User();
    }
    
    /**
     * 获取下载器统计信息
     */
    public function getStats()
    {
        try {
            $user_id = $this->getUserId();
            $downloaderModel = new Downloader();
            $downloaders = $downloaderModel->where(['user_id' => $user_id, 'status' => 1])->get();
            
            $stats = [];
            foreach ($downloaders as $downloader) {
                $stats[] = [
                    'id' => $downloader['id'],
                    'name' => $downloader['name'],
                    'type' => $downloader['type'],
                    'stats' => (new Downloader())->find($downloader['id'])->getStats()
                ];
            }
            
            return $this->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 用户个人资料页面
     * 
     * @return void
     */
    public function profile()
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        // 获取用户信息
        $userId = $this->getUserId();
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $this->setFlash('error', '用户不存在');
            $this->redirect('/');
        }
        
        // 设置页面标题和当前页面
        $page_title = '个人资料';
        $current_page = 'profile';
        
        // 渲染视图
        $this->render('profile', [
            'page_title' => $page_title,
            'current_page' => $current_page,
            'user' => $user
        ]);
    }
    
    /**
     * 更新用户名
     * 
     * @return void
     */
    public function updateUsername()
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/user/profile');
        }
        
        // 获取表单数据
        $newUsername = trim($_POST['username'] ?? '');
        
        // 验证用户名
        if (empty($newUsername)) {
            $this->setFlash('error', '用户名不能为空');
            $this->redirect('/user/profile');
        }
        
        // 检查用户名是否已存在
        $existingUser = $this->userModel->findByUsername($newUsername);
        if ($existingUser && $existingUser['id'] != $this->getUserId()) {
            $this->setFlash('error', '用户名已被使用');
            $this->redirect('/user/profile');
        }
        
        // 更新用户名
        $userId = $this->getUserId();
        $result = $this->userModel->updateUser($userId, ['username' => $newUsername]);
        
        if ($result) {
            // 更新会话中的用户名
            $_SESSION['username'] = $newUsername;
            
            // 记录操作日志
            $this->userModel->logAction($userId, 'update_username', "用户名更新为: {$newUsername}");
            
            $this->setFlash('success', '用户名更新成功');
        } else {
            $this->setFlash('error', '用户名更新失败');
        }
        
        $this->redirect('/user/profile');
    }
    
    /**
     * 更新密码
     * 
     * @return void
     */
    public function updatePassword()
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/user/profile');
        }
        
        // 获取表单数据
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // 验证密码
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->setFlash('error', '所有密码字段都是必填的');
            $this->redirect('/user/profile');
        }
        
        if ($newPassword !== $confirmPassword) {
            $this->setFlash('error', '新密码和确认密码不匹配');
            $this->redirect('/user/profile');
        }
        
        // 验证当前密码
        $userId = $this->getUserId();
        $user = $this->userModel->find($userId);
        
        // 调试：检查$user的类型
        if (is_object($user)) {
            // 如果是对象，使用对象属性访问方式
            if (!$user || !$this->userModel->verifyPassword($currentPassword, $user->password)) {
                $this->setFlash('error', '当前密码不正确');
                $this->redirect('/user/profile');
            }
        } else {
            // 如果是数组，使用数组访问方式
            if (!$user || !$this->userModel->verifyPassword($currentPassword, $user['password'])) {
                $this->setFlash('error', '当前密码不正确');
                $this->redirect('/user/profile');
            }
        }
        
        // 更新密码
        $result = $this->userModel->updatePassword($userId, $newPassword);
        
        if ($result) {
            // 记录操作日志
            $this->userModel->logAction($userId, 'update_password', '密码已更新');
            
            $this->setFlash('success', '密码更新成功');
        } else {
            $this->setFlash('error', '密码更新失败');
        }
        
        $this->redirect('/user/profile');
    }
    
    /**
     * 更新头像
     * 
     * @return void
     */
    public function updateAvatar()
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/user/profile');
        }
        
        // 检查是否有文件上传
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', '头像上传失败');
            $this->redirect('/user/profile');
        }
        
        // 验证文件类型
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['avatar']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $this->setFlash('error', '只允许上传JPG、PNG或GIF图片');
            $this->redirect('/user/profile');
        }
        
        // 验证文件大小（最大2MB）
        $maxSize = 2 * 1024 * 1024;
        if ($_FILES['avatar']['size'] > $maxSize) {
            $this->setFlash('error', '头像文件大小不能超过2MB');
            $this->redirect('/user/profile');
        }
        
        // 生成唯一文件名
        $userId = $this->getUserId();
        $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        
        // 上传目录
        $uploadDir = APP_ROOT . '/storage/uploads/avatars/';
        
        // 确保目录存在
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $targetPath = $uploadDir . $filename;
        
        // 移动上传的文件
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
            // 获取相对路径
            $relativePath = '/storage/uploads/avatars/' . $filename;
            
            // 更新用户头像
            $result = $this->userModel->updateAvatar($userId, $relativePath);
            
            if ($result) {
                // 更新会话中的头像
                $_SESSION['avatar'] = $relativePath;
                
                // 记录操作日志
                $this->userModel->logAction($userId, 'update_avatar', '头像已更新');
                
                $this->setFlash('success', '头像更新成功');
            } else {
                $this->setFlash('error', '头像更新失败');
            }
        } else {
            $this->setFlash('error', '头像上传失败');
        }
        
        $this->redirect('/user/profile');
    }
    
    /**
     * 用户日志页面
     * 
     * @return void
     */
    public function logs()
    {
        // 检查用户是否已登录
        $this->requireLogin();
        
        // 获取分页参数
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        // 获取用户日志
        $userId = $this->getUserId();
        $logs = $this->userModel->getUserLogs($userId, $perPage, $offset);
        
        // 获取日志总数
        $totalLogs = $this->userModel->count(['user_id' => $userId]);
        $totalPages = ceil($totalLogs / $perPage);
        
        // 设置页面标题和当前页面
        $page_title = '操作日志';
        $current_page = 'logs';
        
        // 渲染视图
        $this->render('logs', [
            'page_title' => $page_title,
            'current_page' => $current_page,
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }
    
    /**
     * 登录页面
     * 
     * @return void
     */
    public function login()
    {
        // 如果用户已登录，重定向到首页
        if ($this->isLoggedIn()) {
            $this->redirect('/');
        }
        
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            
            // 验证用户名和密码
            if (empty($username) || empty($password)) {
                $this->setFlash('error', '用户名和密码不能为空');
                $this->render('login', ['page_title' => '登录']);
                return;
            }
            
            // 尝试登录
            $user = $this->userModel->login($username, $password);
            
            if ($user) {
                // 设置会话
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['avatar'] = $user['avatar'];
                
                // 记录操作日志
                $this->userModel->logAction($user['id'], 'login', '用户登录成功');
                
                // 如果选择了"记住我"，设置cookie
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (86400 * 30); // 30天
                    
                    setcookie('remember_token', $token, $expires, '/');
                    
                    // 存储令牌到数据库（这里简化处理）
                    $this->userModel->updateUser($user['id'], ['remember_token' => $token]);
                }
                
                $this->redirect('/');
            } else {
                $this->setFlash('error', '用户名或密码不正确');
                $this->render('login', ['page_title' => '登录']);
            }
        } else {
            // 显示登录页面
            $this->render('login', ['page_title' => '登录']);
        }
    }
    
    /**
     * 注册页面
     * 
     * @return void
     */
    public function register()
    {
        // 如果用户已登录，重定向到首页
        if ($this->isLoggedIn()) {
            $this->redirect('/');
        }
        
        // 检查请求方法
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // 验证表单数据
            $errors = [];
            
            if (empty($username)) {
                $errors[] = '用户名不能为空';
            }
            
            if (empty($email)) {
                $errors[] = '邮箱不能为空';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = '邮箱格式不正确';
            }
            
            if (empty($password)) {
                $errors[] = '密码不能为空';
            } elseif (strlen($password) < 6) {
                $errors[] = '密码长度不能少于6个字符';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = '两次输入的密码不一致';
            }
            
            // 检查用户名和邮箱是否已存在
            if ($this->userModel->findByUsername($username)) {
                $errors[] = '用户名已被使用';
            }
            
            if ($this->userModel->findByEmail($email)) {
                $errors[] = '邮箱已被注册';
            }
            
            // 如果有错误，显示错误信息
            if (!empty($errors)) {
                $this->render('register', [
                    'page_title' => '注册',
                    'errors' => $errors,
                    'username' => $username,
                    'email' => $email
                ]);
                return;
            }
            
            // 创建新用户
            $userId = $this->userModel->create($username, $email, $password);
            
            if ($userId) {
                // 记录操作日志
                $this->userModel->logAction($userId, 'register', '用户注册成功');
                
                // 自动登录
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['avatar'] = '';
                
                $this->setFlash('success', '注册成功，欢迎加入！');
                $this->redirect('/');
            } else {
                $this->setFlash('error', '注册失败，请稍后再试');
                $this->render('register', [
                    'page_title' => '注册',
                    'username' => $username,
                    'email' => $email
                ]);
            }
        } else {
            // 显示注册页面
            $this->render('register', ['page_title' => '注册']);
        }
    }
    
    /**
     * 退出登录
     * 
     * @return void
     */
    public function logout()
    {
        // 检查用户是否已登录
        if ($this->isLoggedIn()) {
            // 记录操作日志
            $this->userModel->logAction($this->getUserId(), 'logout', '用户退出登录');
            
            // 清除会话
            session_unset();
            session_destroy();
            
            // 清除记住我的cookie
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }
        
        $this->redirect('/user/login');
    }
} 