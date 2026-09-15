<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * 总后台 IP 白名单（docs/04 §五、docs/06 §八 安全加固项）
 *
 * 白名单来源：config/admin.php 的 ip_whitelist（.env ADMIN_IP_WHITELIST 逗号分隔）。
 *   - 数组为空 = 白名单关闭，全部放行（默认，不影响现有部署）；
 *   - 非空时仅允许列表内 IP / CIDR（如 1.2.3.4、10.0.0.0/8）访问 /admin-api/v1。
 *
 * 取 IP 说明：$request->ips() 返回 X-Forwarded-For 展开后的候选 IP 链（最左为客户端声称的原始 IP）。
 * 本实现取链中第一个「公网」IP 作为判定依据（私网/回环候选视为代理层内部跳板）；
 * ⚠️ 生产环境存在反向代理时，应同步配置框架 trustProxy（TrustHosts/可信代理网段），
 *    否则 X-Forwarded-For 可被客户端伪造，白名单仅作为纵深防御手段之一。
 */
class AdminIpWhitelist
{
    /** 私网 / 回环网段（判定「公网 IP」时排除的候选） */
    private const PRIVATE_RANGES = [
        '127.0.0.0/8',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '169.254.0.0/16',
        '::1/128',
        'fc00::/7',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $whitelist = array_values(array_filter((array) config('admin.ip_whitelist', [])));

        // 白名单未开启：全部放行
        if ($whitelist === []) {
            return $next($request);
        }

        $clientIp = $this->resolveClientIp($request);

        if ($clientIp !== '' && IpUtils::checkIp($clientIp, $whitelist)) {
            return $next($request);
        }

        return ApiResponse::error(
            ErrorCode::NO_PERMISSION,
            '当前 IP 不在访问白名单内，请联系管理员',
            null,
            403
        );
    }

    /**
     * 从 IP 链中解析真实客户端 IP：取第一个不在私网/回环段的候选；全为内网时取最后一个候选
     */
    private function resolveClientIp(Request $request): string
    {
        $candidates = $request->ips();

        if ($candidates === []) {
            return $request->ip() ?? '';
        }

        foreach ($candidates as $ip) {
            if ($ip !== '' && ! IpUtils::checkIp($ip, self::PRIVATE_RANGES)) {
                return $ip;
            }
        }

        return (string) end($candidates);
    }
}
