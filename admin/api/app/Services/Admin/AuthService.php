<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Auth\TokenBlacklist;
use App\Exceptions\BusinessException;
use App\Models\SysAdmin;
use App\Models\SysLoginLog;
use App\Support\ErrorCode;
use App\Support\Jwt\TokenService;
use Illuminate\Support\Facades\Hash;

/**
 * 管理员认证服务（docs/04 §五 API-ADM-AUTH-*）
 *
 * - 登录：bcrypt 校验 → 写登录日志 → 签发 admin 作用域 JWT（含 jti）→ 更新登录信息
 * - 登出：jti 进黑名单
 * - me：返回管理员 + 角色 + 权限码数组
 */
class AuthService
{
    /** 连续登录失败锁定阈值 */
    private const MAX_LOGIN_FAIL = 5;

    /** 登录（API-ADM-001） */
    public function login(string $username, string $password, string $ip, ?string $userAgent): array
    {
        /** @var SysAdmin|null $admin */
        $admin = SysAdmin::where('username', $username)->first();

        // 账号不存在：记录一条 admin_id=0 的失败日志，不暴露账号是否存在
        if ($admin === null) {
            $this->writeLoginLog(0, $username, SysLoginLog::RESULT_FAILED, '账号不存在', $ip, $userAgent);

            throw new BusinessException(ErrorCode::ADMIN_LOGIN_FAILED, '账号或密码错误', null, 401);
        }

        // 锁定中
        if ($admin->locked_until !== null && $admin->locked_until->isFuture()) {
            $this->writeLoginLog($admin->id, $username, SysLoginLog::RESULT_FAILED, '账号已锁定', $ip, $userAgent);

            throw new BusinessException(ErrorCode::ADMIN_LOCKED, '账号已锁定，请稍后再试', null, 403);
        }

        // 禁用
        if ($admin->status !== SysAdmin::STATUS_NORMAL) {
            $this->writeLoginLog($admin->id, $username, SysLoginLog::RESULT_FAILED, '账号已禁用', $ip, $userAgent);

            throw new BusinessException(ErrorCode::ADMIN_DISABLED, '该管理员账号已被禁用', null, 403);
        }

        // 密码校验
        if (! Hash::check($password, $admin->password)) {
            $failCount = $admin->login_fail_count + 1;
            $lockUntil = $failCount >= self::MAX_LOGIN_FAIL ? now()->addMinutes(15) : null;

            $admin->update([
                'login_fail_count' => $failCount,
                'locked_until'     => $lockUntil,
            ]);

            $this->writeLoginLog($admin->id, $username, SysLoginLog::RESULT_FAILED, '密码错误', $ip, $userAgent);

            throw new BusinessException(ErrorCode::ADMIN_LOGIN_FAILED, '账号或密码错误', null, 401);
        }

        // 登录成功：重置失败计数并写入登录信息
        $admin->update([
            'login_fail_count' => 0,
            'locked_until'     => null,
            'last_login_at'    => now(),
            'last_login_ip'    => $ip,
        ]);

        $this->writeLoginLog($admin->id, $username, SysLoginLog::RESULT_SUCCESS, '', $ip, $userAgent);

        $ttl = (int) config('auth.guards.admin.ttl', 30);
        $secret = (string) config('auth.guards.admin.secret', '');
        $issued = TokenService::issue($admin->id, $secret, $ttl);

        return [
            'token'      => $issued['token'],
            'token_type' => 'Bearer',
            'expires_in'=> $issued['expires_in'],
            'expires_at' => $issued['expires_at'],
            'admin'      => $this->adminInfo($admin),
        ];
    }

    /** 登出（API-ADM-002）：jti 进黑名单 */
    public function logout(SysAdmin $admin, string $token): void
    {
        // 从当前请求 Token 解析 jti；若解析失败（已坏）则忽略
        $secret = (string) config('auth.guards.admin.secret', '');

        try {
            $parsed = TokenService::parse($token, $secret);
            $jti = $parsed['jti'];
            $expiresIn = (int) ($parsed['payload']['exp'] ?? time()) - time();
        } catch (\Throwable) {
            return;
        }

        if ($jti !== '') {
            TokenBlacklist::add($jti, max($expiresIn, 60));
        }
    }

    /** 当前管理员信息（API-ADM-003） */
    public function me(SysAdmin $admin): array
    {
        return $this->adminInfo($admin->loadMissing('roles.permissions'));
    }

    /** 组装管理员对外信息（含角色与权限码） */
    private function adminInfo(SysAdmin $admin): array
    {
        return [
            'id'           => $admin->id,
            'username'     => $admin->username,
            'real_name'    => $admin->real_name,
            'mobile'       => $admin->mobile,
            'email'        => $admin->email,
            'avatar'       => $admin->avatar,
            'is_super'     => (int) $admin->is_super,
            'status'       => (int) $admin->status,
            'last_login_at'=> $admin->last_login_at?->toDateTimeString(),
            'roles'        => $admin->roles->map(fn ($role) => [
                'id'   => $role->id,
                'name' => $role->name,
                'code' => $role->code,
            ])->all(),
            'permissions'  => $admin->permissionCodes(),
        ];
    }

    private function writeLoginLog(int $adminId, string $username, int $status, string $message, string $ip, ?string $userAgent): void
    {
        SysLoginLog::create([
            'admin_id'   => $adminId,
            'username'   => $username,
            'login_type' => SysLoginLog::LOGIN_TYPE_PASSWORD,
            'ip'         => $ip,
            'user_agent' => mb_substr((string) $userAgent, 0, 255),
            'status'     => $status,
            'message'    => $message,
            'created_at' => now(),
        ]);
    }
}
