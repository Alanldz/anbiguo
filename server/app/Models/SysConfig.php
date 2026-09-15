<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_configs ｜ 系统配置表（配置中心，短信/微信/OSS/支付/AI/OCR 全部 KEY 存于此）
 * 登记：docs/03A-数据字典.md §8.5.1
 */
class SysConfig extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'sys_configs';

    /** 主键 */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** 时间戳由应用层/模型事件显式写入 */
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /** 可写字段 */
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

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
