<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class MitraKasirUserRequest extends BaseRequest
{
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
}
