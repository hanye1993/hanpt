# Cron 任务设置指南

本文档将指导您如何设置 cron 任务，用于定期执行自动获取和应用封禁规则的脚本。

## 前提条件

- 您需要有服务器的 shell 访问权限
- 您需要有设置 cron 任务的权限
- PHP 需要安装并配置好

## 设置 cron 任务

### 1. 编辑 crontab

在终端中执行以下命令来编辑 crontab：

```bash
crontab -e
```

### 2. 添加 cron 任务

在打开的编辑器中，添加以下行：

```
# 每小时执行一次自动获取和应用封禁规则的脚本
0 * * * * php /path/to/your/website/services/auto_fetch_ban_rules.php >> /path/to/your/website/logs/cron_auto_fetch_ban_rules.log 2>&1
```

请将 `/path/to/your/website` 替换为您网站的实际路径。

### 3. 保存并退出

保存文件并退出编辑器。cron 任务将自动生效。

## 验证 cron 任务

您可以通过以下命令查看当前设置的 cron 任务：

```bash
crontab -l
```

## 调整执行频率

上述设置将使脚本每小时执行一次。您可以根据需要调整执行频率。以下是一些常见的 cron 表达式示例：

- 每天凌晨执行一次：`0 0 * * *`
- 每 6 小时执行一次：`0 */6 * * *`
- 每 30 分钟执行一次：`*/30 * * * *`

## 注意事项

- 脚本会根据系统设置中的 `vampire_auto_fetch_rules` 和 `vampire_rules_fetch_interval` 参数来决定是否执行和执行的频率。
- 即使 cron 任务设置为每小时执行一次，如果 `vampire_rules_fetch_interval` 设置为 86400 秒（1 天），脚本也只会每天执行一次实际的获取和应用操作。
- 脚本执行日志将保存在网站的 `logs` 目录下。

## 故障排除

如果 cron 任务未按预期执行，请检查：

1. cron 服务是否正在运行
2. PHP 路径是否正确
3. 脚本路径是否正确
4. 脚本是否有执行权限
5. 日志目录是否存在且可写

您可以通过查看 `/var/log/syslog` 或 `/var/log/cron` 文件来检查 cron 任务的执行情况。 