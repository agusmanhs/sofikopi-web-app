<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseRequest extends FormRequest
{
  /**
   * Authorization default
   * Bisa dioverride di child request
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Format error validasi standar (API friendly)
   */
  protected function failedValidation(Validator $validator)
  {
    if ($this->expectsJson() || $this->ajax()) {
      throw new HttpResponseException(
        response()->json([
          'success' => false,
          'message' => 'Terdapat kesalahan validasi data.',
          'errors'  => $validator->errors(),
        ], 422)
      );
    }

    parent::failedValidation($validator);
  }

  /**
   * Tempat normalisasi data
   * contoh: trim, cast, merge field
   */
  protected function prepareForValidation()
  {
    // $this->merge([
    //     'name' => trim($this->name),
    // ]);
  }
}
