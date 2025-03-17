# 吸血检测定时任务设置指南

为了使吸血检测功能能够自动运行，您需要设置一个定时任务（Cron Job）来定期执行检测脚本。本指南将帮助您在不同的环境中设置定时任务。

## 前提条件

1. 确保您已经正确安装并配置了吸血检测系统
2. 确保您已经运行了 `update_vampire_db.php` 更新数据库
3. 确保您已经在管理界面中启用了吸血检测功能（设置 `vampire_enabled` 为 1）

## Linux/Unix 环境设置

在 Linux/Unix 系统中，您可以使用 crontab 来设置定时任务。

### 步骤 1: 编辑 crontab

```bash
crontab -e
```

### 步骤 2: 添加定时任务

添加以下行到 crontab 文件中（根据您的实际路径进行调整）：

```
# 每5分钟执行一次吸血检测
*/5 * * * * php /path/to/your/website/services/vampire_check.php > /dev/null 2>&1
```

如果您希望保存日志，可以使用：

```
# 每5分钟执行一次吸血检测，并保存日志
*/5 * * * * php /path/to/your/website/services/vampire_check.php > /path/to/your/logs/vampire_check.log 2>&1
```

### 步骤 3: 保存并退出

保存文件并退出编辑器。crontab 将自动安装新的定时任务。

## Windows 环境设置

在 Windows 系统中，您可以使用任务计划程序来设置定时任务。

### 步骤 1: 打开任务计划程序

1. 按下 `Win + R` 打开运行对话框
2. 输入 `taskschd.msc` 并按回车键

### 步骤 2: 创建基本任务

1. 在右侧面板中点击 "创建基本任务"
2. 输入任务名称，如 "吸血检测"，然后点击 "下一步"
3. 选择 "每天"，然后点击 "下一步"
4. 设置开始时间，然后点击 "下一步"
5. 选择 "启动程序"，然后点击 "下一步"
6. 在 "程序或脚本" 字段中输入 PHP 的完整路径，例如：`C:\php\php.exe`
7. 在 "添加参数" 字段中输入脚本的完整路径，例如：`D:\phpstudy_pro\WWW\services\vampire_check.php`
8. 点击 "下一步"，然后点击 "完成"

### 步骤 3: 修改任务设置

1. 在任务计划程序中找到刚刚创建的任务
2. 右键点击任务，选择 "属性"
3. 切换到 "触发器" 选项卡
4. 选择触发器，然后点击 "编辑"
5. 选择 "重复任务间隔"，设置为 "5 分钟"，持续时间设置为 "无限期"
6. 点击 "确定" 保存更改

## XAMPP/WAMP 环境设置

如果您使用的是 XAMPP 或 WAMP 等本地开发环境，您可以使用以下方法：

### XAMPP

1. 创建一个批处理文件 (vampire_check.bat)，内容如下：

```bat
@echo off
"C:\xampp\php\php.exe" -f "C:\xampp\htdocs\services\vampire_check.php"
```

2. 使用 Windows 任务计划程序设置定时运行此批处理文件（参考上面的 Windows 环境设置）

### WAMP

1. 创建一个批处理文件 (vampire_check.bat)，内容如下：

```bat
@echo off
"C:\wamp64\bin\php\php7.4.9\php.exe" -f "C:\wamp64\www\services\vampire_check.php"
```

2. 使用 Windows 任务计划程序设置定时运行此批处理文件（参考上面的 Windows 环境设置）

## PHPStudy 环境设置

如果您使用的是 PHPStudy，您可以使用以下方法：

1. 创建一个批处理文件 (vampire_check.bat)，内容如下：

```bat
@echo off
"D:\phpstudy_pro\Extensions\php\php7.4.3nts\php.exe" -f "D:\phpstudy_pro\WWW\services\vampire_check.php"
```

2. 使用 Windows 任务计划程序设置定时运行此批处理文件（参考上面的 Windows 环境设置）

## 验证定时任务是否正常工作

设置完成后，您可以通过以下方法验证定时任务是否正常工作：

1. 手动运行脚本，确认没有错误：
   - 在浏览器中访问 `http://your-website/services/vampire_check.php`
   - 或者在命令行中运行 `php /path/to/your/website/services/vampire_check.php`

2. 检查日志文件（如果您配置了日志输出）

3. 在吸血管理界面中查看是否有新的检测记录和封禁记录

## 常见问题

### 脚本没有执行

- 确保 PHP 路径正确
- 确保脚本路径正确
- 检查 PHP 是否有足够的权限执行脚本
- 检查 PHP 是否安装了所需的扩展（PDO, cURL 等）

### 脚本执行但没有检测到吸血行为

- 确保数据库连接正确
- 确保下载器配置正确
- 检查吸血检测的阈值设置是否合理
- 确保有活跃的种子和连接的 Peers

### 脚本执行但出现错误

- 检查 PHP 错误日志
- 尝试在浏览器中直接访问脚本，查看错误信息
- 确保数据库表结构正确

## 调整检测频率和参数

您可以在管理界面中调整以下参数来优化吸血检测：

- `vampire_enabled`: 启用/禁用吸血检测功能
- `vampire_refresh_interval`: 刷新间隔（秒）
- `vampire_ban_duration`: 封禁持续时间（秒）
- `vampire_min_ratio`: 最小上传/下载比例
- `vampire_min_upload`: 最小上传量（字节）
- `vampire_check_interval`: 检查间隔（秒）
- `vampire_ban_threshold`: 封禁阈值（次数）

## 结论

通过设置定时任务，您的吸血检测系统将能够自动运行，定期检测和封禁吸血行为。根据您的实际需求，您可以调整检测频率和参数，以获得最佳效果。 