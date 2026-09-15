<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 表：sys_logs ｜ 操作日志表（流水表，不做软删除，docs/03 §2.10）
 *
 * 仅 created_at 时间戳（无 updated_at），写入时显式赋值。
 */
class SysLog extends Model
{
    protected $table = 'sys_logs';

    /** 流水表无更新时间 */
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'admin_name',
        'module',
        'action',
        'description',
        'target_type',
        'target_id',
        'before_json',
        'after_json',
        'ip',
        'user_agent',
        'request_id',
        'result',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'admin_id'  => 'integer',
            'target_id' => 'integer',
            'result'    => 'integer',
        ];
    }
}
