<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;

class AkuntansiJournalStoreRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'entry_date' => 'required|date',
            'description' => 'required|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ];
    }
}
