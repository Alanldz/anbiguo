<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 全局错误码（docs/04-API接口规范与登记表.md §2.3）
 *
 * ⚠️ 错误码全局唯一，不得重复。新增错误码必须：
 *   1. 先在本类中登记
 *   2. 同步 docs/04 §2.3 表格
 *
 * 分段：
 *   0        成功
 *   1xxxx    通用 / 参数 / 鉴权
 *   2xxxx    用户域
 *   3xxxx    题库 / 题目域
 *   4xxxx    考试域
 *   5xxxx    交易 / 支付域
 *   6xxxx    文件 / OSS 域
 *   7xxxx    第三方接口域
 *   8xxxx    管理后台域
 *   9xxxx    未实现 / 维护中
 */
final class ErrorCode
{
    public const SUCCESS = 0;

    // -------------------------------------------------------------------------
    // 1xxxx 通用 / 参数 / 鉴权
    // -------------------------------------------------------------------------
    public const PARAM_ERROR          = 10001;  // 参数错误
    public const PARAM_MISSING        = 10002;  // 缺少必传参数
    public const PARAM_FORMAT_ERROR   = 10003;  // 参数格式不正确
    public const DATA_NOT_FOUND       = 10004;  // 数据不存在
    public const DATA_EXISTS          = 10005;  // 数据已存在
    public const OPERATION_FORBIDDEN  = 10006;  // 当前状态不允许该操作

    public const UNAUTHORIZED         = 10401;  // 登录失效
    public const NO_PERMISSION        = 10403;  // 无权限
    public const FORBIDDEN            = 10403;  // 越权访问（与 NO_PERMISSION 同义，专用于数据归属边界）
    public const NOT_FOUND            = 10404;  // 接口不存在
    public const METHOD_NOT_ALLOWED   = 10405;  // 请求方法不允许
    public const TOO_MANY_REQUESTS    = 10429;  // 请求过于频繁
    public const SERVER_ERROR         = 10500;  // 服务异常

    // -------------------------------------------------------------------------
    // 2xxxx 用户域
    // -------------------------------------------------------------------------
    public const MOBILE_REGISTERED    = 20001;  // 手机号已注册
    public const SMS_CODE_ERROR       = 20002;  // 验证码错误或已过期
    public const MEMBER_EXPIRED       = 20003;  // 会员已过期
    public const ACCOUNT_DISABLED     = 20004;  // 账号已被禁用
    public const ACCOUNT_NOT_FOUND    = 20005;  // 账号不存在
    public const PASSWORD_ERROR       = 20006;  // 密码错误
    public const ACCOUNT_LOCKED       = 20007;  // 账号已锁定，请稍后再试
    public const SMS_SEND_TOO_OFTEN   = 20008;  // 验证码发送过于频繁
    public const SMS_DAILY_LIMIT      = 20009;  // 当日验证码次数已用尽
    public const WECHAT_LOGIN_FAILED  = 20010;  // 微信登录失败
    public const QUOTA_EXHAUSTED      = 20011;  // 配额已用尽（AI 导题等）

    // -------------------------------------------------------------------------
    // 3xxxx 题库 / 题目域
    // -------------------------------------------------------------------------
    public const BANK_NOT_FOUND       = 30001;  // 题库不存在
    public const IMPORT_PARSE_FAILED  = 30002;  // 导入解析失败
    public const QUESTION_LIMIT       = 30003;  // 题目数量超出上限
    public const BANK_NO_PERMISSION   = 30004;  // 无权操作该题库
    public const QUESTION_NOT_FOUND   = 30005;  // 题目不存在
    public const IMPORT_FILE_TYPE     = 30006;  // 不支持的文件格式
    public const BANK_AUDITING        = 30007;  // 题库审核中，暂不可用

    // -------------------------------------------------------------------------
    // 4xxxx 考试域
    // -------------------------------------------------------------------------
    public const PAPER_NOT_FOUND      = 40001;  // 试卷不存在
    public const EXAM_ENDED           = 40002;  // 考试已结束
    public const EXAM_SUBMITTED       = 40003;  // 重复交卷
    public const EXAM_NOT_STARTED     = 40004;  // 考试尚未开始
    public const EXAM_QUESTION_EMPTY  = 40005;  // 组卷题目不足

    // -------------------------------------------------------------------------
    // 5xxxx 交易 / 支付域
    // -------------------------------------------------------------------------
    public const ORDER_NOT_FOUND      = 50001;  // 订单不存在
    public const PAY_FAILED           = 50002;  // 支付失败
    public const ORDER_PAID           = 50003;  // 订单已支付
    public const ORDER_CLOSED         = 50004;  // 订单已关闭
    public const REFUND_FAILED        = 50005;  // 退款失败
    public const PLAN_OFF_SHELF       = 50006;  // 套餐已下架

    // -------------------------------------------------------------------------
    // 6xxxx 文件 / OSS 域
    // -------------------------------------------------------------------------
    public const FILE_TYPE_NOT_SUPPORT = 60001; // 文件类型不支持
    public const FILE_TOO_LARGE        = 60002; // 文件过大
    public const FILE_UPLOAD_FAILED    = 60003; // 上传失败
    public const FILE_NOT_FOUND        = 60004; // 文件不存在
    public const FILE_EXPIRED          = 60005; // 文件已过期
    public const STORAGE_NOT_CONFIGURED = 60006; // 存储服务未配置，请联系管理员

    // -------------------------------------------------------------------------
    // 7xxxx 第三方接口域
    // -------------------------------------------------------------------------
    public const SMS_SEND_FAILED      = 70001;  // 短信发送失败
    public const WECHAT_API_ERROR     = 70002;  // 微信接口异常
    public const AI_SERVICE_TIMEOUT   = 70003;  // AI 服务超时
    public const OCR_SERVICE_ERROR    = 70004;  // OCR 识别失败
    public const THIRD_PARTY_ERROR    = 70005;  // 第三方服务异常

    // -------------------------------------------------------------------------
    // 8xxxx 管理后台域
    // -------------------------------------------------------------------------
    public const ADMIN_LOGIN_FAILED   = 80001;  // 管理员账号或密码错误
    public const ADMIN_DISABLED       = 80002;  // 管理员账号已禁用
    public const CONFIG_NOT_FOUND     = 80003;  // 配置项不存在
    public const CONFIG_SAVE_FAILED   = 80004;  // 配置保存失败
    public const CONFIG_TEST_FAILED   = 80005;  // 配置连通性测试失败

    // -------------------------------------------------------------------------
    // 9xxxx 其他
    // -------------------------------------------------------------------------
    public const NOT_IMPLEMENTED      = 90001;  // 接口开发中
    public const MAINTENANCE          = 90002;  // 系统维护中

    /** 错误码 → 默认中文提示 */
    public static function message(int $code): string
    {
        return self::MAP[$code] ?? '未知错误';
    }

    /** 全部错误码映射，供后台文档与前端错误提示复用 */
    public const MAP = [
        self::SUCCESS              => '成功',

        self::PARAM_ERROR          => '参数错误',
        self::PARAM_MISSING        => '缺少必传参数',
        self::PARAM_FORMAT_ERROR   => '参数格式不正确',
        self::DATA_NOT_FOUND       => '数据不存在',
        self::DATA_EXISTS          => '数据已存在',
        self::OPERATION_FORBIDDEN  => '当前状态不允许该操作',

        self::UNAUTHORIZED         => '登录状态已失效，请重新登录',
        self::NO_PERMISSION        => '没有操作权限',
        self::NOT_FOUND            => '接口不存在',
        self::METHOD_NOT_ALLOWED   => '请求方法不被允许',
        self::TOO_MANY_REQUESTS    => '操作过于频繁，请稍后再试',
        self::SERVER_ERROR         => '服务开小差了，请稍后再试',

        self::MOBILE_REGISTERED    => '该手机号已注册',
        self::SMS_CODE_ERROR       => '验证码错误或已过期',
        self::MEMBER_EXPIRED       => '会员已过期，请续费后使用',
        self::ACCOUNT_DISABLED     => '账号已被禁用，请联系客服',
        self::ACCOUNT_NOT_FOUND    => '账号不存在',
        self::PASSWORD_ERROR       => '密码错误',
        self::ACCOUNT_LOCKED       => '账号已锁定，请稍后再试',
        self::SMS_SEND_TOO_OFTEN   => '验证码发送过于频繁，请稍后再试',
        self::SMS_DAILY_LIMIT      => '今日验证码次数已用尽',
        self::WECHAT_LOGIN_FAILED  => '微信登录失败，请重试',
        self::QUOTA_EXHAUSTED      => '配额已用尽，开通会员可获得更多次数',

        self::BANK_NOT_FOUND       => '题库不存在或已删除',
        self::IMPORT_PARSE_FAILED  => '文档解析失败，请检查文件内容',
        self::QUESTION_LIMIT       => '题目数量超出上限',
        self::BANK_NO_PERMISSION   => '无权操作该题库',
        self::QUESTION_NOT_FOUND   => '题目不存在',
        self::IMPORT_FILE_TYPE     => '不支持该文件格式',
        self::BANK_AUDITING        => '题库审核中，暂不可用',

        self::PAPER_NOT_FOUND      => '试卷不存在',
        self::EXAM_ENDED           => '考试已结束',
        self::EXAM_SUBMITTED       => '请勿重复交卷',
        self::EXAM_NOT_STARTED     => '考试尚未开始',
        self::EXAM_QUESTION_EMPTY  => '可抽取的题目不足，请调整组卷规则',

        self::ORDER_NOT_FOUND      => '订单不存在',
        self::PAY_FAILED           => '支付失败，请重试',
        self::ORDER_PAID           => '订单已支付',
        self::ORDER_CLOSED         => '订单已关闭',
        self::REFUND_FAILED        => '退款失败',
        self::PLAN_OFF_SHELF       => '该套餐已下架',

        self::FILE_TYPE_NOT_SUPPORT => '文件类型不支持',
        self::FILE_TOO_LARGE        => '文件过大，请压缩后重试',
        self::FILE_UPLOAD_FAILED    => '文件上传失败，请重试',
        self::FILE_NOT_FOUND        => '文件不存在',
        self::FILE_EXPIRED          => '文件已过期，请重新上传',
        self::STORAGE_NOT_CONFIGURED => '存储服务未配置，请联系管理员',

        self::SMS_SEND_FAILED      => '短信发送失败',
        self::WECHAT_API_ERROR     => '微信接口异常',
        self::AI_SERVICE_TIMEOUT   => 'AI 服务响应超时，请稍后再试',
        self::OCR_SERVICE_ERROR    => '图片识别失败，请重新拍摄',
        self::THIRD_PARTY_ERROR    => '第三方服务异常',

        self::ADMIN_LOGIN_FAILED   => '账号或密码错误',
        self::ADMIN_DISABLED       => '该管理员账号已被禁用',
        self::CONFIG_NOT_FOUND     => '配置项不存在',
        self::CONFIG_SAVE_FAILED   => '配置保存失败',
        self::CONFIG_TEST_FAILED   => '配置连通性测试失败',

        self::NOT_IMPLEMENTED      => '该功能正在开发中',
        self::MAINTENANCE          => '系统维护中，请稍后再试',
    ];
}
