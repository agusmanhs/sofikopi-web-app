<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AbsensiService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class AbsensiApiController extends Controller
{
    public function __construct(protected AbsensiService $service) {}

    /**
     * @OA\Get(
     *     path="/api/absensi/history",
     *     tags={"Absensi"},
     *     summary="Get attendance history for logged in user",
     *     description="Mengambil riwayat absensi pegawai yang sedang login",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit data per page (default: 10)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="tanggal", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="jam_masuk", type="string", example="08:00"),
     *                 @OA\Property(property="jam_pulang", type="string", example="17:00"),
     *                 @OA\Property(property="status", type="string", example="Hadir"),
     *                 @OA\Property(property="durasi_kerja", type="string", example="8 Jam"),
     *                 @OA\Property(property="foto_masuk_url", type="string", example="http://example.com/storage/foto.jpg"),
     *                 @OA\Property(property="foto_pulang_url", type="string", example="http://example.com/storage/foto.jpg")
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=50)
     *             )
     *         )
     *     )
     * )
     */
    public function history(Request $request)
    {
        $user = $request->user();
        if (!$user->pegawai) {
            return ResponseHelper::error('User belum terdaftar sebagai pegawai', 400);
        }

        $limit = $request->input('limit', 10);
        
        // Menggunakan service/repository yang ada untuk mengambil data paginate
        // Karena service Absensi belum punya method paginate per pegawai spesifik, kita query manual 
        // atau idealnya tambahkan method di Service, tapi untuk cepat kita akses relasi.
        
        $absensis = $user->pegawai->absensis()
            ->with('shift')
            ->latest('tanggal')
            ->paginate($limit);

        // Transform data
        $data = $absensis->getCollection()->map(function ($absen) {
            return [
                'id' => $absen->id,
                'tanggal' => $absen->tanggal->format('Y-m-d'),
                'jam_masuk' => $absen->jam_masuk ? $absen->jam_masuk->format('H:i') : null,
                'jam_pulang' => $absen->jam_pulang ? $absen->jam_pulang->format('H:i') : null,
                'status' => $absen->status,
                'durasi_kerja' => $absen->durasi_kerja,
                'foto_masuk_url' => $absen->foto_masuk_url,
                'foto_pulang_url' => $absen->foto_pulang_url,
                'lokasi_masuk' => $absen->lokasi_masuk,
                'lokasi_pulang' => $absen->lokasi_pulang,
            ];
        });

        // Struktur respon manual agar sesuai format ResponseHelper + Meta Pagination
        return response()->json([
            'success' => true,
            'message' => 'Data retrieved successfully',
            'data' => $data,
            'meta' => [
                'current_page' => $absensis->currentPage(),
                'last_page' => $absensis->lastPage(),
                'per_page' => $absensis->perPage(),
                'total' => $absensis->total(),
            ]
        ]);
    }
    /**
     * @OA\Post(
     *     path="/api/absensi/masuk",
     *     tags={"Absensi"},
     *     summary="Clock-in (Absen Masuk)",
     *     description="Melakukan absen masuk dengan foto dan lokasi",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"shift_id", "latitude", "longitude", "foto"},
     *                 @OA\Property(property="shift_id", type="integer", example=1),
     *                 @OA\Property(property="latitude", type="string", example="-5.147615"),
     *                 @OA\Property(property="longitude", type="string", example="119.432731"),
     *                 @OA\Property(property="foto", type="string", format="binary"),
     *                 @OA\Property(property="device", type="string", example="iPhone 13")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil Absen Masuk",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Absen masuk berhasil"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad Request/Validation Error")
     * )
     */
    public function masuk(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'latitude' => 'required',
            'longitude' => 'required',
            'foto' => 'required|image|max:2048',
        ]);

        $user = $request->user();
        if (!$user->pegawai) {
            return ResponseHelper::error('User belum terdaftar sebagai pegawai', 400);
        }

        try {
            $result = $this->service->absenMasuk($user->pegawai, $request->all());
            return ResponseHelper::success($result, 'Absen masuk berhasil dilakukan.');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/absensi/pulang",
     *     tags={"Absensi"},
     *     summary="Clock-out (Absen Pulang)",
     *     description="Melakukan absen pulang dengan foto dan lokasi",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"latitude", "longitude", "foto"},
     *                 @OA\Property(property="shift_id", type="integer", example=1, description="Optional if only one active session"),
     *                 @OA\Property(property="latitude", type="string", example="-5.147615"),
     *                 @OA\Property(property="longitude", type="string", example="119.432731"),
     *                 @OA\Property(property="foto", type="string", format="binary"),
     *                 @OA\Property(property="keterangan", type="string", example="Pulang lebih awal karena urusan keluarga"),
     *                 @OA\Property(property="device", type="string", example="iPhone 13")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil Absen Pulang",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Absen pulang berhasil"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad Request/Validation Error")
     * )
     */
    public function pulang(Request $request)
    {
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'foto' => 'required|image|max:2048',
        ]);

        $user = $request->user();
        if (!$user->pegawai) {
            return ResponseHelper::error('User belum terdaftar sebagai pegawai', 400);
        }

        try {
            $result = $this->service->absenPulang($user->pegawai, $request->all());
            return ResponseHelper::success($result, 'Absen pulang berhasil dilakukan.');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }
}
