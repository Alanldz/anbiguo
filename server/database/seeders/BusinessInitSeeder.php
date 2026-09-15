<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 业务域初始化数据：题库分类 / 文件预置分类 / 会员套餐 / 运营位
 *
 * 幂等：使用 updateOrInsert，可重复执行
 */
class BusinessInitSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ------------------------------------------------------------------
        // 1. 题库一级分类（对应参考软件题库市场的分类体系）
        // ------------------------------------------------------------------
        $categories = [
            ['code' => 'construction',  'name' => '建筑工程',  'icon' => 'icon-cat-construction.png',  'sort' => 10],
            ['code' => 'accounting',    'name' => '财会经济',  'icon' => 'icon-cat-accounting.png',    'sort' => 20],
            ['code' => 'civil_service', 'name' => '公务员',    'icon' => 'icon-cat-civil.png',         'sort' => 30],
            ['code' => 'teacher',       'name' => '教师资格',  'icon' => 'icon-cat-teacher.png',       'sort' => 40],
            ['code' => 'medical',       'name' => '医药卫生',  'icon' => 'icon-cat-medical.png',       'sort' => 50],
            ['code' => 'driver',        'name' => '驾考',      'icon' => 'icon-cat-driver.png',        'sort' => 60],
            ['code' => 'education',     'name' => '学历教育',  'icon' => 'icon-cat-education.png',     'sort' => 70],
            ['code' => 'computer',      'name' => '计算机',    'icon' => 'icon-cat-computer.png',      'sort' => 80],
            ['code' => 'language',      'name' => '外语',      'icon' => 'icon-cat-language.png',      'sort' => 90],
            ['code' => 'law',           'name' => '法律职业',  'icon' => 'icon-cat-law.png',           'sort' => 100],
            ['code' => 'fire_safety',   'name' => '消防工程',  'icon' => 'icon-cat-fire.png',          'sort' => 110],
            ['code' => 'safety',        'name' => '安全生产',  'icon' => 'icon-cat-safety.png',        'sort' => 120],
            ['code' => 'skill',         'name' => '职业技能',  'icon' => 'icon-cat-skill.png',         'sort' => 130],
            ['code' => 'other',         'name' => '其他',      'icon' => 'icon-cat-other.png',         'sort' => 999],
        ];

        foreach ($categories as $cat) {
            DB::table('bank_categories')->updateOrInsert(
                ['code' => $cat['code']],
                [
                    'parent_id' => 0,
                    'name'      => $cat['name'],
                    'icon'      => $cat['icon'],
                    'level'     => 1,
                    'sort_order' => $cat['sort'],
                    'status'    => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // ------------------------------------------------------------------
        // 2. 系统预置学习资料分类（user_id = 0）
        // ------------------------------------------------------------------
        $fileCategories = [
            ['code' => 'exam_paper',    'name' => '历年真题',  'sort' => 10],
            ['code' => 'lecture_note',  'name' => '讲义笔记',  'sort' => 20],
            ['code' => 'syllabus',      'name' => '考试大纲',  'sort' => 30],
            ['code' => 'video_course',  'name' => '视频课程',  'sort' => 40],
            ['code' => 'exercise_book', 'name' => '习题册',    'sort' => 50],
            ['code' => 'uncategorized', 'name' => '未分类',    'sort' => 999],
        ];

        foreach ($fileCategories as $cat) {
            DB::table('file_categories')->updateOrInsert(
                ['user_id' => 0, 'code' => $cat['code']],
                [
                    'parent_id'  => 0,
                    'name'       => $cat['name'],
                    'icon'       => '',
                    'sort_order' => $cat['sort'],
                    'status'     => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // ------------------------------------------------------------------
        // 3. 会员套餐
        // ------------------------------------------------------------------
        $plans = [
            [
                'name' => '月卡会员', 'level' => 1, 'duration_days' => 30,
                'price_amount' => 18.00, 'origin_amount' => 28.00,
                'description' => '30 天畅享全部会员题库与 AI 导题', 'ai_import_quota' => 30,
                'is_recommend' => 0, 'sort' => 10,
                'benefits_json' => json_encode([
                    '全部会员题库免费练', 'AI 导题每月 30 次', '错题导出 PDF', '去广告',
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                'name' => '季卡会员', 'level' => 2, 'duration_days' => 90,
                'price_amount' => 48.00, 'origin_amount' => 84.00,
                'description' => '90 天畅享，折合每月 16 元', 'ai_import_quota' => 100,
                'is_recommend' => 1, 'sort' => 20,
                'benefits_json' => json_encode([
                    '全部会员题库免费练', 'AI 导题每月 100 次', '错题导出 PDF', '去广告', '专属客服',
                ], JSON_UNESCAPED_UNICODE),
            ],
            [
                'name' => '年卡会员', 'level' => 3, 'duration_days' => 365,
                'price_amount' => 128.00, 'origin_amount' => 336.00,
                'description' => '365 天畅享，折合每月 10.6 元', 'ai_import_quota' => 500,
                'is_recommend' => 0, 'sort' => 30,
                'benefits_json' => json_encode([
                    '全部会员题库免费练', 'AI 导题每月 500 次', '错题导出 PDF', '去广告',
                    '专属客服', '优先使用新功能',
                ], JSON_UNESCAPED_UNICODE),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('order_member_plans')->updateOrInsert(
                ['name' => $plan['name']],
                [
                    'level'           => $plan['level'],
                    'duration_days'   => $plan['duration_days'],
                    'price_amount'    => $plan['price_amount'],
                    'origin_amount'   => $plan['origin_amount'],
                    'description'     => $plan['description'],
                    'benefits_json'   => $plan['benefits_json'],
                    'ai_import_quota' => $plan['ai_import_quota'],
                    'is_recommend'    => $plan['is_recommend'],
                    'sort_order'      => $plan['sort'],
                    'status'          => 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]
            );
        }

        // ------------------------------------------------------------------
        // 4. 运营位示例（首页轮播，占位图由前端兜底）
        // ------------------------------------------------------------------
        $banners = [
            ['position' => 'home_top', 'title' => '新人专享', 'subtitle' => '首月会员 9.9 元', 'link_type' => 5, 'link_value' => '/pages-sub/member/index', 'sort' => 10],
            ['position' => 'home_top', 'title' => 'AI 导题',   'subtitle' => '拍照上传，自动生成题库', 'link_type' => 5, 'link_value' => '/pages/ai/index', 'sort' => 20],
        ];

        foreach ($banners as $banner) {
            DB::table('content_banners')->updateOrInsert(
                ['position_code' => $banner['position'], 'title' => $banner['title']],
                [
                    'subtitle'    => $banner['subtitle'],
                    'image'       => '',
                    'link_type'   => $banner['link_type'],
                    'link_value'  => $banner['link_value'],
                    'client_scope' => 'all',
                    'sort_order'  => $banner['sort'],
                    'status'      => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]
            );
        }
    }
}
