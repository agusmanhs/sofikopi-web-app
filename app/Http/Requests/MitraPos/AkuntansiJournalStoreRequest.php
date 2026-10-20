<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\MitraPos\Concerns\NormalizesRupiah;

class AkuntansiJournalStoreRequest extends BaseRequest
{
    use NormalizesRupiah;

    protected function prepareForValidation()
    {
        $lines = $this->input('lines');

        if (! is_array($lines)) {
            return;
        }

        $this->merge([
            'lines' => array_map(function ($line) {
                if (! is_array($line)) {
                    return $line;
                }

                foreach (['debit', 'credit'] as $field) {
                    if (array_key_exists($field, $line)) {
                        $line[$field] = $this->normalizeRupiah($line[$field]);
                    }
                }

                return $line;
            }, $lines),
        ]);
    }

    public function rules(): array
    {
        return [
            'entry_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
            'lines.*.debit' => 'nullable|numeric|min:0|max:9999999999999',
            'lines.*.credit' => 'nullable|numeric|min:0|max:9999999999999',
        ];
    }

    public function messages(): array
    {
        return [
            'entry_date.required' => 'Tanggal jurnal wajib diisi.',
            'entry_date.date' => 'Tanggal jurnal tidak valid.',
            'entry_date.before_or_equal' => 'Tanggal jurnal tidak boleh di masa depan.',
            'description.required' => 'Keterangan jurnal wajib diisi.',
            'description.max' => 'Keterangan jurnal maksimal 255 huruf.',
            'lines.required' => 'Jurnal harus punya minimal 2 baris.',
            'lines.min' => 'Jurnal harus punya minimal 2 baris.',
            'lines.*.account_id.required' => 'Baris ke-:position: akun belum dipilih.',
            'lines.*.account_id.integer' => 'Baris ke-:position: akun tidak valid.',
            'lines.*.debit.numeric' => 'Baris ke-:position: nilai Debit harus berupa angka.',
            'lines.*.credit.numeric' => 'Baris ke-:position: nilai Kredit harus berupa angka.',
            'lines.*.debit.min' => 'Baris ke-:position: nilai Debit tidak boleh minus.',
            'lines.*.credit.min' => 'Baris ke-:position: nilai Kredit tidak boleh minus.',
            'lines.*.debit.max' => 'Baris ke-:position: nilai Debit terlalu besar.',
            'lines.*.credit.max' => 'Baris ke-:position: nilai Kredit terlalu besar.',
        ];
    }
}
