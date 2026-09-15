<?php

declare(strict_types=1);

namespace App\Services\Config;

use App\Exceptions\BusinessException;
use App\Models\SysConfig;
use App\Support\ErrorCode;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * 配置中心（docs/04-API接口规范与登记表.md §六）
 *
 * 职责：
 *   1. 从 sys_configs 读取短信 / 微信 / 支付 / 存储 / AI / OCR / 站点配置
 *   2. 敏感项（is_secret=1）在数据库中以 AES-256 密文存储，读取时自动解密
 *   3. 带进程级 + Redis 二级缓存，避免每个请求都查库
 *   4. 配置在总后台修改后，由 SysConfig 模型的 saved 事件清除缓存
 *
 * ⚠️ 严禁在业务代码中直接读取 env() 获取第三方 KEY，必须经由此类。
 *    env 仅用于「部署级」配置（数据库、Redis、JWT 密钥）。
 */
class ConfigCenter
{
    /** 进程内缓存：分组 => [键 => 值] */
    private static array $runtime = [];

    /** Redis 缓存前缀 */
    private const CACHE_KEY = 'sys_config:group:';

    /** Redis 缓存时长（秒） */
    private const CACHE_TTL = 600;

    /**
     * 读取一个配置值
     *
     * @param  string  $key      完整键，如 storage.access_key
     * @param  mixed   $default  缺省值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        [$group, $name] = $this->splitKey($key);

        $items = $this->group($group);

        return $items[$name] ?? $default;
    }

    /**
     * 读取一个配置值，缺失时抛业务异常（用于强依赖项）
     */
    public function getOrFail(string $key, int $errorCode = ErrorCode::STORAGE_NOT_CONFIGURED): mixed
    {
        $value = $this->get($key);

        if ($value === null || $value === '') {
            throw new BusinessException($errorCode, "配置项 {$key} 未配置，请在总后台配置中心填写");
        }

        return $value;
    }

    /**
     * 读取整组配置
     *
     * @return array<string, mixed>
     */
    public function group(string $groupCode): array
    {
        if (isset(self::$runtime[$groupCode])) {
            return self::$runtime[$groupCode];
        }

        $cacheKey = self::CACHE_KEY.$groupCode;

        $items = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($groupCode) {
            $rows = SysConfig::query()
                ->where('group_code', $groupCode)
                ->where('status', 1)
                ->get(['config_key', 'config_value', 'is_secret', 'value_type']);

            $result = [];
            foreach ($rows as $row) {
                // config_key 形如 "storage.access_key"，取点号后段作为组内键名
                $shortName = str_contains($row->config_key, '.')
                    ? substr($row->config_key, strrpos($row->config_key, '.') + 1)
                    : $row->config_key;

                $result[$shortName] = $this->castValue($row);
            }

            return $result;
        });

        self::$runtime[$groupCode] = $items;

        return $items;
    }

    /**
     * 判断某一组是否已完成关键配置
     */
    public function isConfigured(string $groupCode, array $requiredKeys): bool
    {
        $items = $this->group($groupCode);

        foreach ($requiredKeys as $key) {
            if (empty($items[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 清除配置缓存（总后台保存配置后调用）
     */
    public function flush(?string $groupCode = null): void
    {
        if ($groupCode !== null) {
            Cache::forget(self::CACHE_KEY.$groupCode);
            unset(self::$runtime[$groupCode]);

            return;
        }

        self::$runtime = [];

        $groups = SysConfig::query()->distinct()->pluck('group_code')->all();
        foreach ($groups as $group) {
            Cache::forget(self::CACHE_KEY.$group);
        }
    }

    /**
     * 加密敏感值（写入前调用）
     */
    public function encryptSecret(string $plain): string
    {
        return Crypt::encryptString($plain);
    }

    /**
     * 解密敏感值（读取时调用）
     */
    public function decryptSecret(string $cipher): string
    {
        try {
            return Crypt::decryptString($cipher);
        } catch (DecryptException) {
            // APP_KEY 变更或数据被污染；抛业务异常而非静默返回密文，避免用错密钥去调第三方
            throw new BusinessException(
                ErrorCode::CONFIG_SAVE_FAILED,
                '配置解密失败，可能是 APP_KEY 被更换，请在总后台重新填写该项配置'
            );
        }
    }

    /**
     * 生成脱敏展示值，如 sk_****f3a1
     */
    public function maskSecret(?string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        $len = strlen($value);

        if ($len <= 8) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, 3).'****'.substr($value, -4);
    }

    /** 拆分 "storage.access_key" => ['storage', 'access_key'] */
    private function splitKey(string $key): array
    {
        if (! str_contains($key, '.')) {
            throw new BusinessException(ErrorCode::CONFIG_NOT_FOUND, "配置键 {$key} 格式不正确，应为 分组.键名");
        }

        [$group, $name] = explode('.', $key, 2);

        return [$group, $name];
    }

    /** 按 value_type 做类型转换，密文自动解密 */
    private function castValue(SysConfig $row): mixed
    {
        $raw = (string) ($row->config_value ?? '');

        // 密文：先解密再按字符串返回
        if ((int) $row->is_secret === 1) {
            return $raw === '' ? '' : $this->decryptSecret($raw);
        }

        return match ((int) $row->value_type) {
            2 => is_numeric($raw) ? (int) $raw : 0,                  // 数字
            3 => in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true),  // 布尔
            4 => json_decode($raw, true) ?: [],                      // JSON
            default => $raw,                                         // 字符串 / 密文
        };
    }
}
