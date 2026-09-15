<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ErrorCode;
use RuntimeException;
use Throwable;

/**
 * 业务异常
 *
 * 使用约定：
 *   - Service / Controller 层遇到可预期的业务失败，一律 `throw new BusinessException(ErrorCode::XXX)`
 *   - 不要抛通用 Exception，否则前端只能拿到 10500，无法做针对性提示
 *   - 消息留空时自动取 ErrorCode::message() 的默认中文提示
 *
 * 示例：
 *   throw new BusinessException(ErrorCode::BANK_NOT_FOUND);
 *   throw BusinessException::of(ErrorCode::QUESTION_LIMIT, '单题库最多 50000 题');
 *   throw BusinessException::notFound('题库不存在');
 */
class BusinessException extends RuntimeException
{
    private int $errorCode;

    private mixed $errorData;

    private int $httpStatus;

    public function __construct(
        int $errorCode = ErrorCode::SERVER_ERROR,
        string $message = '',
        mixed $errorData = null,
        int $httpStatus = 200,
        ?Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
        $this->httpStatus = $httpStatus;

        parent::__construct(
            $message !== '' ? $message : ErrorCode::message($errorCode),
            $errorCode,
            $previous
        );
    }

    /** 快捷构造 */
    public static function of(int $errorCode, string $message = '', mixed $data = null, int $httpStatus = 200): self
    {
        return new self($errorCode, $message, $data, $httpStatus);
    }

    /** 参数错误（10001，HTTP 422） */
    public static function param(string $message = ''): self
    {
        return new self(ErrorCode::PARAM_ERROR, $message, null, 422);
    }

    /** 数据不存在（10004，HTTP 404） */
    public static function notFound(string $message = ''): self
    {
        return new self(ErrorCode::DATA_NOT_FOUND, $message, null, 404);
    }

    /** 无权限（10403，HTTP 403） */
    public static function forbidden(string $message = ''): self
    {
        return new self(ErrorCode::NO_PERMISSION, $message, null, 403);
    }

    /** 功能开发中（90001） */
    public static function notImplemented(string $message = ''): self
    {
        return new self(ErrorCode::NOT_IMPLEMENTED, $message);
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorData(): mixed
    {
        return $this->errorData;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
