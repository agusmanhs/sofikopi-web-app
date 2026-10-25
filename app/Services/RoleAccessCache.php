<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cache menu sidebar dan permission per role.
 *
 * Tanpa cache ini setiap view yang dirender menjalankan ulang query menu, dan
 * setiap pengecekan izin menjalankan satu query join. Panggil flush() setiap
 * kali menu atau role_menu berubah (lihat Menu::booted dan PermissionController).
 */
class RoleAccessCache
{
    private const TTL = 1800;

    /** @var array<int, array<string, array<string, bool>>> */
    private array $permissionMemo = [];

    public function menuTree(Role $role): Collection
    {
        return Cache::remember($this->menuKey($role->id), self::TTL, function () use ($role) {
            return $role->menus()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->wherePivot('can_read', true)
                ->with(['children' => function ($q) use ($role) {
                    $q->where('is_active', true)->whereHas('roles', function ($rq) use ($role) {
                        $rq->where('roles.id', $role->id)->where('can_read', true);
                    })->with(['children' => function ($sq) use ($role) {
                        $sq->where('is_active', true)->whereHas('roles', function ($srq) use ($role) {
                            $srq->where('roles.id', $role->id)->where('can_read', true);
                        })->orderBy('order_no');
                    }])->orderBy('order_no');
                }])
                ->orderBy('order_no')
                ->get();
        });
    }

    public function can(?Role $role, string $menuSlug, string $action): bool
    {
        if (! $role) {
            return false;
        }

        return $this->permissions($role)[$menuSlug][$action] ?? false;
    }

    /**
     * @return array<string, array<string, bool>> slug => [create, read, update, delete]
     */
    public function permissions(Role $role): array
    {
        return $this->permissionMemo[$role->id] ??= Cache::remember(
            $this->permissionKey($role->id),
            self::TTL,
            function () use ($role) {
                $map = [];
                foreach ($role->menus()->get() as $menu) {
                    $map[$menu->slug] ??= [
                        'create' => (bool) $menu->pivot->can_create,
                        'read' => (bool) $menu->pivot->can_read,
                        'update' => (bool) $menu->pivot->can_update,
                        'delete' => (bool) $menu->pivot->can_delete,
                    ];
                }

                return $map;
            }
        );
    }

    public function flush(): void
    {
        $this->permissionMemo = [];

        foreach (Role::query()->pluck('id') as $roleId) {
            Cache::forget($this->menuKey($roleId));
            Cache::forget($this->permissionKey($roleId));
        }
    }

    private function menuKey(int $roleId): string
    {
        return "role_access.menu.{$roleId}";
    }

    private function permissionKey(int $roleId): string
    {
        return "role_access.permissions.{$roleId}";
    }
}
