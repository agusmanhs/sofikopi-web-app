<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use App\Services\MitraPos\MitraContext;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MitraKasirUserRequest extends BaseRequest
{
    /**
     * Cek kepemilikan SEBELUM validasi: kalau tidak, aturan unique email
     * menjawab 422 untuk user mitra lain dan 404 untuk yang tidak ada, sehingga
     * pemilik mitra bisa menebak email dan id pengguna di mitra lain.
     */
    protected function prepareForValidation(): void
    {
        $target = $this->route('user');

        if ($target !== null) {
            $owned = $target->mitra_id === app(MitraContext::class)->id()
                && $target->role?->slug === 'mitra-kasir';

            if (! $owned) {
                throw new NotFoundHttpException('User kasir tidak ditemukan.');
            }
        }
    }

    /**
     * role_id/mitra_id are never accepted from the client — the service
     * always forces role to 'mitra-kasir' and mitra_id to the owner's own
     * mitra (see MitraKasirUserService::createKasir()).
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => $this->isMethod('post')
                ? 'required|string|min:6|max:255'
                : 'nullable|string|min:6|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kasir wajib diisi.',
            'email.required' => 'Email kasir wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ];
    }
}
