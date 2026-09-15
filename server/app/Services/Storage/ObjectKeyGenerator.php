<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Enums\FileBizType;
use App\Exceptions\BusinessException;
use App\Support\ErrorCode;
use Illuminate\Support\Str;

/**
 * 对象 Key 生成器（docs/05-OSS存储与文件分类规范.md §二）
 *
 * 目录树：
 *   avatar/{user_id}/{yyyyMM}/
 *   bank/{bank_id}/source|image|ocr|export/
 *   question/{question_id}/
 *   resource/{bank_id}/{category_code}/{yyyyMM}/
 *   course/{course_id}/video|audio|doc/
 *   public/{module}/
 *   template/
 *   report/{yyyyMM}/
 *   feedback/{yyyyMM}/
 *   temp/{yyyyMMdd}/
 *
 * 文件名：
 *   {业务前缀}_{主体ID}_{yyyyMMddHHmmss}_{随机6位}.{扩展名}
 *   例：bank_1024_20260915113025_a7f3c1.xlsx
 */
final class ObjectKeyGenerator
{
    /**
     * 生成对象 Key
     *
     * @param  FileBizType  $bizType       业务类型
     * @param  string       $ext           扩展名（不含点，小写）
     * @param  int          $userId        归属用户
     * @param  int          $bankId        归属题库（可空）
     * @param  int          $questionId    归属题目；课程多媒体场景下传 course_id
     * @param  int          $categoryId    归属文件分类（可空）
     * @param  string       $categoryCode  分类编码（resource 目录用）
     */
    public static function make(
        FileBizType $bizType,
        string $ext,
        int $userId = 0,
        int $bankId = 0,
        int $questionId = 0,
        int $categoryId = 0,
        string $categoryCode = ''
    ): string {
        $ext = self::assertExt($bizType, $ext);

        $directory = self::directory($bizType, $ext, $userId, $bankId, $questionId, $categoryCode);
        $filename = self::filename(self::bizPrefix($bizType), self::subjectId($bizType, $userId, $bankId, $questionId), $ext);

        return $directory.$filename;
    }

    /** 扩展名规范化 + 白名单校验（docs/05 §三） */
    private static function assertExt(FileBizType $bizType, string $ext): string
    {
        $ext = strtolower(ltrim(trim($ext), '.'));

        if ($ext === '') {
            throw new BusinessException(ErrorCode::FILE_TYPE_NOT_SUPPORT, '文件扩展名缺失');
        }

        if (! preg_match('/^[a-z0-9]{1,10}$/', $ext)) {
            throw new BusinessException(ErrorCode::FILE_TYPE_NOT_SUPPORT, '文件扩展名不合法');
        }

        if (! in_array($ext, $bizType->allowedExtensions(), true)) {
            throw new BusinessException(
                ErrorCode::FILE_TYPE_NOT_SUPPORT,
                "「{$bizType->label()}」不支持 .{$ext} 格式"
            );
        }

        return $ext;
    }

    /** 目录前缀 */
    private static function directory(
        FileBizType $bizType,
        string $ext,
        int $userId,
        int $bankId,
        int $questionId,
        string $categoryCode
    ): string {
        $month = date('Ym');
        $day = date('Ymd');

        return match ($bizType) {
            FileBizType::AVATAR => "avatar/{$userId}/{$month}/",

            FileBizType::BANK_SOURCE => "bank/{$bankId}/source/",

            // 已归属题库的图片随题库归档；未归属的挂到题目目录
            FileBizType::QUESTION_IMAGE => $bankId > 0
                ? "bank/{$bankId}/image/"
                : "question/{$questionId}/",

            FileBizType::STUDY_MATERIAL => 'resource/'
                .($bankId > 0 ? $bankId : 0).'/'
                .($categoryCode !== '' ? $categoryCode : 'uncategorized')
                ."/{$month}/",

            // 课程多媒体：调用方把 course_id 传入 $questionId；子目录按扩展名归类
            FileBizType::COURSE_MEDIA => 'course/'.$questionId.'/'.self::courseSubDir($ext).'/',

            FileBizType::PUBLIC_STATIC => 'public/common/',

            FileBizType::TEMP => "temp/{$day}/",
        };
    }

    /** 课程子目录按扩展名归类（docs/05 §一 course/{course_id}/video|audio|doc） */
    private static function courseSubDir(string $ext): string
    {
        return match (strtolower($ext)) {
            'mp4', 'mov', 'avi', 'mkv' => 'video',
            'mp3', 'm4a', 'wav', 'aac' => 'audio',
            default                    => 'doc',
        };
    }

    /** 业务前缀（docs/05 §2.2 对照表） */
    private static function bizPrefix(FileBizType $bizType): string
    {
        return match ($bizType) {
            FileBizType::AVATAR         => 'avatar',
            FileBizType::BANK_SOURCE    => 'bank',
            FileBizType::QUESTION_IMAGE => 'qimg',
            FileBizType::STUDY_MATERIAL => 'res',
            FileBizType::COURSE_MEDIA   => 'video',
            FileBizType::PUBLIC_STATIC  => 'pub',
            FileBizType::TEMP           => 'tmp',
        };
    }

    /** 主体 ID */
    private static function subjectId(FileBizType $bizType, int $userId, int $bankId, int $questionId): int
    {
        return match ($bizType) {
            FileBizType::AVATAR         => $userId,
            FileBizType::BANK_SOURCE    => $bankId,
            FileBizType::QUESTION_IMAGE => $bankId > 0 ? $bankId : $questionId,
            FileBizType::STUDY_MATERIAL => $bankId,
            FileBizType::COURSE_MEDIA   => $questionId,
            FileBizType::PUBLIC_STATIC  => 0,
            FileBizType::TEMP           => $userId,
        };
    }

    /** 文件名：{前缀}_{主体ID}_{yyyyMMddHHmmss}_{随机6位}.{ext} */
    private static function filename(string $prefix, int $subjectId, string $ext): string
    {
        $timestamp = date('YmdHis');
        $random = strtolower(Str::random(6));

        return "{$prefix}_{$subjectId}_{$timestamp}_{$random}.{$ext}";
    }
}
