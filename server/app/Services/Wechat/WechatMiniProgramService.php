<?php

declare(strict_types=1);

namespace App\Services\Wechat;

use App\Exceptions\BusinessException;
use App\Services\Config\ConfigCenter;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Log;

/**
 * 微信小程序服务（code2Session）
 *
 * 配置来源：sys_configs 的 wechat 分组
 *   mp_app_id / mp_secret
 *
 * 注意：
 *   1. session_key 属于敏感数据，不得下发给前端、不得写日志
 *   2. 失效时间由微信控制，本服务只负责换取，不做缓存
 */
class WechatMiniProgramService
{
    private const CODE2SESSION_URL = 'https://api.weixin.qq.com/sns/jscode2session';

    public function __construct(private readonly ConfigCenter $config)
    {
    }

    /**
     * 用前端 wx.login 拿到的 code 换取 openid / unionid
     *
     * @return array{openid:string, unionid:string, session_key:string}
     */
    public function code2Session(string $code): array
    {
        $appId  = (string) $this->config->getOrFail('wechat.mp_app_id', ErrorCode::WECHAT_LOGIN_FAILED);
        $secret = (string) $this->config->getOrFail('wechat.mp_secret', ErrorCode::WECHAT_LOGIN_FAILED);

        $query = http_build_query([
            'appid'      => $appId,
            'secret'     => $secret,
            'js_code'    => $code,
            'grant_type' => 'authorization_code',
        ]);

        $context = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 8]]);
        $raw = @file_get_contents(self::CODE2SESSION_URL.'?'.$query, false, $context);

        if ($raw === false) {
            throw new BusinessException(ErrorCode::WECHAT_API_ERROR, '微信接口无响应，请稍后重试');
        }

        $result = json_decode($raw, true) ?: [];

        if (! empty($result['errcode'])) {
            // 只记 errcode 与 errmsg，绝不记 code / session_key
            Log::channel('third_party')->warning('微信 code2Session 失败', [
                'errcode' => $result['errcode'],
                'errmsg'  => $result['errmsg'] ?? '',
            ]);

            $message = match ((int) $result['errcode']) {
                40029 => '登录凭证已失效，请重试',
                45011 => '操作过于频繁，请稍后再试',
                40226 => '该账号存在风险，已被限制登录',
                default => '微信登录失败：'.($result['errmsg'] ?? '未知错误'),
            };

            throw new BusinessException(ErrorCode::WECHAT_LOGIN_FAILED, $message);
        }

        $openid = (string) ($result['openid'] ?? '');
        if ($openid === '') {
            throw new BusinessException(ErrorCode::WECHAT_LOGIN_FAILED, '微信未返回用户标识');
        }

        return [
            'openid'      => $openid,
            'unionid'     => (string) ($result['unionid'] ?? ''),
            'session_key' => (string) ($result['session_key'] ?? ''),
        ];
    }

    /**
     * 解密小程序加密数据（手机号获取等场景，后续版本接入）
     */
    public function decryptData(string $sessionKey, string $encryptedData, string $iv): array
    {
        $aesKey = base64_decode($sessionKey, true);
        $aesIv  = base64_decode($iv, true);
        $aesCipher = base64_decode($encryptedData, true);

        if ($aesKey === false || $aesIv === false || $aesCipher === false) {
            throw new BusinessException(ErrorCode::WECHAT_API_ERROR, '加密数据格式不正确');
        }

        $decrypted = openssl_decrypt(
            $aesCipher,
            'AES-128-CBC',
            $aesKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $aesIv
        );

        if ($decrypted === false) {
            throw new BusinessException(ErrorCode::WECHAT_API_ERROR, '数据解密失败');
        }

        // 微信在 PKCS#7 之外额外补了一个字符，需先去尾
        $decrypted = substr($decrypted, 0, -1);
        $data = json_decode($decrypted, true);

        if (! is_array($data)) {
            throw new BusinessException(ErrorCode::WECHAT_API_ERROR, '数据解密结果异常');
        }

        return $data;
    }
}
