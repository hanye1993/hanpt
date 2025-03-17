# ThinkPHP应用框架

这是一个基于PHP的MVC框架，提供了用户认证、数据库操作、主题切换等功能。

## 目录结构

```
├── app                     # 应用程序目录
│   ├── config              # 配置文件
│   ├── controllers         # 控制器
│   ├── helpers             # 辅助类
│   ├── models              # 模型
│   └── views               # 视图
│       ├── components      # 组件
│       ├── layouts         # 布局
│       └── pages           # 页面
├── database                # 数据库相关文件
├── public                  # 公共访问目录
│   ├── assets              # 静态资源
│   │   ├── css             # CSS文件
│   │   ├── js              # JavaScript文件
│   │   └── images          # 图片文件
│   └── index.php           # 入口文件
└── storage                 # 存储目录
    ├── backups             # 数据库备份
    ├── cache               # 缓存文件
    ├── logs                # 日志文件
    └── uploads             # 上传文件
        └── avatars         # 用户头像
```

## 功能特性

- MVC架构，清晰分离业务逻辑、数据访问和表示层
- 用户认证系统，包括登录、注册、个人资料管理
- 数据库操作，包括基本的CRUD操作和事务支持
- 数据库备份和恢复功能
- 主题切换功能（深色/浅色模式）
- 响应式设计，适应不同屏幕尺寸
- 错误处理和404页面

## 安装说明

1. 克隆仓库到本地
2. 创建数据库并导入 `database/init.sql` 文件
3. 配置数据库连接信息（`app/config/database.php`）
4. 确保 `storage` 目录及其子目录可写
5. 配置Web服务器，将根目录指向 `public` 目录

## 使用说明

### 用户认证

- 访问 `/user/login` 登录系统
- 访问 `/user/register` 注册新用户
- 访问 `/user/profile` 管理个人资料
- 访问 `/user/logs` 查看操作日志

### 数据库管理

- 访问 `/database` 管理数据库备份和恢复

### 开发指南

#### 创建新控制器

```php
<?php
namespace app\controllers;

class ExampleController extends Controller
{
    public function index()
    {
        $this->render('example/index', [
            'page_title' => '示例页面',
            'current_page' => 'example'
        ]);
    }
}
```

#### 创建新模型

```php
<?php
namespace app\models;

class Example extends Model
{
    protected $table = 'examples';
    
    // 添加自定义方法
}
```

#### 创建新视图

```php
<?php
// 开始输出缓冲
ob_start();
?>

<div class="page-header">
    <h1>示例页面</h1>
</div>

<div class="page-card">
    <div class="card-header">
        <h3>示例内容</h3>
    </div>
    <div class="card-body">
        <p>这是一个示例页面。</p>
    </div>
</div>

<?php
// 获取输出缓冲内容
$content = ob_get_clean();

// 包含主布局文件
include_once __DIR__ . '/../layouts/main.php';
?>
```

## 技术栈

- PHP 7.4+
- MySQL 5.7+
- HTML5/CSS3
- JavaScript
- Font Awesome 6

## 许可证

MIT