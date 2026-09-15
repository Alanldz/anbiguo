<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_configs ｜ 系统配置表（配置中心，docs/03 §2.10）
 *
 * ⚠️ is_secret=1 的 config_value 存储的是 Crypt（AES-256-CBC）加密串，
 *   读取/写入必须与主应用共享 APP_KEY（见 .env.example 注释）。
 */
class SysConfig extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sys_configs';

    /** 值类型：1=字符串 2=数字 3=布尔 4=JSON 5=密文 */
    public const VALUE_TYPE_STRING = 1;
    public const VALUE_TYPE_INT = 2;
    public const VALUE_TYPE_BOOL = 3;
    public const VALUE_TYPE_JSON = 4;
    public const VALUE_TYPE_SECRET = 5;

    /** 是否敏感：0=否 1=是（界面脱敏显示） */
    public const IS_SECRET = 1;
    public const NOT_SECRET = 0;

    /** 状态：1=启用 2=停用 */
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 2;

    protected $fillable = [
        'group_code',
        'config_key',
        'config_value',
        'default_value',
        'value_type',
        'is_secret',
        'title',
        'remark',
        'is_system',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'value_type' => 'integer',
            'is_secret'  => 'integer',
            'is_system'  => 'integer',
            'sort_order' => 'integer',
            'status'     => 'integer',
        ];
    }

    /** 脱敏掩码（敏感配置对外不暴露真实值） */
    public const MASKED_VALUE = '******';
}
