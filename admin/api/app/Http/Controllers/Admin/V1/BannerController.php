<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Models\ContentBanner;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 运营位（Banner）管理（docs/04 §五 API-ADM-OPR-001）
 *   API-ADM-080 GET    /banners       列表（position 筛选）
 *   API-ADM-081 POST   /banners       新建
 *   API-ADM-082 PUT    /banners/{id}  编辑
 *   API-ADM-083 DELETE /banners/{id}  删除
 */
class BannerController extends Controller
{
    /** API-ADM-080 轮播列表 */
    public function index(Request $request): JsonResponse
    {
        $position = trim((string) $request->query('position', ''));
        $page = $this->pageParams();

        $query = ContentBanner::query();
        if ($position !== '') {
            $query->where('position_code', $position);
        }

        $paginator = $query->orderBy('position_code')->orderBy('sort_order')
            ->paginate($page['page_size'], ['*'], 'page', $page['page']);

        return ApiResponse::paginate($paginator);
    }

    /** API-ADM-081 新建 */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'position_code' => ['required', 'string', 'max:32'],
            'title'         => ['string', 'max:64'],
            'subtitle'      => ['string', 'max:120'],
            'image'         => ['required', 'string', 'max:255'],
            'link_type'     => ['integer', 'in:1,2,3,4,5'],
            'link_value'    => ['string', 'max:255'],
            'client_scope'  => ['string', 'max:32'],
            'sort_order'    => ['integer'],
            'status'        => ['integer', 'in:1,2'],
            'started_at'    => ['nullable', 'date'],
            'ended_at'      => ['nullable', 'date'],
        ]);

        $banner = ContentBanner::create($data);

        return ApiResponse::success($banner->toArray(), '创建成功');
    }

    /** API-ADM-082 编辑 */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var ContentBanner|null $banner */
        $banner = ContentBanner::find($id);
        if ($banner === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '运营位不存在', null, 404);
        }

        $data = $request->validate([
            'position_code' => ['string', 'max:32'],
            'title'         => ['string', 'max:64'],
            'subtitle'      => ['string', 'max:120'],
            'image'         => ['string', 'max:255'],
            'link_type'     => ['integer', 'in:1,2,3,4,5'],
            'link_value'    => ['string', 'max:255'],
            'client_scope'  => ['string', 'max:32'],
            'sort_order'    => ['integer'],
            'status'        => ['integer', 'in:1,2'],
            'started_at'    => ['nullable', 'date'],
            'ended_at'      => ['nullable', 'date'],
        ]);

        $banner->update($data);

        return ApiResponse::success($banner->toArray(), '更新成功');
    }

    /** API-ADM-083 删除 */
    public function destroy(int $id): JsonResponse
    {
        /** @var ContentBanner|null $banner */
        $banner = ContentBanner::find($id);
        if ($banner === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '运营位不存在', null, 404);
        }

        $banner->delete();

        return ApiResponse::ok('删除成功');
    }
}
