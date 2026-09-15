<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：user_accounts ｜ 用户账号表（与主应用同一张表，仅查询/禁用，docs/03 §2.3）
 */
class User extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'user_accounts';

    /** 状态：1=正常 2=禁用 3=注销中 4=已注销 */
    public const STATUS_NORMAL = 1;
    public const STATUS_DISABLED = 2;
    public const STATUS_CANCELLING = 3;
    public const STATUS_CANCELLED = 4;

    protected $fillable = [
        'uid',
        'mobile',
        'mobile_country_code',
        'password',
        'wx_mp_openid',
        'wx_unionid',
        'register_source',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password'        => 'hashed',
            'register_source' => 'integer',
            'status'          => 'integer',
            'last_login_at'   => 'datetime',
        ];
    }
}
