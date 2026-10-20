<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;

class VoidTransactionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:5|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
            'reason.min' => 'Alasan pembatalan minimal 5 huruf, tuliskan dengan jelas.',
            'reason.max' => 'Alasan pembatalan maksimal 255 huruf.',
        ];
    }
}
