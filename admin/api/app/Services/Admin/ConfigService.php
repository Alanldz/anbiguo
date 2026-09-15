<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\SysConfig;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * 配置中心服务（docs/04 §五 API-ADM-CFG-*）
 *
 * ⚠️ 缓存键必须与主应用一致：`sys_config:group:{group}`
 *   总后台修改配置后 Cache::forget 该键，主应用读取配置时即可立即生效（docs/04 §2.2）。
 */
class ConfigService
{
    /** 配置缓存键前缀（与主应用共享） */
    private const CACHE_KEY_PREFIX = 'sys_config:group:';

    /** 配置分组清单（与 SystemInitSeeder 对齐） */
    private const GROUPS = ['sms', 'wechat', 'payment', 'storage', 'ai', 'ocr', 'site'];

    /** 列出配置（API-ADM-040）：group 为空返回全部分组 + 全部配置 */
    public function list(?string $group): array
    {
        if ($group === null || $group === '') {
            $configs = SysConfig::query()->orderBy('group_code')->orderBy('sort_order')->get();
            $items = $configs->map(fn ($c) => $this->toArray($c))->all();

            return [
                'groups' => self::GROUPS,
                'items'  => $items,
            ];
        }

        $configs = SysConfig::where('group_code', $group)
            ->orderBy('sort_order')
            ->get();

        return [
            'group' => $group,
            'items' => $configs->map(fn ($c) => $this->toArray($c))->all(),
        ];
    }

    /** 修改配置（API-ADM-041） */
    public function update(int $id, string $value): array
    {
        /** @var SysConfig|null $config */
        $config = SysConfig::find($id);

        if ($config === null) {
            throw new BusinessException(ErrorCode::CONFIG_NOT_FOUND, '配置项不存在', null, 404);
        }

        // 敏感配置：用 Crypt 加密存储（与主应用共享 APP_KEY 解密）
        $stored = $config->is_secret === SysConfig::IS_SECRET
            ? Crypt::encryptString($value)
            : $value;

        $ok = $config->update(['config_value' => $stored]);
        if (! $ok) {
            throw new BusinessException(ErrorCode::CONFIG_SAVE_FAILED, '配置保存失败', null, 500);
        }

        // 清缓存：让主应用立即生效（键名必须一致）
        Cache::forget(self::CACHE_KEY_PREFIX.$config->group_code);

        return $this->toArray($config->fresh());
    }

    /**
     * 配置连通性探测（API-ADM-101 / sys:config:test）
     *
     * 读 sys_configs 该条配置，按 key/type 做轻量连通性探测（域名 ping / TCP 端口探测等），
     * 超时 ≤5s。第三方账号未接入属常态，探测失败不是异常——仅返回 ok/message/latency_ms。
     *
     * @return array{ok: bool, message: string, latency_ms: int}
     */
    public function test(int $id): array
    {
        /** @var SysConfig|null $config */
        $config = SysConfig::find($id);
        if ($config === null) {
            throw new BusinessException(ErrorCode::CONFIG_NOT_FOUND, '配置项不存在', null, 404);
        }

        // 敏感配置（如密钥/商户号）密文不可读，无法做有意义的连通探测
        if ($config->is_secret === SysConfig::IS_SECRET) {
            return [
                'ok'         => false,
                'message'    => '敏感配置（密钥/商户号）已加密，无法探测，请确认配置已正确填写',
                'latency_ms' => 0,
            ];
        }

        $value = (string) $config->config_value;
        $probe = $this->resolveProbe($config->config_key, $config->group_code, $value);

        if ($probe === null) {
            return [
                'ok'         => false,
                'message'    => '未识别到可探测的主机地址（配置值应为 URL 或 域名[:端口]）',
                'latency_ms' => 0,
            ];
        }

        $start = microtime(true);
        $errno = 0;
        $errstr = '';
        // 轻量 TCP 连通探测，超时 5s（非阻塞连接超时，不等业务响应）
        $fp = @fsockopen($probe['host'], $probe['port'], $errno, $errstr, 5.0);
        $latency = (int) round((microtime(true) - $start) * 1000);

        if ($fp === false) {
            return [
                'ok'         => false,
                'message'    => sprintf('连接失败：%s（%s:%d）', $errstr !== '' ? $errstr : '超时', $probe['host'], $probe['port']),
                'latency_ms' => $latency,
            ];
        }

        fclose($fp);

        return [
            'ok'         => true,
            'message'    => sprintf('连接成功，耗时 %dms（%s:%d）', $latency, $probe['host'], $probe['port']),
            'latency_ms' => $latency,
        ];
    }

    /**
     * 从配置键/分组/值中推导探测目标（host + port）
     *
     * 支持：完整 URL（http/https）、host:port、纯域名（按分组给默认端口）。
     *
     * @return array{host: string, port: int}|null
     */
    private function resolveProbe(string $key, string $group, string $value): ?array
    {
        $raw = trim($value);
        if ($raw === '') {
            $raw = trim($key);
        }

        // 1) 完整 URL
        if (preg_match('#^https?://#i', $raw)) {
            $parts = parse_url($raw);
            if (! isset($parts['host'])) {
                return null;
            }
            $port = $parts['port'] ?? (strcasecmp($parts['scheme'] ?? '', 'https') === 0 ? 443 : 80);

            return ['host' => $parts['host'], 'port' => (int) $port];
        }

        // 2) host:port
        if (preg_match('#^([a-zA-Z0-9.\-]+):(\d{1,5})$#', $raw, $m)) {
            return ['host' => $m[1], 'port' => (int) $m[2]];
        }

        // 3) 纯域名 + 分组默认端口
        if (preg_match('#^[a-zA-Z0-9.\-]+$#', $raw)) {
            $port = match ($group) {
                'payment' => 443,
                'wechat'  => 443,
                'sms'     => 443,
                'storage' => 443,
                'ai'      => 443,
                'ocr'     => 443,
                default   => 80,
            };

            return ['host' => $raw, 'port' => $port];
        }

        return null;
    }

    /** 对外结构：敏感项返回掩码值 */
    private function toArray(SysConfig $config): array
    {
        return [
            'id'          => $config->id,
            'group_code'  => $config->group_code,
            'config_key'  => $config->config_key,
            'value'       => $config->is_secret === SysConfig::IS_SECRET
                ? SysConfig::MASKED_VALUE
                : $config->config_value,
            'value_type'  => (int) $config->value_type,
            'is_secret'   => (int) $config->is_secret,
            'title'       => $config->title,
            'remark'      => $config->remark,
            'is_system'   => (int) $config->is_system,
            'sort_order'  => (int) $config->sort_order,
            'status'      => (int) $config->status,
        ];
    }
}
