<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Services\MenuService;
use App\Http\Requests\MenuRequest;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct(
        protected MenuService $service
    ) {}

    public function index()
    {
        $menus = $this->service->getMenuTree();
        return view('pages.menu.index', compact('menus'));
    }

    public function create()
    {
        $parentMenus = $this->service->all(); // Simplified for now
        return view('pages.menu.create', compact('parentMenus'));
    }

    public function store(MenuRequest $request)
    {
        $this->service->create($request->validated());
        return redirect()->route('menu.index')->with('success', 'Menu berhasil ditambahkan');
    }

    public function show($id)
    {
        $menu = $this->service->find($id);
        return view('pages.menu.show', compact('menu'));
    }

    public function edit($id)
    {
        $menu = $this->service->find($id);
        $parentMenus = $this->service->all();
        return view('pages.menu.edit', compact('menu', 'parentMenus'));
    }

    public function update(MenuRequest $request, $id)
    {
        $this->service->update($id, $request->validated());
        return redirect()->route('menu.index')->with('success', 'Menu berhasil diperbarui');
    }

    public function destroy($id)
    {
        try {
            $menu = $this->service->find($id);

            // Cek apakah menu punya children
            if ($menu->children()->count() > 0) {
                $message = 'Menu dengan sub-menu tidak bisa dihapus. Hapus sub-menu terlebih dahulu.';
                if (request()->wantsJson()) {
                    return ResponseHelper::error($message, 400);
                }
                return back()->with('error', $message);
            }

            // Detach semua permissions sebelum hapus
            if ($menu->roles()->count() > 0) {
                $menu->roles()->detach();
            }

            $this->service->delete($id);

            if (request()->wantsJson()) {
                return ResponseHelper::success(null, 'Menu berhasil dihapus');
            }

            return redirect()->route('menu.index')->with('success', 'Menu berhasil dihapus');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return ResponseHelper::error($e->getMessage(), 400);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
