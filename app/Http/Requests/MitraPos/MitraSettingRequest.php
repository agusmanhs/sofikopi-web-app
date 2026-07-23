<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;

class MitraSettingRequest extends BaseRequest
{
    /**
     * monthly_revenue_target arrives Indonesian-formatted from the
     * rupiah-input JS ("15.000.000") — same normalization as
     * MitraMaterialRequest::prepareForValidation().
     */
    protected function prepareForValidation()
    {
        $target = $this->input('monthly_revenue_target');

        if (is_string($target) && $target !== '') {
            $this->merge([
                'monthly_revenue_target' => str_replace(',', '.', str_replace('.', '', $target)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'monthly_revenue_target' => 'nullable|numeric|min:0',
            'receipt_footer' => 'nullable|string|max:500',
            'service_charge_percent' => 'required|numeric|min:0|max:100',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'qris_fee_percent' => 'required|numeric|min:0|max:100',
            'transfer_fee_percent' => 'required|numeric|min:0|max:100',
            'edc_fee_percent' => 'required|numeric|min:0|max:100',
        ];
    }
}
