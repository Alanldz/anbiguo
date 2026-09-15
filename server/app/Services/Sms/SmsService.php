<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Exceptions\BusinessException;
use App\Services\Config\ConfigCenter;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 短信服务（验证码发送与校验）
 *
 * 配置来源：sys_configs 的 sms 分组（总后台配置中心可视化修改）
 *   provider / access_key / secret / sign_name / template_code
 *
 * 验证码生命周期：
 *   发送 → 生成 6 位数字 → 写缓存（5 分钟）→ 调用服务商发送
 *   校验 → 比对成功即销毁（一次性），并记录失败次数防暴力破解
 *
 * ⚠️ 服务商密钥存储于数据库且加密，绝不写死在代码里（docs/04 §六）。
 */
class SmsService
{
    private const CACHE_PREFIX = 'sms:code:';

    private const FAIL_PREFIX = 'sms:fail:';

    /** 单条验证码最多校验失败 5 次，超过即作废 */
    private const MAX_VERIFY_FAIL = 5;

    public function __construct(private readonly ConfigCenter $config)
    {
    }

    /**
     * 发送验证码
     *
     * @param  string  $mobile  手机号（不含国家码）
     * @param  string  $scene   场景：login / bind / reset（不同场景互不干扰）
     * @return array{debug_code:?string}
     */
    public function sendCode(string $mobile, string $scene = 'login'): array
    {
        $this->assertProviderConfigured();

        $code = $this->generateCode();
        $expire = (int) config('anbiguo.sms.code_expire', 300);

        // 先发短信，成功后再写缓存：避免「短信发送失败但缓存里已有验证码」的脏状态
        $this->dispatch($mobile, $code);

        Cache::put($this->cacheKey($mobile, $scene), $code, $expire);
        Cache::forget($this->failKey($mobile, $scene));

        return [
            // 仅当 SMS_DEBUG_SHOW_CODE=true 时返回，生产环境必须为 null
            'debug_code' => $this->debugShowCode() ? $code : null,
        ];
    }

    /**
     * 校验验证码；成功后立即销毁（一次性使用）
     */
    public function verifyCode(string $mobile, string $code, string $scene = 'login'): bool
    {
        $cacheKey = $this->cacheKey($mobile, $scene);
        $cached = (string) Cache::get($cacheKey, '');

        if ($cached === '') {
            throw new BusinessException(ErrorCode::SMS_CODE_ERROR, '验证码已过期，请重新获取');
        }

        // 防暴力破解：同一手机号同一场景累计失败 5 次即作废当前验证码
        $failKey = $this->failKey($mobile, $scene);
        $failCount = (int) Cache::get($failKey, 0);

        if ($failCount >= self::MAX_VERIFY_FAIL) {
            Cache::forget($cacheKey);

            throw new BusinessException(ErrorCode::SMS_CODE_ERROR, '验证码已作废，请重新获取');
        }

        if (! hash_equals($cached, $code)) {
            Cache::put($failKey, $failCount + 1, (int) config('anbiguo.sms.code_expire', 300));

            throw new BusinessException(ErrorCode::SMS_CODE_ERROR);
        }

        Cache::forget($cacheKey);
        Cache::forget($failKey);

        return true;
    }

    /**
     * 服务商连通性测试（总后台「测试连通性」按钮调用）
     *
     * @return array{success:bool, message:string}
     */
    public function testConnection(): array
    {
        try {
            $this->assertProviderConfigured();
        } catch (BusinessException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return [
            'success' => true,
            'message' => sprintf(
                '配置完整：服务商=%s，签名=%s，模板=%s（未实际发送短信）',
                $this->providerName(),
                (string) $this->config->get('sms.sign_name', '-'),
                (string) $this->config->get('sms.template_code', '-')
            ),
        ];
    }

    /** 实际调用服务商发送 */
    private function dispatch(string $mobile, string $code): void
    {
        $provider = (int) $this->config->get('sms.provider', 1);

        try {
            match ($provider) {
                1 => $this->sendByAliyun($mobile, $code),
                2 => $this->sendByTencent($mobile, $code),
                default => throw new BusinessException(ErrorCode::SMS_SEND_FAILED, '短信服务商配置不正确'),
            };
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::channel('third_party')->error('短信发送异常', [
                'mobile'   => $this->maskMobile($mobile),
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);

            throw new BusinessException(ErrorCode::SMS_SEND_FAILED, '短信发送失败，请稍后重试');
        }

        Log::channel('third_party')->info('短信发送成功', [
            'mobile'   => $this->maskMobile($mobile),
            'provider' => $provider,
            'template' => (string) $this->config->get('sms.template_code', ''),
        ]);
    }

    /**
     * 阿里云短信
     *
     * 使用官方 SDK（alibabacloud/dysmsapi-20170525）会更省事，
     * 但为减少依赖体积，这里走 V3 签名直连 HTTP 接口。
     */
    private function sendByAliyun(string $mobile, string $code): void
    {
        $accessKey = (string) $this->config->getOrFail('sms.access_key', ErrorCode::SMS_SEND_FAILED);
        $secret    = (string) $this->config->getOrFail('sms.secret', ErrorCode::SMS_SEND_FAILED);
        $signName  = (string) $this->config->getOrFail('sms.sign_name', ErrorCode::SMS_SEND_FAILED);
        $template  = (string) $this->config->getOrFail('sms.template_code', ErrorCode::SMS_SEND_FAILED);

        // 阿里云短信 API 使用 RPC 风格签名（HMAC-SHA1）
        $params = [
            'Action'           => 'SendSms',
            'Version'          => '2017-05-25',
            'RegionId'         => 'cn-hangzhou',
            'PhoneNumbers'     => $mobile,
            'SignName'         => $signName,
            'TemplateCode'     => $template,
            'TemplateParam'    => json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
            'Format'           => 'JSON',
            'SignatureMethod'  => 'HMAC-SHA1',
            'SignatureVersion' => '1.0',
            'SignatureNonce'   => bin2hex(random_bytes(16)),
            'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
            'AccessKeyId'      => $accessKey,
        ];

        ksort($params);
        $canonical = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $stringToSign = 'GET&%2F&'.rawurlencode($canonical);
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $secret.'&', true));
        $query = $canonical.'&Signature='.rawurlencode($signature);

        $response = $this->httpGet('https://dysmsapi.aliyuncs.com/?'.$query);

        if ($response === null) {
            throw new BusinessException(ErrorCode::SMS_SEND_FAILED, '短信服务无响应');
        }

        $result = json_decode($response, true);
        if (($result['Code'] ?? '') !== 'OK') {
            throw new BusinessException(
                ErrorCode::SMS_SEND_FAILED,
                '短信发送失败：'.($result['Message'] ?? '未知错误')
            );
        }
    }

    /** 腾讯云短信（V3 TC3-HMAC-SHA256 签名，实现留待接入时补齐） */
    private function sendByTencent(string $mobile, string $code): void
    {
        throw new BusinessException(
            ErrorCode::SMS_SEND_FAILED,
            '腾讯云短信驱动尚未实现，请在配置中心将服务商改为阿里云，或联系开发补齐驱动'
        );
    }

    private function httpGet(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 8,
                'header'  => "User-Agent: anbiguo-server/1.0\r\n",
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        return $content === false ? null : $content;
    }

    /** 生成 6 位数字验证码（首位不为 0，避免用户忽略前导零） */
    private function generateCode(): string
    {
        $length = (int) config('anbiguo.sms.code_length', 6);

        return (string) random_int(10 ** ($length - 1), (10 ** $length) - 1);
    }

    private function cacheKey(string $mobile, string $scene): string
    {
        return self::CACHE_PREFIX.$scene.':'.$mobile;
    }

    private function failKey(string $mobile, string $scene): string
    {
        return self::FAIL_PREFIX.$scene.':'.$mobile;
    }

    private function debugShowCode(): bool
    {
        return (bool) config('anbiguo.sms.debug_show_code', false) && ! app()->isProduction();
    }

    private function assertProviderConfigured(): void
    {
        if (! $this->config->isConfigured('sms', ['provider', 'access_key', 'secret', 'sign_name', 'template_code'])) {
            throw new BusinessException(
                ErrorCode::SMS_SEND_FAILED,
                '短信服务未配置，请在总后台「配置中心 → 短信服务」填写完整'
            );
        }
    }

    private function providerName(): string
    {
        return match ((int) $this->config->get('sms.provider', 1)) {
            1 => '阿里云',
            2 => '腾讯云',
            default => '未知',
        };
    }

    /** 日志脱敏 */
    private function maskMobile(string $mobile): string
    {
        return strlen($mobile) >= 11
            ? substr($mobile, 0, 3).'****'.substr($mobile, -4)
            : '***';
    }
}
