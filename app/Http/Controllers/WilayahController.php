<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Services\WilayahService;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    protected $service;

    public function __construct(WilayahService $service)
    {
        $this->service = $service;
    }

    public function provinces()
    {
        return response()->json(Province::orderBy('name')->get());
    }

    public function regencies($provinceCode)
    {
        return response()->json(Regency::where('province_code', $provinceCode)->orderBy('name')->get());
    }

    public function districts($regencyCode)
    {
        return response()->json(District::where('regency_code', $regencyCode)->orderBy('name')->get());
    }

    public function syncProvinces()
    {
        $result = $this->service->syncProvinces();
        return response()->json($result);
    }

    public function syncRegencies(Request $request)
    {
        $result = $this->service->syncRegencies($request->province_code);
        return response()->json($result);
    }

    public function syncDistricts(Request $request)
    {
        $result = $this->service->syncDistricts($request->regency_code);
        return response()->json($result);
    }
}
