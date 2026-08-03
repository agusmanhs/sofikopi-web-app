<?php

namespace App\Services\MitraPos;

use App\Models\PosTransaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Owner self-service for their own mitra's kasir accounts. User has no
 * BelongsToMitra scope (that trait auto-filters by the AUTHENTICATED user's
 * mitra_id, which would be wrong for cross-cutting lookups), so every query
 * here filters mitra_id manually — the same escape-hatch pattern documented
 * on BelongsToMitra::scopeForMitra().
 */
class MitraKasirUserService
{
    public const MAX_KASIR_PER_MITRA = 2;

    public function listForMitra(int $mitraId): Collection
    {
        return User::where('mitra_id', $mitraId)
            ->whereHas('role', fn ($q) => $q->where('slug', 'mitra-kasir'))
            ->orderBy('name')
            ->get();
    }

    public function createKasir(int $mitraId, array $data): User
    {
        if ($this->listForMitra($mitraId)->count() >= self::MAX_KASIR_PER_MITRA) {
            throw ValidationException::withMessages([
                'email' => 'Maksimal '.self::MAX_KASIR_PER_MITRA.' user kasir per mitra. Hapus salah satu kasir lama untuk menambah yang baru.',
            ]);
        }

        $kasirRole = Role::where('slug', 'mitra-kasir')->firstOrFail();

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $kasirRole->id,
            'mitra_id' => $mitraId,
        ]);
    }

    public function updateKasir(int $mitraId, int $userId, array $data): User
    {
        $kasir = $this->findOwnedKasir($mitraId, $userId);

        $kasir->name = $data['name'];
        $kasir->email = $data['email'];

        if (! empty($data['password'])) {
            $kasir->password = Hash::make($data['password']);
        }

        $kasir->save();

        return $kasir;
    }

    public function deleteKasir(int $mitraId, int $userId): void
    {
        $kasir = $this->findOwnedKasir($mitraId, $userId);

        if (PosTransaction::forMitra($mitraId)->where('user_id', $kasir->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Kasir ini sudah memiliki riwayat transaksi dan tidak bisa dihapus. Ganti password-nya saja untuk mencabut akses.',
            ]);
        }

        $kasir->delete();
    }

    /**
     * 404s (not 403) if the target isn't a kasir belonging to this mitra —
     * an owner has no business knowing whether a given id exists at all
     * outside their own tenant, let alone editing it.
     */
    private function findOwnedKasir(int $mitraId, int $userId): User
    {
        $kasir = User::where('id', $userId)
            ->where('mitra_id', $mitraId)
            ->whereHas('role', fn ($q) => $q->where('slug', 'mitra-kasir'))
            ->first();

        if (! $kasir) {
            throw new NotFoundHttpException('User kasir tidak ditemukan.');
        }

        return $kasir;
    }
}
