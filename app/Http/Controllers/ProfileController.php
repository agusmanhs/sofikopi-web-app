<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct(
        protected \App\Services\FileUploadService $fileUploadService
    ) {}

    public function edit()
    {
        $user = Auth::user();
        $pegawai = $user->pegawai;
        return view('pages.profile.index', compact('user', 'pegawai'));
    }

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
                // Hapus foto lama jika ada
                if ($pegawai->foto) {
                    $media = \App\Models\Media::where('path', $pegawai->foto)->first();
                    if ($media) {
                        $this->fileUploadService->delete($media);
                    }
                }
                
                // Upload foto menggunakan service proyek
                $media = $this->fileUploadService->upload($request->file('foto'), 'pegawai', 'public', [
                    'width' => 300,
                    'height' => 300,
                    'crop' => true,
                ]);
                $data['foto'] = $media->path;
            }

            $pegawai->update($data);
        }

        return back()->with('success', 'Profil berhasil diperbarui!');
    }

    public function editPassword()
    {
        return view('pages.profile.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password berhasil diperbarui!');
    }
}
