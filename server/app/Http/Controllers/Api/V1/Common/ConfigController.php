<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Models\ContentBanner;
use App\Services\Config\ConfigCenter;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * 客户端公共配置控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-CFG-001
 */
class ConfigController extends Controller
{
    public function __construct(private readonly ConfigCenter $config)
    {
    }

    /**
     * API-CFG-001 客户端启动配置
     *
     * 用途：App / 小程序启动时一次性拉取站点信息、版本策略与首页运营位，
     *      避免首屏发起多个请求。
     */
    public function boot(): JsonResponse
    {
        return ApiResponse::success([
            'site' => [
                'name'              => (string) $this->config->get('site.name', '安必果刷题'),
                'logo'              => (string) $this->config->get('site.logo', ''),
                'icp_no'            => (string) $this->config->get('site.icp_no', ''),
                'customer_service'  => (string) $this->config->get('site.customer_service', ''),
                'share_title'       => (string) $this->config->get('site.share_title', ''),
            ],
            'version' => [
                // 低于该版本由客户端强制提示升级（具体弹窗策略由前端决定）
                'min_client_version' => (string) $this->config->get('site.client_min_version', '1.0.0'),
                'force_update'       => false,
            ],
            'banners' => $this->banners('home_top'),
        ]);
    }

    /**
     * 读取指定位置的运营位
     *
     * 运营位可能为空（新站未配置），此时返回空数组，前端需有兜底展示。
     */
    private function banners(string $positionCode): array
    {
        return ContentBanner::query()
            ->where('position_code', $positionCode)
            ->where('status', 1)
            ->where(function ($query) {
                $query->whereNull('started_at')->orWhere('started_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->get(['id', 'title', 'subtitle', 'image', 'link_type', 'link_value'])
            ->map(fn (ContentBanner $banner) => [
                'id'         => $banner->id,
                'title'      => $banner->title,
                'subtitle'   => $banner->subtitle,
                'image'      => $banner->image,
                'link_type'  => (int) $banner->link_type,
                'link_value' => $banner->link_value,
            ])
            ->all();
    }
}
