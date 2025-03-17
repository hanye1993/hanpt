/*
Navicat MySQL Data Transfer

Source Server         : localhost_3306
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : download_manager

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2025-03-17 22:34:05
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for attendance
-- ----------------------------
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL COMMENT '站点ID',
  `status` tinyint(1) NOT NULL COMMENT '签到状态：0=失败，1=成功',
  `message` text COLLATE utf8mb4_unicode_ci COMMENT '签到消息',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for downloaders
-- ----------------------------
DROP TABLE IF EXISTS `downloaders`;
CREATE TABLE `downloaders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `domain` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `version` varchar(50) DEFAULT NULL COMMENT '下载器版本',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for download_history
-- ----------------------------
DROP TABLE IF EXISTS `download_history`;
CREATE TABLE `download_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL COMMENT '站点ID',
  `torrent_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '种子ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标题',
  `size` bigint(20) NOT NULL COMMENT '大小',
  `download_url` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '下载链接',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态：0=等待下载，1=下载中，2=下载完成，3=下载失败',
  `error` text COLLATE utf8mb4_unicode_ci COMMENT '错误信息',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `download_history_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for logs
-- ----------------------------
DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('site','error','operation') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1490 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for peer_bans
-- ----------------------------
DROP TABLE IF EXISTS `peer_bans`;
CREATE TABLE `peer_bans` (
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ban_time` timestamp NULL DEFAULT NULL,
  `unban_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `auto_unban_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `downloader_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for peer_checks
-- ----------------------------
DROP TABLE IF EXISTS `peer_checks`;
CREATE TABLE `peer_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `downloader_id` int(11) NOT NULL COMMENT '下载器ID',
  `torrent_hash` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '种子Hash',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Peer IP',
  `download_speed` bigint(20) NOT NULL DEFAULT '0' COMMENT '下载速度',
  `upload_speed` bigint(20) NOT NULL DEFAULT '0' COMMENT '上传速度',
  `is_banned` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已封禁',
  `check_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '检查时间',
  `vampire_ratio` float NOT NULL,
  PRIMARY KEY (`id`),
  KEY `downloader_id` (`downloader_id`),
  CONSTRAINT `peer_checks_ibfk_1` FOREIGN KEY (`downloader_id`) REFERENCES `downloaders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2975 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '设置名称',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '设置值',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for sites
-- ----------------------------
DROP TABLE IF EXISTS `sites`;
CREATE TABLE `sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `cookie` text,
  `rss_url` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for site_stats
-- ----------------------------
DROP TABLE IF EXISTS `site_stats`;
CREATE TABLE `site_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL COMMENT '站点ID',
  `upload` bigint(20) NOT NULL DEFAULT '0' COMMENT '上传量',
  `download` bigint(20) NOT NULL DEFAULT '0' COMMENT '下载量',
  `ratio` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '分享率',
  `bonus` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '魔力值',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `site_stats_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for torrents
-- ----------------------------
DROP TABLE IF EXISTS `torrents`;
CREATE TABLE `torrents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '种子Hash',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '种子名称',
  `size` bigint(20) NOT NULL DEFAULT '0' COMMENT '种子大小',
  `downloader_id` int(11) NOT NULL COMMENT '下载器ID',
  `added_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
  `last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后检测时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`),
  KEY `downloader_id` (`downloader_id`),
  CONSTRAINT `torrents_ibfk_1` FOREIGN KEY (`downloader_id`) REFERENCES `downloaders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
-- 插入默认管理员账户
INSERT INTO `users` (`username`, `password`, `email`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com')
ON DUPLICATE KEY UPDATE `id` = `id`;
