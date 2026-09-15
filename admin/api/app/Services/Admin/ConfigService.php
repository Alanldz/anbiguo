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
