<?php

namespace App\Http\Controllers;

use App\Services\RoleService;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $service
    ) {}

    public function index()
    {
        $roles = $this->service->all();
        return view('pages.role.index', compact('roles'));
    }

    public function create()
    {
        return view('pages.role.create');
    }

    public function store(RoleRequest $request)
    {
        $this->service->create($request->validated());
        return redirect()->route('role.index')->with('success', 'Role berhasil ditambahkan');
    }

    public function show($id)
    {
        $role = $this->service->find($id);
        return view('pages.role.show', compact('role'));
    }

    public function edit($id)
    {
        $role = $this->service->find($id);
        return view('pages.role.edit', compact('role'));
    }

    public function update(RoleRequest $request, $id)
    {
        $this->service->update($id, $request->validated());
        return redirect()->route('role.index')->with('success', 'Role berhasil diperbarui');
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);

            if (request()->wantsJson()) {
                return \App\Helpers\ResponseHelper::success(null, 'Role berhasil dihapus');
            }

            return redirect()->route('role.index')->with('success', 'Role berhasil dihapus');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return \App\Helpers\ResponseHelper::error('Gagal menghapus role: ' . $e->getMessage());
            }
            return redirect()->back()->with('error', 'Gagal menghapus role');
        }
    }
}
