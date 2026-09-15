-- =============================================================================
-- 00_create_database.sql · 创建数据库
-- -----------------------------------------------------------------------------
-- 执行身份：MySQL root / DBA
-- 执行时机：首次部署，在跑 migration 之前
-- 幂等：可重复执行
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `anbiguo`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

-- MySQL 8.0 默认认证插件确认（应用侧连接使用 mysql_native_password 或 caching_sha2_password 均可）
-- SHOW VARIABLES LIKE 'character_set_server';
-- SHOW VARIABLES LIKE 'collation_server';
