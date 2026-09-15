<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

/**
 * 统一响应体（docs/04-API接口规范与登记表.md §2.2）
 *
 * 成功：
 *   { "code": 0, "message": "success", "data": {}, "request_id": "...", "timestamp": 1789000000 }
 *
 * 分页：
 *   { "code": 0, "message": "success",
 *     "data": { "list": [], "pagination": { "page": 1, "page_size": 20, "total": 135, "total_pages": 7 } } }
 *
 * ⚠️ 所有接口必须经由此类返回，禁止在控制器里手写 response()->json()
 */
final class ApiResponse
{
    /** 成功 */
    public static function success(mixed $data = null, string $message = 'success', int $httpStatus = 200): JsonResponse
    {
        return self::build(ErrorCode::SUCCESS, $message, $data, $httpStatus);
    }

    /** 成功（空数据） */
    public static function ok(string $message = 'success'): JsonResponse
    {
        return self::build(ErrorCode::SUCCESS, $message, null, 200);
    }

    /** 失败 */
    public static function error(
        int $code,
        string $message = '',
        mixed $data = null,
        int $httpStatus = 200
    ): JsonResponse {
        return self::build($code, $message !== '' ? $message : ErrorCode::message($code), $data, $httpStatus);
    }

    /**
     * 分页成功响应
     *
     * @param  LengthAwarePaginator  $paginator  分页器
     * @param  class-string<JsonResource>|null  $resourceClass  单条数据的 Resource 类（可选）
     */
    public static function paginate(LengthAwarePaginator $paginator, ?string $resourceClass = null): JsonResponse
    {
        $items = Collection::make($paginator->items());

        if ($resourceClass !== null && is_subclass_of($resourceClass, JsonResource::class)) {
            $items = $resourceClass::collection($items)->resolve();
        }

        return self::success([
            'list'       => $items->values()->all(),
            'pagination' => [
                'page'        => $paginator->currentPage(),
                'page_size'   => $paginator->perPage(),
                'total'       => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    /** 资源集合成功响应 */
    public static function collection(ResourceCollection $collection): JsonResponse
    {
        return self::success($collection->resolve());
    }

    /** 组装响应体 */
    private static function build(int $code, string $message, mixed $data, int $httpStatus): JsonResponse
    {
        return response()->json([
            'code'       => $code,
            'message'    => $message,
            'data'       => $data,
            'request_id' => RequestContext::requestId(),
            'timestamp'  => time(),
        ], $httpStatus, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
