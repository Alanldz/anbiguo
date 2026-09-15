<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 表：sys_event_logs ｜ 客户端埋点日志表（append-only，不软删）
 * 登记：docs/04-API接口规范与登记表.md §三 API-EVT-001、§五 API-ADM-106
 */
class SysEventLog extends Model
{
    use HasFactory;

    /** 表名 */
    protected $table = 'sys_event_logs';

    /** 主键 */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** 时间戳由模型显式写入 */
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /** 可写字段 */
    protected $fillable = [
        'user_id',
        'event',
        'page',
        'biz_type',
        'biz_id',
        'extra_json',
        'client_platform',
        'occurred_at',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'biz_id' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }
}
