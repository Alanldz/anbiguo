#!/usr/bin/env bash
# =============================================================================
# 服务器端初始化脚本（在宝塔 Linux 服务器上执行）
# 用法：
#   bash deploy/scripts/server-init.sh server   # 初始化主应用（api + console-api）
#   bash deploy/scripts/server-init.sh admin    # 初始化总后台（admin/api）
# 前提：代码已克隆到 /www/wwwroot/anbiguo；MySQL 已建库并导入 sql/ 脚本；
#      PHP 8.3 + Composer 已就绪（docs/08 §1.2/§1.3）。
# =============================================================================
set -euo pipefail

APP="/www/wwwroot/anbiguo"
TARGET="${1:-}"
PHP_BIN="$(command -v php || echo /www/server/php/83/bin/php)"
COMPOSER="$(command -v composer || echo /usr/bin/composer)"

case "$TARGET" in
  server)
    DIR="$APP/server"
    ;;
  admin)
    DIR="$APP/admin/api"
    ;;
  *)
    echo "用法: bash $0 server|admin"; exit 1
    ;;
esac

echo "==> 目标应用: $TARGET ($DIR)"

cd "$DIR"

# 1) 依赖安装（生产依赖，不装 dev）
echo "==> composer install --no-dev"
"$COMPOSER" install --no-dev --optimize-autoloader --no-interaction

# 2) .env 准备（不存在则从模板复制，内容自行填写后再重跑本脚本）
if [ ! -f .env ]; then
  cp .env.example .env
  echo "!! 已生成 .env（从 .env.example），请先填写数据库/Redis/JWT 等配置，再重新执行本脚本"
  exit 2
fi

# 3) APP_KEY（server 首次生成；admin 不生成——必须手动与 server 保持一致）
if [ "$TARGET" = "server" ] && ! grep -q '^APP_KEY=base64' .env; then
  echo "==> 生成 APP_KEY"
  "$PHP_BIN" artisan key:generate --force
fi

# 4) 迁移与初始化数据（用 app_admin 账号跑 DDL，环境变量方式临时注入）
if [ "$TARGET" = "server" ]; then
  echo "==> migrate + seed（需输入 app_admin 密码，见提示）"
  read -r -p "app_admin 数据库密码: " ADMIN_PWD
  DB_USERNAME=app_admin DB_PASSWORD="$ADMIN_PWD" "$PHP_BIN" artisan migrate --force
  DB_USERNAME=app_admin DB_PASSWORD="$ADMIN_PWD" "$PHP_BIN" artisan db:seed --force
  echo "!! 迁移完成。请把 .env 的 DB_USERNAME/DB_PASSWORD 回填为 app_client + 对应密码"
fi

# 5) 目录权限与软链
echo "==> 权限与 storage:link"
chown -R www:www storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache
"$PHP_BIN" artisan storage:link || true

# 6) 生产优化（配置/路由缓存；CORS/自定义配置走 ConfigCenter，不需要 config:clear）
echo "==> 优化缓存"
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan event:cache 2>/dev/null || true
chown -R www:www bootstrap/cache 2>/dev/null || true

echo "==> $TARGET 初始化完成 ✅"
[ "$TARGET" = "server" ] && echo ">> 记得：① .env 回填 app_client 账号 ② 修改 ADMIN_INIT_PASSWORD 后删除该行 ③ 添加宝塔计划任务 schedule:run"
[ "$TARGET" = "admin" ] && echo ">> 记得：APP_KEY / CACHE_PREFIX 必须与 server/.env 完全一致；JWT_SECRET_ADMIN 独立生成"
