<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：user_accounts ｜ 用户账号表（独立账号体系，与后台管理员不互通）
 * 登记：docs/03A-数据字典.md §1.1
 */
class User extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名（模型名 User 无法自动推断为 user_accounts） */
    protected $table = 'user_accounts';

    /** 主键 */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** 时间戳由应用层/模型事件显式写入 */
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /** 可写字段（排除 id 与时间戳/软删字段） */
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

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** 用户资料（1:1） */
    public function userProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    /** 会员信息（1:1） */
    public function userMember(): HasOne
    {
        return $this->hasOne(UserMember::class, 'user_id');
    }

    /** 拥有的题库（user_id=0 为官方题库） */
    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'user_id');
    }

    /** 错题本 */
    public function wrongQuestions(): HasMany
    {
        return $this->hasMany(UserWrongQuestion::class, 'user_id');
    }

    /** 收藏题 */
    public function favoriteQuestions(): HasMany
    {
        return $this->hasMany(UserFavoriteQuestion::class, 'user_id');
    }

    /** 考试记录 */
    public function examRecords(): HasMany
    {
        return $this->hasMany(ExamRecord::class, 'user_id');
    }

    /** 订单 */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }
}
