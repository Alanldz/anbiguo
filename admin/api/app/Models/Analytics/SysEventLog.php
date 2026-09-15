<?php

declare(strict_types=1);

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 表：sys_event_logs ｜ 客户端埋点日志表（仅只读统计，主应用负责写入）
 * 登记：docs/04-API接口规范与登记表.md §三 API-EVT-001、§五 API-ADM-106
 *
 * ⚠️ 总后台不跑迁移：该表由主应用 server/ 迁移 2026_09_15_100004 创建，
 *   本模型直接指向同名表做只读分析（与 SysConfig 跨应用共享表同理）。
 */
class SysEventLog extends Model
{
    use HasFactory;

    protected $table = 'sys_event_logs';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** 只读：禁止时间戳自动写入 */
    public $timestamps = false;
}
