<?php
/**
 * 应用程序入口文件
 * 
 * 这个文件是应用程序的主入口点，负责初始化应用程序并路由请求到相应的控制器。
 */

// 定义应用程序根目录
define('APP_ROOT', dirname(__DIR__));

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 启动会话
session_start();

// 自动加载类
spl_autoload_register(function ($class) {
    // 将命名空间转换为文件路径
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = APP_ROOT . DIRECTORY_SEPARATOR . $class . '.php';
    
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
});

// 简单的路由系统
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// 默认控制器和方法
$controller = 'Home';
$method = 'index';
$params = [];

// 解析路径
if (!empty($path)) {
    $path_parts = explode('/', $path);
    
    // 获取控制器
    if (!empty($path_parts[0])) {
        $controller = ucfirst($path_parts[0]);
        array_shift($path_parts);
    }
    
    // 获取方法
    if (!empty($path_parts[0])) {
        $method = $path_parts[0];
        array_shift($path_parts);
    }
    
    // 剩余部分作为参数
    $params = $path_parts;
}

// 构建控制器类名
$controller_class = "app\\controllers\\{$controller}Controller";

// 检查控制器是否存在
if (!class_exists($controller_class)) {
    // 如果控制器不存在，尝试直接加载视图
    $view_path = APP_ROOT . "/app/views/pages/{$controller}.php";
    if (file_exists($view_path)) {
        include $view_path;
        exit;
    }
    
    // 如果视图也不存在，显示404页面
    header("HTTP/1.0 404 Not Found");
    include APP_ROOT . '/app/views/pages/404.php';
    exit;
}

// 创建控制器实例
$controller_instance = new $controller_class();

// 检查方法是否存在
if (!method_exists($controller_instance, $method)) {
    header("HTTP/1.0 404 Not Found");
    include APP_ROOT . '/app/views/pages/404.php';
    exit;
}

// 调用控制器方法
call_user_func_array([$controller_instance, $method], $params);
?> 