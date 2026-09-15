<?php

declare(strict_types=1);

/**
 * 总后台安全配置（docs/06 §八 安全加固项）
 */
return [
    // ---------------------------------------------------------------------
    // 总后台访问 IP 白名单（API-ADM 全域，含登录接口）
    //
    //   - 空数组 = 白名单关闭，全部放行（默认，不影响部署）；
    //   - 非空时仅允许列表内 IP / CIDR 访问 /admin-api/v1，其余返回 403。
    //
    // 配置方式（.env，逗号分隔，支持单个 IP 或 CIDR 网段）：
    //   ADMIN_IP_WHITELIST=203.0.113.10,198.51.100.0/24
    //
    // ⚠️ 生产环境存在反向代理时，应同时配置框架可信代理（trustProxy），
    //    否则 X-Forwarded-For 可被伪造，白名单仅作纵深防御手段之一。
    // ---------------------------------------------------------------------
    'ip_whitelist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_IP_WHITELIST', ''))
    ))),
];
