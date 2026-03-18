<?php
namespace App\Http\Controllers;
use App\Repositories\PricelistRepository;
use Illuminate\Http\Request;

class PricelistController extends Controller {
    public function __construct(private PricelistRepository $repository) {}

    // ---- Umum ----
    public function indexUmum(Request $request) {
        return $this->repository->indexUmum($request->query('per_page', 10), $request->query('search', ''));
    }

    public function getOrCreateUmum(Request $request) {
        $data = $request->validate([
            'tahun' => 'required|integer|min:2020|max:2100',
            'bulan' => 'required|integer|min:1|max:12',
            'periode' => 'required|integer|in:1,2',
        ]);
        return $this->repository->getOrCreateUmum($data['tahun'], $data['bulan'], $data['periode']);
    }

    public function saveUmumItems(Request $request, $pricelistId) {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.katalog_barang_id' => 'required|uuid|exists:katalog_barang,id',
            'items.*.modal_rata_rata' => 'nullable|numeric|min:0',
            'items.*.persentase' => 'nullable|numeric',
            'items.*.harga_jual' => 'required|numeric|min:0',
        ]);
        return $this->repository->saveUmumItems($pricelistId, $data['items']);
    }

    public function destroyUmum($id) {
        return $this->repository->destroyUmum($id);
    }

    // ---- Mitra ----
    public function indexMitra(Request $request) {
        return $this->repository->indexMitra(
            $request->query('per_page', 10),
            $request->query('search', ''),
            $request->query('mitra_id'),
        );
    }

    public function getOrCreateMitra(Request $request) {
        $data = $request->validate([
            'mitra_id' => 'required|uuid|exists:mitra,id',
            'tahun' => 'required|integer|min:2020|max:2100',
            'bulan' => 'required|integer|min:1|max:12',
            'periode' => 'required|integer|in:1,2',
        ]);
        return $this->repository->getOrCreateMitra($data['mitra_id'], $data['tahun'], $data['bulan'], $data['periode']);
    }

    public function saveMitraItems(Request $request, $pricelistId) {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.katalog_barang_id' => 'required|uuid|exists:katalog_barang,id',
            'items.*.modal_rata_rata' => 'nullable|numeric|min:0',
            'items.*.persentase' => 'nullable|numeric',
            'items.*.harga_jual' => 'required|numeric|min:0',
        ]);
        return $this->repository->saveMitraItems($pricelistId, $data['items']);
    }

    public function destroyMitra($id) {
        return $this->repository->destroyMitra($id);
    }
}
