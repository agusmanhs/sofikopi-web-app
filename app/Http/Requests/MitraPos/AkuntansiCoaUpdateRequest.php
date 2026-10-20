<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\MitraPos\Concerns\NormalizesRupiah;
use App\Models\AkuntansiAccount;
use App\Models\Mitra;
use App\Services\MitraPos\MitraContext;

class AkuntansiCoaUpdateRequest extends BaseRequest
{
    use NormalizesRupiah;

    protected function prepareForValidation()
    {
        $balances = $this->input('opening_balance');

        if (! is_array($balances)) {
            return;
        }

        $this->merge([
            'opening_balance' => array_map(fn ($value) => $this->normalizeRupiah($value), $balances),
        ]);
    }

    public function rules(): array
    {
        return [
            'is_active' => 'nullable|array',
            'is_active.*' => 'nullable',
            'opening_balance' => 'nullable|array',
            'opening_balance.*' => 'nullable|numeric|min:0|max:9999999999999',
        ];
    }

    public function messages(): array
    {
        return [
            'opening_balance.*.numeric' => ':attribute harus berupa angka.',
            'opening_balance.*.min' => ':attribute tidak boleh minus.',
            'opening_balance.*.max' => ':attribute terlalu besar.',
        ];
    }

    public function attributes(): array
    {
        $ids = array_keys((array) $this->input('opening_balance', []));
        if ($ids === []) {
            return [];
        }

        $mitraId = $this->route('mitra') instanceof Mitra
            ? $this->route('mitra')->id
            : app(MitraContext::class)->id();

        $attributes = [];
        $accounts = AkuntansiAccount::forMitra($mitraId)->whereIn('id', $ids)->get();
        foreach ($accounts as $account) {
            $attributes['opening_balance.'.$account->id] = "Saldo awal akun {$account->code} {$account->name}";
        }

        return $attributes;
    }
}
