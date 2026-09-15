<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SysLog;
use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * 操作日志中间件（docs/06 §八 第 4 条）
 *
 * 用法：在需要留痕的路由上挂 `op.log:模块,动作`，例如：
 *   Route::put('configs/{group}', [...])->middleware('op.log:config,update');
 *
 * 设计取舍：
 *   - 写日志失败**绝不能影响主业务**，因此全程 try/catch 并只记 warning
 *   - 请求体中的密码、密钥等敏感字段在写入前统一脱敏
 *   - 当前仅服务于总后台（sys_logs 表），客户端不写此表
 */
class OperationLogMiddleware
{
    /** 需要脱敏的字段名（含子串匹配） */
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'old_password', 'new_password',
        'secret', 'secret_key', 'access_key', 'api_key', 'private_key',
        'token', 'authorization', 'cert', 'certificate',
    ];

    public function handle(Request $request, Closure $next, string $module = '', string $action = ''): Response
    {
        $response = $next($request);

        // 只记录写操作
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        try {
            $this->write($request, $module, $action, $response);
        } catch (Throwable $e) {
            Log::channel('business')->warning('操作日志写入失败', [
                'module' => $module,
                'action' => $action,
                'error'  => $e->getMessage(),
            ]);
        }

        return $response;
    }

    private function write(Request $request, string $module, string $action, Response $response): void
    {
        $admin = $request->user();

        SysLog::create([
            'admin_id'    => (int) ($admin?->getAuthIdentifier() ?? 0),
            'admin_name'  => (string) ($admin?->getAttribute('username') ?? ''),
            'module'      => $module,
            'action'      => $action,
            'description' => $this->describe($request, $module, $action),
            'target_type' => $this->targetType($request),
            'target_id'   => (int) $request->route('id', 0),
            'before_json' => null,
            'after_json'  => $this->sanitize($request->except(['password', 'password_confirmation'])),
            'ip'          => (string) $request->ip(),
            'user_agent'  => mb_substr((string) $request->userAgent(), 0, 255),
            'request_id'  => RequestContext::requestId(),
            'result'      => $response->getStatusCode() < 400 ? 1 : 2,
            'created_at'  => now(),
        ]);
    }

    private function describe(Request $request, string $module, string $action): string
    {
        $moduleName = $module !== '' ? $module : '未知模块';
        $actionName = $action !== '' ? $action : strtolower($request->method());

        return "{$moduleName} · {$actionName} · {$request->method()} {$request->path()}";
    }

    private function targetType(Request $request): string
    {
        // 取路由第一段作为对象类型，如 admin-api/v1/configs/2 → configs
        $segments = array_values(array_filter(explode('/', $request->path())));

        return mb_substr($segments[2] ?? ($segments[1] ?? ''), 0, 64);
    }

    /** 递归脱敏 */
    private function sanitize(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            $isSensitive = false;
            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $result[$key] = '******';
                continue;
            }

            $result[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $result;
    }
}
