-- =============================================================================
-- 01_create_users_and_privileges.sql · 三套数据库账号与权限隔离
-- -----------------------------------------------------------------------------
-- 设计依据：docs/06-后台隔离与权限设计.md §二
-- 核心原则：
--   1. 客户端（app_client）与用户后台（app_console）账号 **永远拿不到 sys_ 表权限**
--   2. 只有总后台（app_admin）可访问 sys_configs / sys_admins / sys_logs 等系统表
--   3. 从数据层阻断越权读取密钥，而非仅靠应用层判断
--
-- 执行身份：MySQL root / DBA
-- 执行时机：跑完 migration 之后执行（表已存在再授权更直观）
-- =============================================================================

-- -----------------------------------------------------------------------------
-- ⚠️ 执行前必须替换以下三个占位密码，且三套密码必须互不相同
--    建议使用 24 位以上随机字符串，并单独保存在密码管理器中
-- -----------------------------------------------------------------------------
SET @pwd_client  = 'REPLACE_WITH_CLIENT_PASSWORD';
SET @pwd_console = 'REPLACE_WITH_CONSOLE_PASSWORD';
SET @pwd_admin   = 'REPLACE_WITH_ADMIN_PASSWORD';

-- =============================================================================
-- 账号一：app_client · 客户端 API 专用（api.xxx.com）
-- 登录主机：建议限制为应用服务器内网 IP，而非 '%'
-- =============================================================================
CREATE USER IF NOT EXISTS 'app_client'@'%' IDENTIFIED BY @pwd_client;

-- 业务表读写权限
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`user_%`     TO 'app_client'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`bank_%`     TO 'app_client'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`question_%` TO 'app_client'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`exam_%`     TO 'app_client'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`order_%`    TO 'app_client'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`file_%`     TO 'app_client'@'%';
GRANT SELECT                          ON `anbiguo`.`content_%` TO 'app_client'@'%';
-- ❌ 不授予 sys_% 任何权限

-- =============================================================================
-- 账号二：app_console · 用户电脑端后台专用（console.xxx.com）
-- 权限范围与客户端一致，同样排除 sys_%
-- =============================================================================
CREATE USER IF NOT EXISTS 'app_console'@'%' IDENTIFIED BY @pwd_console;

GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`user_%`     TO 'app_console'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`bank_%`     TO 'app_console'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`question_%` TO 'app_console'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`exam_%`     TO 'app_console'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`order_%`    TO 'app_console'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `anbiguo`.`file_%`     TO 'app_console'@'%';
GRANT SELECT                          ON `anbiguo`.`content_%` TO 'app_console'@'%';
-- ❌ 不授予 sys_% 任何权限

-- =============================================================================
-- 账号三：app_admin · 总后台专用（admin.xxx.com，独立部署）
-- 唯一可访问 sys_ 表的账号
-- =============================================================================
CREATE USER IF NOT EXISTS 'app_admin'@'%' IDENTIFIED BY @pwd_admin;

GRANT ALL PRIVILEGES ON `anbiguo`.* TO 'app_admin'@'%';

-- =============================================================================
-- 权限刷新与校验
-- =============================================================================
FLUSH PRIVILEGES;

-- -----------------------------------------------------------------------------
-- 校验一：app_client / app_console 对 sys_ 表应当无任何权限记录
-- -----------------------------------------------------------------------------
SELECT `GRANTEE`, `TABLE_SCHEMA`, `TABLE_NAME`, `PRIVILEGE_TYPE`
FROM `information_schema`.`TABLE_PRIVILEGES`
WHERE `TABLE_SCHEMA` = 'anbiguo'
  AND `TABLE_NAME` LIKE 'sys\_%'
ORDER BY `GRANTEE`, `TABLE_NAME`;
-- 预期：结果集中不应出现 app_client / app_console

-- -----------------------------------------------------------------------------
-- 校验二：schema 级授权清单（应只看到 app_admin）
-- -----------------------------------------------------------------------------
SELECT `GRANTEE`, `PRIVILEGE_TYPE`
FROM `information_schema`.`SCHEMA_PRIVILEGES`
WHERE `TABLE_SCHEMA` = 'anbiguo';

-- -----------------------------------------------------------------------------
-- 校验三：查看各账号最终权限
-- -----------------------------------------------------------------------------
-- SHOW GRANTS FOR 'app_client'@'%';
-- SHOW GRANTS FOR 'app_console'@'%';
-- SHOW GRANTS FOR 'app_admin'@'%';

-- =============================================================================
-- 生产环境加固建议（按需启用）
-- =============================================================================
-- 1. 将 '%' 收窄为应用服务器内网网段，例如 'app_client'@'10.0.1.%'
-- 2. 关闭客户端的 DDL 权限（本脚本已通过逐表 GRANT 天然实现）
-- 3. 开启 MySQL 审计插件或将慢查询日志与 sys_logs 交叉比对
-- 4. 密码轮换：每 90 天更换三套账号密码，并同步更新各自 .env
