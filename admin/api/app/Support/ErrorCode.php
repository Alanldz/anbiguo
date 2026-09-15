<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 总后台错误码（docs/04-API接口规范与登记表.md §2.3，独立应用子集）
 *
 * ⚠️ 错误码全局唯一，不得重复。新增错误码必须：
 *   1. 先在本类登记
 *   2. 同步 docs/04 §2.3 表格
 *
 * 分段（总后台专属子集）：
 *   0         成功
 *   10401     未登录
 *   10403     无权限
 *   10422     参数错误
 *   10500     服务异常
 *   4xxxx     总后台业务段
 *
 * 说明：本应用独立部署，业务段从 4xxxx 起，避免与三端通用 1xxxx 段混淆。
 */
final class ErrorCode
{
    public const SUCCESS = 0;

    // -------------------------------------------------------------------------
    // 通用 / 鉴权
    // -------------------------------------------------------------------------
    public const UNAUTHORIZED  = 10401;  // 登录失效 / 未登录
    public const NO_PERMISSION = 10403;  // 无权限
    public const PARAM_ERROR   = 10422;  // 参数错误
    public const SERVER_ERROR  = 10500;  // 服务异常

    // -------------------------------------------------------------------------
    // 4xxxx 总后台业务段
    // -------------------------------------------------------------------------
    public const ADMIN_LOGIN_FAILED       = 40101;  // 管理员账号或密码错误
    public const ADMIN_DISABLED           = 40102;  // 管理员账号已禁用
    public const ADMIN_LOCKED             = 40103;  // 管理员账号已锁定，请稍后再试
    public const ADMIN_DELETE_FORBIDDEN   = 40104;  // 禁止删除自己 / 最后一个启用超管
    public const ADMIN_USERNAME_EXISTS    = 40105;  // 登录账号已存在
    public const ADMIN_NOT_SUPER          = 40106;  // 仅超级管理员可执行该操作

    public const ROLE_IN_USE              = 40201;  // 角色仍有管理员在使用，无法删除
    public const ROLE_SYSTEM_PROTECTED    = 40202;  // 系统预置角色不可删除

    public const CONFIG_NOT_FOUND         = 40301;  // 配置项不存在
    public const CONFIG_SAVE_FAILED       = 40302;  // 配置保存失败

    public const DATA_NOT_FOUND           = 40401;  // 数据不存在
    public const DATA_EXISTS              = 40402;  // 数据已存在
    public const OPERATION_FORBIDDEN      = 40403;  // 当前状态不允许该操作
    public const BANK_AUDIT_REJECTED      = 40404;  // 题库已被拒绝，无法上架

    /** 错误码 → 默认中文提示 */
    public static function message(int $code): string
    {
        return self::MAP[$code] ?? '未知错误';
    }

    /** 全部错误码映射，供后台文档与前端错误提示复用 */
    public const MAP = [
        self::SUCCESS              => '成功',

        self::UNAUTHORIZED         => '登录状态已失效，请重新登录',
        self::NO_PERMISSION        => '没有操作权限',
        self::PARAM_ERROR          => '参数错误',
        self::SERVER_ERROR         => '服务开小差了，请稍后再试',

        self::ADMIN_LOGIN_FAILED     => '账号或密码错误',
        self::ADMIN_DISABLED         => '该管理员账号已被禁用',
        self::ADMIN_LOCKED           => '账号已锁定，请稍后再试',
        self::ADMIN_DELETE_FORBIDDEN => '禁止删除自己或最后一个启用状态的超级管理员',
        self::ADMIN_USERNAME_EXISTS  => '该登录账号已存在',
        self::ADMIN_NOT_SUPER        => '仅超级管理员可执行该操作',

        self::ROLE_IN_USE            => '该角色仍有管理员在使用，无法删除',
        self::ROLE_SYSTEM_PROTECTED  => '系统预置角色不可删除',

        self::CONFIG_NOT_FOUND       => '配置项不存在',
        self::CONFIG_SAVE_FAILED     => '配置保存失败',

        self::DATA_NOT_FOUND          => '数据不存在',
        self::DATA_EXISTS             => '数据已存在',
        self::OPERATION_FORBIDDEN     => '当前状态不允许该操作',
        self::BANK_AUDIT_REJECTED     => '该题库已被拒绝，无法上架',
    ];
}
