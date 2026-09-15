<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\BankCategory;
use App\Models\FileCategory;
use App\Support\ErrorCode;

/**
 * 分类管理服务（docs/04 §五 API-ADM-100）
 *
 * 两类分类共用同一套接口：
 *   - type=bank：操作 bank_categories（全局表，无 user_id，有 level 字段）
 *   - type=file：操作 file_categories 中 user_id=0 的系统预置分类（无 level 字段，恒为 1）
 *
 * 对外统一行结构（count 由 bank.question_bank_count / file.file_count 映射）。
 * 删除：软删；有子分类或在用（count>0）抛 BusinessException。
 */
class CategoryService
{
    public const TYPE_BANK = 'bank';
    public const TYPE_FILE = 'file';

    /** 列出分类（含 parent_name 组装） */
    public function list(string $type): array
    {
        $this->assertType($type);

        if ($type === self::TYPE_BANK) {
            $rows = BankCategory::query()->orderBy('parent_id')->orderBy('sort_order')->get();
            $nameMap = $rows->pluck('name', 'id')->all();

            return $rows->map(function (BankCategory $c) use ($nameMap) {
                return $this->bankRow($c, $nameMap[$c->parent_id] ?? '');
            })->all();
        }

        $rows = FileCategory::query()
            ->where('user_id', FileCategory::SYSTEM_USER_ID)
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->get();
        $nameMap = $rows->pluck('name', 'id')->all();

        return $rows->map(function (FileCategory $c) use ($nameMap) {
            return $this->fileRow($c, $nameMap[$c->parent_id] ?? '');
        })->all();
    }

    /** 新建分类 */
    public function create(array $data): array
    {
        $type = $data['type'];
        $this->assertType($type);

        if ($type === self::TYPE_BANK) {
            if (BankCategory::where('code', $data['code'])->exists()) {
                throw new BusinessException(ErrorCode::DATA_EXISTS, '分类编码已存在（全局唯一）', null, 409);
            }
            $parentId = (int) ($data['parent_id'] ?? 0);
            $level = $parentId > 0 ? BankCategory::LEVEL_2 : BankCategory::LEVEL_1;

            $cat = BankCategory::create([
                'parent_id'  => $parentId,
                'name'       => $data['name'],
                'code'       => $data['code'],
                'icon'       => $data['icon'] ?? '',
                'level'      => $level,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'status'     => (int) ($data['status'] ?? BankCategory::STATUS_NORMAL),
            ]);

            return $this->bankRow($cat, $parentId > 0 ? (string) (BankCategory::find($parentId)?->name ?? '') : '');
        }

        if (FileCategory::where('user_id', FileCategory::SYSTEM_USER_ID)->where('code', $data['code'])->exists()) {
            throw new BusinessException(ErrorCode::DATA_EXISTS, '系统预置分类编码已存在', null, 409);
        }

        $cat = FileCategory::create([
            'user_id'    => FileCategory::SYSTEM_USER_ID,
            'parent_id'  => (int) ($data['parent_id'] ?? 0),
            'name'       => $data['name'],
            'code'       => $data['code'],
            'icon'       => $data['icon'] ?? '',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status'     => (int) ($data['status'] ?? FileCategory::STATUS_NORMAL),
        ]);

        return $this->fileRow($cat, '');
    }

    /** 编辑分类（code 不可改；仅更新入参中存在的字段） */
    public function update(int $id, string $type, array $data): array
    {
        $this->assertType($type);

        $attributes = [];
        foreach (['name', 'icon', 'sort_order', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $attributes[$field] = $data[$field];
            }
        }

        if ($type === self::TYPE_BANK) {
            /** @var BankCategory|null $cat */
            $cat = BankCategory::find($id);
            if ($cat === null) {
                throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '分类不存在', null, 404);
            }
            if ($attributes !== []) {
                $cat->update($attributes);
            }

            return $this->bankRow($cat->fresh(), (string) (BankCategory::find($cat->parent_id)?->name ?? ''));
        }

        /** @var FileCategory|null $cat */
        $cat = FileCategory::where('user_id', FileCategory::SYSTEM_USER_ID)->find($id);
        if ($cat === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '分类不存在', null, 404);
        }
        if ($attributes !== []) {
            $cat->update($attributes);
        }

        return $this->fileRow($cat->fresh(), '');
    }

    /** 删除分类（软删；有子分类或在用禁止） */
    public function delete(int $id, string $type): void
    {
        $this->assertType($type);

        if ($type === self::TYPE_BANK) {
            /** @var BankCategory|null $cat */
            $cat = BankCategory::find($id);
            if ($cat === null) {
                throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '分类不存在', null, 404);
            }
            if (BankCategory::where('parent_id', $id)->exists()) {
                throw new BusinessException(ErrorCode::OPERATION_FORBIDDEN, '该分类存在子分类，无法删除', null, 409);
            }
            if ((int) $cat->question_bank_count > 0) {
                throw new BusinessException(ErrorCode::OPERATION_FORBIDDEN, '该分类下仍有题库，无法删除', null, 409);
            }
            $cat->delete();

            return;
        }

        /** @var FileCategory|null $cat */
        $cat = FileCategory::where('user_id', FileCategory::SYSTEM_USER_ID)->find($id);
        if ($cat === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '分类不存在', null, 404);
        }
        if (FileCategory::where('user_id', FileCategory::SYSTEM_USER_ID)->where('parent_id', $id)->exists()) {
            throw new BusinessException(ErrorCode::OPERATION_FORBIDDEN, '该分类存在子分类，无法删除', null, 409);
        }
        if ((int) $cat->file_count > 0) {
            throw new BusinessException(ErrorCode::OPERATION_FORBIDDEN, '该分类下仍有文件，无法删除', null, 409);
        }
        $cat->delete();
    }

    /** bank 行结构 */
    private function bankRow(BankCategory $c, string $parentName): array
    {
        return [
            'id'         => $c->id,
            'parent_id'  => (int) $c->parent_id,
            'parent_name'=> $parentName,
            'name'       => $c->name,
            'code'       => $c->code,
            'icon'       => $c->icon,
            'level'      => (int) $c->level,
            'count'      => (int) $c->question_bank_count,
            'sort_order' => (int) $c->sort_order,
            'status'     => (int) $c->status,
            'created_at' => $c->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /** file 行结构（无 level 字段，恒为 1） */
    private function fileRow(FileCategory $c, string $parentName): array
    {
        return [
            'id'         => $c->id,
            'parent_id'  => (int) $c->parent_id,
            'parent_name'=> $parentName,
            'name'       => $c->name,
            'code'       => $c->code,
            'icon'       => $c->icon,
            'level'      => 1,
            'count'      => (int) $c->file_count,
            'sort_order' => (int) $c->sort_order,
            'status'     => (int) $c->status,
            'created_at' => $c->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /** 校验 type 合法性 */
    private function assertType(string $type): void
    {
        if ($type !== self::TYPE_BANK && $type !== self::TYPE_FILE) {
            throw new BusinessException(ErrorCode::PARAM_ERROR, 'type 仅支持 bank|file', null, 422);
        }
    }
}
