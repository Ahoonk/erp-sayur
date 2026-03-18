<?php
namespace App\Repositories;
use App\Helpers\ResponseHelper;
use App\Http\Resources\MitraResource;
use App\Http\Resources\PaginateResource;
use App\Models\Mitra;

class MitraRepository {
    public function __construct(private Mitra $model) {}

    public function index($perPage, $search) {
        $data = $this->model->search($search)->orderBy('nama')->paginate($perPage);
        return ResponseHelper::success(new PaginateResource($data, MitraResource::class), 'Mitra retrieved successfully');
    }

    public function all($search = '') {
        $data = $this->model->where('is_active', true)->search($search)->orderBy('nama')->get();
        return ResponseHelper::success(MitraResource::collection($data), 'All mitra retrieved');
    }

    public function show($id) {
        return ResponseHelper::success(new MitraResource($this->model->findOrFail($id)), 'Mitra retrieved');
    }

    public function store(array $data) {
        $mitra = $this->model->create($data);
        return ResponseHelper::success(new MitraResource($mitra), 'Mitra created successfully', 201);
    }

    public function update(array $data, $id) {
        $mitra = $this->model->findOrFail($id);
        $mitra->update($data);
        return ResponseHelper::success(new MitraResource($mitra), 'Mitra updated successfully');
    }

    public function destroy($id) {
        $mitra = $this->model->findOrFail($id);
        if ($mitra->pricelistMitra()->exists()) {
            return ResponseHelper::error('Mitra tidak bisa dihapus karena sudah ada pricelist', 422);
        }
        $mitra->delete();
        return ResponseHelper::success(null, 'Mitra deleted successfully');
    }
}
