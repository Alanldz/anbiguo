<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * 系统域初始化数据：权限点 / 角色 / 管理员 / 配置中心配置项
 *
 * 幂等：使用 updateOrInsert，可重复执行
 * 配置项登记来源：docs/04-API接口规范与登记表.md §六
 */
class SystemInitSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ------------------------------------------------------------------
        // 1. 权限点（code 格式：模块:资源:动作）
        // ------------------------------------------------------------------
        $permissions = [
            // 顶级菜单
            ['code' => 'sys:dashboard',        'name' => '数据看板',   'parent' => 0, 'type' => 1, 'route' => '/dashboard',       'icon' => 'dashboard',  'sort' => 10],
            ['code' => 'sys:user',             'name' => '用户管理',   'parent' => 0, 'type' => 1, 'route' => '/users',           'icon' => 'user',       'sort' => 20],
            ['code' => 'bank:question-bank',   'name' => '题库管理',   'parent' => 0, 'type' => 1, 'route' => '/question-banks',  'icon' => 'collection', 'sort' => 30],
            ['code' => 'bank:audit',           'name' => '内容审核',   'parent' => 0, 'type' => 1, 'route' => '/audit',           'icon' => 'audit',      'sort' => 40],
            ['code' => 'bank:category',        'name' => '分类管理',   'parent' => 0, 'type' => 1, 'route' => '/categories',      'icon' => 'tag',        'sort' => 50],
            ['code' => 'content:banner',       'name' => '运营管理',   'parent' => 0, 'type' => 1, 'route' => '/banners',         'icon' => 'image',      'sort' => 60],
            ['code' => 'order:order',          'name' => '订单与交易', 'parent' => 0, 'type' => 1, 'route' => '/orders',          'icon' => 'money',      'sort' => 70],
            ['code' => 'file:asset',           'name' => '文件管理',   'parent' => 0, 'type' => 1, 'route' => '/files',           'icon' => 'folder',     'sort' => 80],
            ['code' => 'sys:config',           'name' => '配置中心',   'parent' => 0, 'type' => 1, 'route' => '/configs',         'icon' => 'setting',    'sort' => 90],
            ['code' => 'sys:system',           'name' => '系统管理',   'parent' => 0, 'type' => 1, 'route' => '/system',          'icon' => 'shield',     'sort' => 100],
            ['code' => 'content:feedback',     'name' => '意见反馈',   'parent' => 0, 'type' => 1, 'route' => '/feedbacks',       'icon' => 'message',    'sort' => 110],

            // 按钮 / 数据级权限点
            ['code' => 'sys:statistics:view',   'name' => '查看看板',     'parent' => 'sys:dashboard',      'type' => 2],
            ['code' => 'sys:user:list',         'name' => '用户列表',     'parent' => 'sys:user',           'type' => 2],
            ['code' => 'sys:user:update',       'name' => '编辑用户',     'parent' => 'sys:user',           'type' => 2],
            ['code' => 'sys:user:ban',          'name' => '封禁用户',     'parent' => 'sys:user',           'type' => 2],
            ['code' => 'sys:user:member',       'name' => '调整会员',     'parent' => 'sys:user',           'type' => 2],
            ['code' => 'bank:question-bank:list',   'name' => '题库列表', 'parent' => 'bank:question-bank', 'type' => 2],
            ['code' => 'bank:question-bank:create', 'name' => '新建题库', 'parent' => 'bank:question-bank', 'type' => 2],
            ['code' => 'bank:question-bank:update', 'name' => '编辑题库', 'parent' => 'bank:question-bank', 'type' => 2],
            ['code' => 'bank:question-bank:delete', 'name' => '删除题库', 'parent' => 'bank:question-bank', 'type' => 2],
            ['code' => 'bank:question-bank:audit',  'name' => '审核题库', 'parent' => 'bank:audit',         'type' => 2],
            ['code' => 'bank:question:list',    'name' => '题目管理',     'parent' => 'bank:question-bank', 'type' => 2],
            ['code' => 'bank:report:handle',    'name' => '处理试题报错', 'parent' => 'bank:audit',         'type' => 2],
            ['code' => 'bank:category:list',    'name' => '分类列表',     'parent' => 'bank:category',      'type' => 2],
            ['code' => 'bank:category:update',  'name' => '维护分类',     'parent' => 'bank:category',      'type' => 2],
            ['code' => 'content:banner:list',   'name' => '运营位列表',   'parent' => 'content:banner',     'type' => 2],
            ['code' => 'content:banner:update', 'name' => '维护运营位',   'parent' => 'content:banner',     'type' => 2],
            ['code' => 'order:order:list',      'name' => '订单列表',     'parent' => 'order:order',        'type' => 2],
            ['code' => 'order:order:refund',    'name' => '订单退款',     'parent' => 'order:order',        'type' => 2],
            ['code' => 'order:plan:update',     'name' => '会员套餐配置', 'parent' => 'order:order',        'type' => 2],
            ['code' => 'file:asset:list',       'name' => '文件列表',     'parent' => 'file:asset',         'type' => 2],
            ['code' => 'file:asset:delete',     'name' => '删除文件',     'parent' => 'file:asset',         'type' => 2],
            ['code' => 'sys:config:view',       'name' => '查看配置',     'parent' => 'sys:config',         'type' => 2],
            ['code' => 'sys:config:update',     'name' => '修改配置',     'parent' => 'sys:config',         'type' => 2],
            ['code' => 'sys:config:test',       'name' => '测试连通性',   'parent' => 'sys:config',         'type' => 2],
            ['code' => 'sys:admin:list',        'name' => '管理员列表',   'parent' => 'sys:system',         'type' => 2],
            ['code' => 'sys:admin:update',      'name' => '管理员维护',   'parent' => 'sys:system',         'type' => 2],
            ['code' => 'sys:role:update',       'name' => '角色权限',     'parent' => 'sys:system',         'type' => 2],
            ['code' => 'sys:log:list',          'name' => '操作日志',     'parent' => 'sys:system',         'type' => 2],
            ['code' => 'content:feedback:list', 'name' => '反馈列表',     'parent' => 'content:feedback',   'type' => 2],
            ['code' => 'content:feedback:handle', 'name' => '处理反馈',   'parent' => 'content:feedback',   'type' => 2],
        ];

        // 先落父级再落子级，保证 parent_id 能查到
        $codeToId = [];
        foreach ($permissions as $row) {
            if ($row['parent'] === 0) {
                $id = $this->upsertPermission($row, 0, $now);
                $codeToId[$row['code']] = $id;
            }
        }
        foreach ($permissions as $row) {
            if ($row['parent'] !== 0) {
                $parentId = $codeToId[$row['parent']] ?? 0;
                $id = $this->upsertPermission($row, $parentId, $now);
                $codeToId[$row['code']] = $id;
            }
        }

        // ------------------------------------------------------------------
        // 2. 预置角色
        // ------------------------------------------------------------------
        $roles = [
            ['code' => 'super_admin', 'name' => '超级管理员', 'description' => '拥有系统全部权限，不可删除', 'is_system' => 1, 'sort' => 1],
            ['code' => 'operation',   'name' => '运营',       'description' => '内容与运营管理，无系统配置权限', 'is_system' => 1, 'sort' => 2],
            ['code' => 'customer_service', 'name' => '客服',  'description' => '用户与订单查询为主，只读', 'is_system' => 1, 'sort' => 3],
            ['code' => 'finance',     'name' => '财务',       'description' => '订单与对账', 'is_system' => 1, 'sort' => 4],
        ];
        foreach ($roles as $role) {
            DB::table('sys_roles')->updateOrInsert(
                ['code' => $role['code']],
                [
                    'name'        => $role['name'],
                    'description' => $role['description'],
                    'is_system'   => $role['is_system'],
                    'sort_order'  => $role['sort'],
                    'status'      => 1,
                    'updated_at'  => $now,
                    'created_at'  => $now,
                ]
            );
        }

        // 运营角色授权
        $operationPermCodes = [
            'sys:dashboard', 'sys:statistics:view',
            'bank:question-bank', 'bank:question-bank:list', 'bank:question-bank:create',
            'bank:question-bank:update', 'bank:question-bank:delete', 'bank:question:list',
            'bank:audit', 'bank:question-bank:audit', 'bank:report:handle',
            'bank:category', 'bank:category:list', 'bank:category:update',
            'content:banner', 'content:banner:list', 'content:banner:update',
            'content:feedback', 'content:feedback:list', 'content:feedback:handle',
            'file:asset', 'file:asset:list',
        ];
        $this->grantRole('operation', $operationPermCodes, $codeToId, $now);

        // 客服角色授权
        $this->grantRole('customer_service', [
            'sys:dashboard', 'sys:statistics:view',
            'sys:user', 'sys:user:list',
            'order:order', 'order:order:list',
            'content:feedback', 'content:feedback:list', 'content:feedback:handle',
        ], $codeToId, $now);

        // 财务角色授权
        $this->grantRole('finance', [
            'sys:dashboard', 'sys:statistics:view',
            'order:order', 'order:order:list', 'order:order:refund', 'order:plan:update',
        ], $codeToId, $now);

        // 超级管理员角色：授予全部权限
        $this->grantRole('super_admin', array_keys($codeToId), $codeToId, $now);

        // ------------------------------------------------------------------
        // 3. 初始管理员（用户名 admin，初始密码取 env ADMIN_INIT_PASSWORD，未配置则 Admin@123456）
        //    ⚠️ 首次登录后必须立即修改密码
        // ------------------------------------------------------------------
        $adminExists = DB::table('sys_admins')->where('username', 'admin')->exists();
        if (! $adminExists) {
            $adminId = DB::table('sys_admins')->insertGetId([
                'username'   => 'admin',
                'password'   => Hash::make(env('ADMIN_INIT_PASSWORD', 'Admin@123456')),
                'real_name'  => '系统管理员',
                'is_super'   => 1,
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $adminId = DB::table('sys_admins')->where('username', 'admin')->value('id');
        }

        $superRoleId = DB::table('sys_roles')->where('code', 'super_admin')->value('id');
        if ($adminId && $superRoleId) {
            DB::table('sys_admin_roles')->updateOrInsert(
                ['admin_id' => $adminId, 'role_id' => $superRoleId],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        // ------------------------------------------------------------------
        // 4. 配置中心配置项（全部 KEY 后台可视化修改）
        // ------------------------------------------------------------------
        $configs = [
            // 短信
            ['sms', 'sms.provider',      '1',             1, 0, '短信服务商',   '1=阿里云 2=腾讯云', 10],
            ['sms', 'sms.access_key',    '',              1, 1, 'AccessKey',    '短信平台 AccessKey ID', 20],
            ['sms', 'sms.secret',        '',              1, 1, 'AccessKeySecret', '短信平台密钥', 30],
            ['sms', 'sms.sign_name',     '',              1, 0, '短信签名',     '已在运营商备案的签名', 40],
            ['sms', 'sms.template_code', '',              1, 0, '验证码模板Code', '登录验证码短信模板 ID', 50],
            // 微信
            ['wechat', 'wechat.mp_app_id',  '', 1, 0, '小程序 AppID',  '微信公众平台小程序 AppID', 10],
            ['wechat', 'wechat.mp_secret',  '', 1, 1, '小程序 Secret', '微信公众平台小程序密钥', 20],
            // 支付
            ['payment', 'payment.mch_id',     '', 1, 0, '商户号',        '微信支付商户号 MchID', 10],
            ['payment', 'payment.api_key',    '', 1, 1, 'APIv3 密钥',    '微信支付 APIv3 密钥', 20],
            ['payment', 'payment.cert_path',  '', 1, 0, '商户证书路径',  'apiclient_cert.pem 绝对路径', 30],
            ['payment', 'payment.notify_url', '', 1, 0, '支付回调地址',  '需公网可访问，如 https://api.xxx.com/api/v1/pay/wechat/notify', 40],
            // 对象存储（七牛云 Kodo）
            ['storage', 'storage.provider',    'qiniu', 1, 0, '存储服务商',   'qiniu / aliyun / local，切换后业务代码无需改动', 10],
            ['storage', 'storage.access_key',  '',      1, 1, 'AccessKey',    '七牛云 AccessKey', 20],
            ['storage', 'storage.secret_key',  '',      1, 1, 'SecretKey',    '七牛云 SecretKey', 30],
            ['storage', 'storage.bucket',      '',      1, 0, '存储空间名称', 'Bucket 名称', 40],
            ['storage', 'storage.domain',      '',      1, 0, '访问域名',     'CDN 加速域名，含 https://', 50],
            ['storage', 'storage.region',      'z0',    1, 0, '存储区域',     'z0=华东 z1=华北 z2=华南 na0=北美', 60],
            ['storage', 'storage.token_expire', '900',  2, 0, '上传凭证有效期', '单位秒，默认 900（15 分钟）', 70],
            ['storage', 'storage.private_bucket', '',   1, 0, '私有空间名称', '私有文件走签名 URL 的空间', 80],
            // AI
            ['ai', 'ai.provider',     '1',        1, 0, 'AI 服务商',   '1=自定义兼容接口 2=其他', 10],
            ['ai', 'ai.api_key',      '',         1, 1, 'API Key',     '大模型服务密钥', 20],
            ['ai', 'ai.model',        '',         1, 0, '模型名称',     '如 gpt-4o-mini / qwen-plus', 30],
            ['ai', 'ai.base_url',     '',         1, 0, '接口地址',     '兼容 OpenAI 协议的 Base URL', 40],
            ['ai', 'ai.daily_quota',  '0',        2, 0, '每日免费额度', '普通用户每日 AI 导题次数，0=不限制', 50],
            // OCR
            ['ocr', 'ocr.provider', '1', 1, 0, 'OCR 服务商', '1=阿里云 2=腾讯云', 10],
            ['ocr', 'ocr.app_id',   '',  1, 0, 'AppID',     'OCR 应用 ID', 20],
            ['ocr', 'ocr.secret',   '',  1, 1, 'Secret',    'OCR 应用密钥', 30],
            // 站点
            ['site', 'site.name',              '识途刷题', 1, 0, '站点名称',     '客户端显示名称', 10],
            ['site', 'site.logo',              '',          1, 0, '站点 Logo',    '图片地址', 20],
            ['site', 'site.icp_no',            '',          1, 0, 'ICP 备案号',   '页面底部展示', 30],
            ['site', 'site.customer_service',  '',          1, 0, '客服微信',     '展示在「我的」页', 40],
            ['site', 'site.share_title',       '',          1, 0, '默认分享标题', '未配置时使用站点名称', 50],
            ['site', 'site.client_min_version', '1.0.0',    1, 0, '客户端最低版本', '低于该版本强制升级', 60],
        ];

        foreach ($configs as $c) {
            DB::table('sys_configs')->updateOrInsert(
                ['group_code' => $c[0], 'config_key' => $c[1]],
                [
                    'config_value' => $c[2],
                    'value_type'   => $c[3],
                    'is_secret'    => $c[4],
                    'title'        => $c[5],
                    'remark'       => $c[6],
                    'is_system'    => 1,
                    'sort_order'   => $c[7],
                    'status'       => 1,
                    'updated_at'   => $now,
                    'created_at'   => $now,
                ]
            );
        }
    }

    /**
     * 写入单个权限点，返回权限 ID
     */
    private function upsertPermission(array $row, int $parentId, $now): int
    {
        DB::table('sys_permissions')->updateOrInsert(
            ['code' => $row['code']],
            [
                'parent_id'   => $parentId,
                'name'        => $row['name'],
                'type'        => $row['type'],
                'route_path'  => $row['route'] ?? '',
                'icon'        => $row['icon'] ?? '',
                'sort_order'  => $row['sort'] ?? 0,
                'status'      => 1,
                'updated_at'  => $now,
                'created_at'  => $now,
            ]
        );

        return (int) DB::table('sys_permissions')->where('code', $row['code'])->value('id');
    }

    /**
     * 为角色授予权限点
     */
    private function grantRole(string $roleCode, array $permCodes, array $codeToId, $now): void
    {
        $roleId = DB::table('sys_roles')->where('code', $roleCode)->value('id');
        if (! $roleId) {
            return;
        }

        // 先清空该角色的权限，保证幂等且与本次配置一致
        DB::table('sys_role_permissions')->where('role_id', $roleId)->delete();

        $rows = [];
        foreach ($permCodes as $code) {
            if (empty($codeToId[$code])) {
                continue;
            }
            $rows[] = [
                'role_id'       => $roleId,
                'permission_id' => $codeToId[$code],
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        if ($rows) {
            DB::table('sys_role_permissions')->insert($rows);
        }
    }
}
