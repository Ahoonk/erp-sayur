<?php

namespace App\Http\Controllers;

use App\Http\Requests\Service\ServiceAddPartRequest;
use App\Http\Requests\Service\ServiceStatusUpdateRequest;
use App\Http\Requests\Service\ServiceStoreRequest;
use App\Http\Requests\Service\ServiceUpdateRequest;
use App\Models\ServiceOrder;
use App\Repositories\ServiceRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view services', only: ['index', 'show']),
            new Middleware('permission:create services', only: ['store', 'addPart']),
            new Middleware('permission:edit services', only: ['update', 'updateStatus']),
            new Middleware('permission:delete services', only: ['destroy', 'removePart']),
        ];
    }

    public function __construct(private ServiceRepository $repository) {}

    public function index(Request $request)
    {
        return $this->repository->index(
            $request->query('per_page', 10),
            $request->query('search', ''),
            $request->query('status'),
            $request->query('start_date'),
            $request->query('end_date'),
            $request->query('exclude_sudah_diambil', true)
        );
    }

    public function show($id)
    {
        return $this->repository->show($id);
    }

    public function store(ServiceStoreRequest $request)
    {
        return $this->repository->store($request->validated());
    }

    public function update(ServiceUpdateRequest $request, $id)
    {
        // Check if service is already completed (selesai) - only super admin can edit
        $service = ServiceOrder::findOrFail($id);
        if ($service->status === 'selesai') {
            $user = Auth::user();
            $isSuperAdmin = $user->roles()->whereIn('name', ['super-admin'])->exists();
            if (!$isSuperAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service yang sudah selesai tidak dapat diedit. Hubungi Super Admin.'
                ], 403);
            }
        }
        return $this->repository->update($id, $request->validated());
    }

    public function updateStatus(ServiceStatusUpdateRequest $request, $id)
    {
        return $this->repository->updateStatus($id, $request->validated());
    }

    public function addPart(ServiceAddPartRequest $request, $id)
    {
        // Check if service is already completed (selesai) - only super admin can add parts
        $service = ServiceOrder::findOrFail($id);
        if ($service->status === 'selesai') {
            $user = Auth::user();
            $isSuperAdmin = $user->roles()->whereIn('name', ['super-admin'])->exists();
            if (!$isSuperAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service yang sudah selesai tidak dapat ditambah sparepart. Hubungi Super Admin.'
                ], 403);
            }
        }
        try {
            return $this->repository->addPart($id, $request->validated());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function removePart($id, $partId)
    {
        // Check if service is already completed (selesai) - only super admin can remove parts
        $service = ServiceOrder::findOrFail($id);
        if ($service->status === 'selesai') {
            $user = Auth::user();
            $isSuperAdmin = $user->roles()->whereIn('name', ['super-admin'])->exists();
            if (!$isSuperAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service yang sudah selesai tidak dapat dihapus sparepartnya. Hubungi Super Admin.'
                ], 403);
            }
        }
        return $this->repository->removePart($id, $partId);
    }

    public function destroy($id)
    {
        // Check if service is already completed (selesai) - only super admin can delete
        $service = ServiceOrder::findOrFail($id);
        if ($service->status === 'selesai') {
            $user = Auth::user();
            $isSuperAdmin = $user->roles()->whereIn('name', ['super-admin'])->exists();
            if (!$isSuperAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service yang sudah selesai tidak dapat dihapus. Hubungi Super Admin.'
                ], 403);
            }
        }
        try {
            return $this->repository->destroy($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
