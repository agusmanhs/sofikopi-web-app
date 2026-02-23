<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Media;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileApiController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/profile/update",
     *     tags={"Profile"},
     *     summary="Update user profile",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="nama_lengkap", type="string"),
     *                 @OA\Property(property="no_telp", type="string"),
     *                 @OA\Property(property="alamat", type="string"),
     *                 @OA\Property(property="foto", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Profile updated successfully")
     * )
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $pegawai = $user->pegawai;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'nama_lengkap' => 'required|string|max:255',
            'no_telp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($pegawai) {
            $data = [
                'nama_lengkap' => $request->nama_lengkap,
                'no_telp' => $request->no_telp,
                'alamat' => $request->alamat,
            ];

            if ($request->hasFile('foto')) {
                // Delete old photo
                if ($pegawai->foto) {
                    $media = Media::where('path', $pegawai->foto)->first();
                    if ($media) {
                        $this->fileUploadService->delete($media);
                    }
                }
                
                // Upload using project service
                $media = $this->fileUploadService->upload($request->file('foto'), 'pegawai', 'public', [
                    'width' => 300,
                    'height' => 300,
                    'crop' => true,
                ]);
                $data['foto'] = $media->path;
            }

            $pegawai->update($data);
        }

        $user->load(['pegawai.divisi', 'role']);
        return ResponseHelper::success(new \App\Http\Resources\UserResource($user), 'Profile updated successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/profile/password",
     *     tags={"Profile"},
     *     summary="Change user password",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","password","password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password updated successfully")
     * )
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return ResponseHelper::success(null, 'Password updated successfully');
    }
}
