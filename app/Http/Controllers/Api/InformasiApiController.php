<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Informasi;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

class InformasiApiController extends Controller
{
    public function __construct(
        protected \App\Services\InformasiService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/informasi",
     *     tags={"Informasi"},
     *     summary="Get list of informasi",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function index()
    {
        $data = $this->service->paginate(10);
        
        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'judul' => $item->judul,
                'isi' => $item->isi,
                'gambar_url' => $item->gambar_url,
                'created_by' => $item->user->name ?? 'System',
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                'created_at_human' => $item->created_at->diffForHumans(),
            ];
        });

        return ResponseHelper::success($data, 'Informasi retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/informasi/{id}",
     *     tags={"Informasi"},
     *     summary="Get detail informasi",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function show($id)
    {
        try {
            $item = $this->service->find($id);

            $response = [
                'id' => $item->id,
                'judul' => $item->judul,
                'isi' => $item->isi,
                'gambar_url' => $item->gambar_url,
                'created_by' => $item->user->name ?? 'System',
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                'created_at_human' => $item->created_at->diffForHumans(),
            ];

            return ResponseHelper::success($response, 'Informasi detail retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Informasi not found', 404);
        }
    }
}
