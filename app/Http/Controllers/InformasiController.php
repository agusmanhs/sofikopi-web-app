<?php

namespace App\Http\Controllers;

use App\Models\Informasi;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Media;
use App\Services\InformasiService;


class InformasiController extends Controller
{
    public function __construct(
        protected InformasiService $service,
        protected FileUploadService $fileUploadService
    ) {}

    public function index()
    {
        $data = $this->service->all();
        return view('pages.informasi.index', compact('data'));
    }

    public function create()
    {
        return view('pages.informasi.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['judul', 'isi']);
        $data['user_id'] = Auth::id();

        if ($request->hasFile('gambar')) {
            $media = $this->fileUploadService->upload($request->file('gambar'), 'informasi', 'public', [
                'width' => 800,
                'height' => 600,
                'crop' => false,
            ]);
            $data['gambar'] = $media->path;
        }

        $this->service->create($data);

        return redirect()->route('informasi.index')->with('success', 'Informasi berhasil ditambahkan!');
    }

    public function show($id)
    {
        $informasi = $this->service->find($id);
        return view('pages.informasi.show', compact('informasi'));
    }

    public function edit($id)
    {
        $informasi = $this->service->find($id);
        return view('pages.informasi.edit', compact('informasi'));
    }

    public function update(Request $request, $id)
    {
        $informasi = $this->service->find($id);

        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['judul', 'isi']);

        if ($request->hasFile('gambar')) {
            // Delete old image
            if ($informasi->gambar) {
                $media = Media::where('path', $informasi->gambar)->first();
                if ($media) {
                    $this->fileUploadService->delete($media);
                }
            }

            $media = $this->fileUploadService->upload($request->file('gambar'), 'informasi', 'public', [
                'width' => 800,
                'height' => 600,
                'crop' => false,
            ]);
            $data['gambar'] = $media->path;
        }

        $this->service->update($id, $data);

        return redirect()->route('informasi.index')->with('success', 'Informasi berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $informasi = $this->service->find($id);

        if ($informasi->gambar) {
            $media = Media::where('path', $informasi->gambar)->first();
            if ($media) {
                $this->fileUploadService->delete($media);
            }
        }

        $this->service->delete($id);

        return redirect()->route('informasi.index')->with('success', 'Informasi berhasil dihapus!');
    }
}
