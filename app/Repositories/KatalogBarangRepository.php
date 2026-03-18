<?php
namespace App\Repositories;
use App\Helpers\ResponseHelper;
use App\Http\Resources\KatalogBarangResource;
use App\Http\Resources\PaginateResource;
use App\Models\Category;
use App\Models\KatalogBarang;
use Illuminate\Support\Facades\DB;

class KatalogBarangRepository {
    public function __construct(private KatalogBarang $model) {}

    public function index($perPage, $search, $categoryId = null) {
        $query = $this->model->newQuery()
            ->with(['category', 'unit'])
            ->search($search)
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->orderBy('kode_barang');
        $data = $query->paginate($perPage);
        return ResponseHelper::success(new PaginateResource($data, KatalogBarangResource::class), 'Katalog barang retrieved successfully');
    }

    public function all($search = '', $categoryId = null) {
        $query = $this->model->newQuery()
            ->with(['category', 'unit'])
            ->search($search)
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->orderBy('kode_barang');
        return ResponseHelper::success(KatalogBarangResource::collection($query->get()), 'All katalog barang retrieved');
    }

    public function show($id) {
        $item = $this->model->with(['category', 'unit'])->findOrFail($id);
        return ResponseHelper::success(new KatalogBarangResource($item), 'Katalog barang retrieved successfully');
    }

    public function store(array $data) {
        return DB::transaction(function () use ($data) {
            $category = Category::findOrFail($data['category_id']);
            $prefix = $category->kode_prefix ?? 'X';
            $kode = KatalogBarang::generateKode($prefix);
            $item = $this->model->create([
                'kode_barang' => $kode,
                'nama_barang' => $data['nama_barang'],
                'category_id' => $data['category_id'],
                'unit_id' => $data['unit_id'],
            ]);
            $item->load(['category', 'unit']);
            return ResponseHelper::success(new KatalogBarangResource($item), 'Katalog barang created successfully', 201);
        });
    }

    public function update(array $data, $id) {
        $item = $this->model->findOrFail($id);
        $item->update($data);
        $item->load(['category', 'unit']);
        return ResponseHelper::success(new KatalogBarangResource($item), 'Katalog barang updated successfully');
    }

    public function destroy($id) {
        $item = $this->model->findOrFail($id);
        if ($item->purchaseItems()->exists()) {
            return ResponseHelper::error('Katalog barang tidak bisa dihapus karena sudah ada pembelian', 422);
        }
        $item->delete();
        return ResponseHelper::success(null, 'Katalog barang deleted successfully');
    }
}
