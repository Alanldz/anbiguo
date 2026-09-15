<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 表：sys_login_logs ｜ 管理员登录日志表（流水表，不做软删除）
 */
class SysLoginLog extends Model
{
    protected $table = 'sys_login_logs';

    /** 流水表无更新时间 */
    public $timestamps = false;

    /** 结果：1=成功 2=失败 */
    public const RESULT_SUCCESS = 1;
    public const RESULT_FAILED = 2;

    /** 登录方式：1=账号密码 2=TOTP 二次验证 */
    public const LOGIN_TYPE_PASSWORD = 1;
    public const LOGIN_TYPE_TOTP = 2;

    protected $fillable = [
        'admin_id',
        'username',
        'login_type',
        'ip',
        'user_agent',
        'status',
        'message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'admin_id'   => 'integer',
            'login_type' => 'integer',
            'status'     => 'integer',
        ];
    }
}
