<?php

namespace App\Helpers;


class ResponseHelper
{
  public static function success($data = null, string $message = 'Success', int $code = 200)
  {
    return response()->json([
      'success' => true,
      'message' => $message,
      'data' => $data,
    ], $code);
  }


  public static function error(string $message = 'Error', int $code = 400, $data = null)
  {
    return response()->json([
      'success' => false,
      'message' => $message,
      'data' => $data,
    ], $code);
  }

  public static function validationError($errors, string $message = 'Validation Error', int $code = 422)
  {
      return response()->json([
          'success' => false,
          'message' => $message,
          'errors' => $errors,
      ], $code);
  }
}
