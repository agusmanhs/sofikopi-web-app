<?php

namespace App\Http\Requests;

class UserRequest extends BaseRequest
{
    public function rules(): array
    {
        $userId = $this->route('user');

        // mitra_id must be REQUIRED for mitra-* roles and FORBIDDEN for any
        // other role: an internal-staff user with a stray mitra_id would be
        // tenant-scoped by BelongsToMitra and waved through EnsureMitraUser.
        // role_id being unconditionally required also closes the "no-role
        // sidebar fallback" hole from the risk register.
        $roleSlug = (string) \App\Models\Role::whereKey($this->input('role_id'))->value('slug');
        $isMitraRole = str_starts_with($roleSlug, 'mitra-');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role_id' => 'required|exists:roles,id',
            'mitra_id' => $isMitraRole
                ? ['required', 'exists:mitras,id']
                : ['prohibited'],
        ];

        // Password required only on create
        if ($this->isMethod('post')) {
            $rules['password'] = 'required|string|min:6|max:255';
            $rules['email'] .= '|unique:users,email';
        } else {
            // On update, password is optional
            $rules['password'] = 'nullable|string|min:6|max:255';
            $rules['email'] .= '|unique:users,email,' . $userId;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',
            'role_id.required' => 'Role wajib dipilih',
            'role_id.exists' => 'Role yang dipilih tidak valid',
            'mitra_id.required' => 'Mitra wajib dipilih untuk role Mitra',
            'mitra_id.prohibited' => 'Mitra hanya boleh diisi untuk role Mitra',
            'mitra_id.exists' => 'Mitra yang dipilih tidak valid',
        ];
    }

    /**
     * Empty select submits '' — normalize to null so 'prohibited' passes for
     * non-mitra roles AND the null lands in validated(), clearing any stale
     * mitra_id when a user is switched from a mitra role to an internal one.
     */
    protected function prepareForValidation()
    {
        if ($this->input('mitra_id') === '') {
            $this->merge(['mitra_id' => null]);
        }
    }

    /**
     * Handle failed validation for web requests
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // If request expects JSON, use API-style response
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        // Otherwise, redirect back with errors (web-style)
        throw new \Illuminate\Validation\ValidationException($validator);
    }
}
